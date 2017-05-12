<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BelongsToMany extends Relation
{
    use Concerns\InteractsWithPivotTable;

    /**
     * The intermediate table for the relation.
     *
     * 关系的中间表
     *
     * @var string
     */
    protected $table;

    /**
     * The foreign key of the parent model.
     *
     * 父模型的外键
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The associated key of the relation.
     *
     * 关联的关系键
     *
     * @var string
     */
    protected $relatedKey;

    /**
     * The "name" of the relationship.
     *
     * “名字”的关系
     *
     * @var string
     */
    protected $relationName;

    /**
     * The pivot table columns to retrieve.
     *
     * 用于检索的主表列
     *
     * @var array
     */
    protected $pivotColumns = [];

    /**
     * Any pivot table restrictions for where clauses.
     *
     * 对于在哪里的子句的任何主表限制
     *
     * @var array
     */
    protected $pivotWheres = [];

    /**
     * Any pivot table restrictions for whereIn clauses.
     *
     * 对于其中的子句的任何主表限制
     *
     * @var array
     */
    protected $pivotWhereIns = [];

    /**
     * The custom pivot table column for the created_at timestamp.
     *
     * 自定义数据透视表created_at时间戳列
     *
     * @var string
     */
    protected $pivotCreatedAt;

    /**
     * The custom pivot table column for the updated_at timestamp.
     *
     * 自定义数据透视表updated_at时间戳列
     *
     * @var string
     */
    protected $pivotUpdatedAt;

    /**
     * The class name of the custom pivot model to use for the relationship.
     *
     * 用于关系的自定义轴心模型的类名
     *
     * @var string
     */
    protected $using;

    /**
     * The count of self joins.
     *
     * 自连接数
     *
     * @var int
     */
    protected static $selfJoinCount = 0;

    /**
     * Create a new belongs to many relationship instance.
     *
     * 创建一个新的属于许多关系实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $relatedKey
     * @param  string  $relationName
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $table, $foreignKey, $relatedKey, $relationName = null)
    {
        $this->table = $table;
        $this->relatedKey = $relatedKey;
        $this->foreignKey = $foreignKey;
        $this->relationName = $relationName;
        //创建一个新的关系实例
        parent::__construct($query, $parent);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * 在关系查询中设置基本约束
     *
     * @return void
     */
    public function addConstraints()
    {
        //为关系查询设置连接子句
        $this->performJoin();

        if (static::$constraints) {
            //为关系查询设置where子句
            $this->addWhereConstraints();
        }
    }

    /**
     * Set the join clause for the relation query.
     *
     * 为关系查询设置连接子句
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     */
    protected function performJoin($query = null)
    {
        $query = $query ?: $this->query;

        // We need to join to the intermediate table on the related model's primary
        // key column with the intermediate table's foreign key for the related
        // model instance. Then we can set the "where" for the parent models.
        //
        // 我们需要使用相关模型实例的中间表的外键连接到相关模型的主键列的中间表
        // 然后，我们可以为父模型设置“where”
        //
        //                     获取与模型相关联的表
        $baseTable = $this->related->getTable();
        //                              从模型中获取主键
        $key = $baseTable.'.'.$this->related->getKeyName();
        //向查询添加联接子句                          获得与此相关的完全限定的“相关密钥”
        $query->join($this->table, $key, '=', $this->getQualifiedRelatedKeyName());

        return $this;
    }

    /**
     * Set the where clause for the relation query.
     *
     * 为关系查询设置where子句
     *
     * @return $this
     */
    protected function addWhereConstraints()
    {
        //将基本WHERE子句添加到查询中
        $this->query->where(
            //获得与此相关的完全合格的外键                        获取模型主键的值
            $this->getQualifiedForeignKeyName(), '=', $this->parent->getKey()
        );

        return $this;
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * 为关系的贪婪加载设置约束条件
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        //在查询中添加“where in”子句(获得与此相关的完全合格的外键,获取一组模型的所有主键)
        $this->query->whereIn($this->getQualifiedForeignKeyName(), $this->getKeys($models));
    }

    /**
     * Initialize the relation on a set of models.
     *
     * 初始化一组模型的关系
     *
     * @param  array   $models
     * @param  string  $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            //在模型上设置整个关系数组              创建一个新的Eloquent集合实例
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * 将急切的结果与他们的父母相匹配
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        //根据关系的外键建立模型字典
        $dictionary = $this->buildDictionary($results);

        // Once we have an array dictionary of child objects we can easily match the
        // children back to their parent using the dictionary and the keys on the
        // the parent models. Then we will return the hydrated models back out.
        //
        // 一旦我们有了一个子对象数组字典，我们就可以使用字典和父模型中的键轻松地将这些子对象与它们的父类匹配
        // 然后我们会把水分的模型返回
        //
        foreach ($models as $model) {
            //                           获取模型主键的值
            if (isset($dictionary[$key = $model->getKey()])) {
                //在模型上设置整个关系数组
                $model->setRelation(
                    //                      创建一个新的Eloquent集合实例
                    $relation, $this->related->newCollection($dictionary[$key])
                );
            }
        }

        return $models;
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * 根据关系的外键建立模型字典
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        // First we will build a dictionary of child models keyed by the foreign key
        // of the relation so that we will easily and quickly match them to their
        // parents without having a possibly slow inner loops for every models.
        //
        // 首先,我们将建立一个字典的键控的孩子模型外键的关系,这样我们会轻松快速地匹配他们父母不可能减缓内循环为每个模型
        //
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->pivot->{$this->foreignKey}][] = $result;
        }

        return $dictionary;
    }

    /**
     * Specify the custom pivot model to use for the relationship.
     *
     * 指定用于关系的自定义轴心模型
     *
     * @param  string  $class
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function using($class)
    {
        $this->using = $class;

        return $this;
    }

    /**
     * Set a where clause for a pivot table column.
     *
     * 为主表列设置where子句
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wherePivot($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->pivotWheres[] = func_get_args();
        //将基本WHERE子句添加到查询中
        return $this->where($this->table.'.'.$column, $operator, $value, $boolean);
    }

    /**
     * Set a "where in" clause for a pivot table column.
     *
     * 为主表列设置“where in”子句
     *
     * @param  string  $column
     * @param  mixed   $values
     * @param  string  $boolean
     * @param  bool    $not
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wherePivotIn($column, $values, $boolean = 'and', $not = false)
    {
        $this->pivotWhereIns[] = func_get_args();
        //在查询中添加“在哪里”子句
        return $this->whereIn($this->table.'.'.$column, $values, $boolean, $not);
    }

    /**
     * Set an "or where" clause for a pivot table column.
     *
     * 为主表列设置“or where”子句
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orWherePivot($column, $operator = null, $value = null)
    {
        //为主表列设置where子句
        return $this->wherePivot($column, $operator, $value, 'or');
    }

    /**
     * Set an "or where in" clause for a pivot table column.
     *
     * 为主表列设置“or where in”子句
     *
     * @param  string  $column
     * @param  mixed   $values
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orWherePivotIn($column, $values)
    {
        //为主表列设置“where in”子句
        return $this->wherePivotIn($column, $values, 'or');
    }

    /**
     * Find a related model by its primary key or return new instance of the related model.
     *
     * 通过它的主键找到相关的模型，或者返回相关模型的新实例
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function findOrNew($id, $columns = ['*'])
    {
        //              通过它的主键找到相关的模型
        if (is_null($instance = $this->find($id, $columns))) {
            //创建给定模型的新实例
            $instance = $this->related->newInstance();
        }

        return $instance;
    }

    /**
     * Get the first related model record matching the attributes or instantiate it.
     *
     * 获取与属性相关的第一个相关的模型记录，或者实例化它
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrNew(array $attributes)
    {
        //                   将基本WHERE子句添加到查询中   执行查询和得到的第一个结果
        if (is_null($instance = $this->where($attributes)->first())) {
            //                        创建给定模型的新实例
            $instance = $this->related->newInstance($attributes);
        }

        return $instance;
    }

    /**
     * Get the first related record matching the attributes or create it.
     *
     * 获取与属性相关的第一个相关记录，或者创建它
     *
     * @param  array  $attributes
     * @param  array  $joining
     * @param  bool   $touch
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrCreate(array $attributes, array $joining = [], $touch = true)
    {
        //                   将基本WHERE子句添加到查询中   执行查询和得到的第一个结果
        if (is_null($instance = $this->where($attributes)->first())) {
            //              创建相关模型的新实例
            $instance = $this->create($attributes, $joining, $touch);
        }

        return $instance;
    }

    /**
     * Create or update a related record matching the attributes, and fill it with values.
     *
     * 创建或更新与属性匹配的相关记录，并将其填充为值
     *
     * @param  array  $attributes
     * @param  array  $values
     * @param  array  $joining
     * @param  bool   $touch
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreate(array $attributes, array $values = [], array $joining = [], $touch = true)
    {
        //                   将基本WHERE子句添加到查询中   执行查询和得到的第一个结果
        if (is_null($instance = $this->where($attributes)->first())) {
            //              创建相关模型的新实例
            return $this->create($values, $joining, $touch);
        }
        //用属性数组填充模型
        $instance->fill($values);
        //将模型保存到数据库中
        $instance->save(['touch' => false]);

        return $instance;
    }

    /**
     * Find a related model by its primary key.
     *
     * 通过它的主键找到相关的模型
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|null
     */
    public function find($id, $columns = ['*'])
    {
        //                       通过主键找到多个相关的模型           将基本WHERE子句添加到查询中
        return is_array($id) ? $this->findMany($id, $columns) : $this->where(
            //获取相关模型的关系->获取表格的键名
            $this->getRelated()->getQualifiedKeyName(), '=', $id
        )->first($columns);//执行查询和得到的第一个结果
    }

    /**
     * Find multiple related models by their primary keys.
     *
     * 通过主键找到多个相关的模型
     *
     * @param  mixed  $ids
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findMany($ids, $columns = ['*'])
    {
        //                     //获取相关模型的关系->创建一个新的Eloquent集合实例     在查询中添加“在哪里”子句
        return empty($ids) ? $this->getRelated()->newCollection() : $this->whereIn(
            //获取相关模型的关系->获取表格的键名
            $this->getRelated()->getQualifiedKeyName(), $ids
        )->get($columns);//将查询执行为“SELECT”语句
    }

    /**
     * Find a related model by its primary key or throw an exception.
     *
     * 通过它的主键找到一个相关的模型，或者抛出一个异常
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, $columns = ['*'])
    {
        //           通过它的主键找到相关的模型
        $result = $this->find($id, $columns);

        if (is_array($id)) {
            if (count($result) == count(array_unique($id))) {
                return $result;
            }
        } elseif (! is_null($result)) {
            return $result;
        }
        //                                设置受影响的Eloquent型和实例ids
        throw (new ModelNotFoundException)->setModel(get_class($this->related));
    }

    /**
     * Execute the query and get the first result.
     *
     * 执行查询并获得第一个结果
     *
     * @param  array   $columns
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        //别名设置查询的“limit”值->执行查询作为“select”语句
        $results = $this->take(1)->get($columns);

        return count($results) > 0 ? $results->first() : null;
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * 执行查询并获得第一个结果或抛出异常
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function firstOrFail($columns = ['*'])
    {
        //                       执行查询并获得第一个结果
        if (! is_null($model = $this->first($columns))) {
            return $model;
        }
        //                                设置受影响的Eloquent型和实例ids
        throw (new ModelNotFoundException)->setModel(get_class($this->related));
    }

    /**
     * Get the results of the relationship.
     *
     * 得到关系的结果
     *
     * @return mixed
     */
    public function getResults()
    {
        //执行查询作为“select”语句
        return $this->get();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * 执行查询作为“select”语句
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        // First we'll add the proper select columns onto the query so it is run with
        // the proper columns. Then, we will get the results and hydrate out pivot
        // models with the result of those columns as a separate model relation.
        //
        // 首先，我们将在查询中添加适当的select列，以便使用正确的列运行它
        // 然后，我们将得到结果，并将这些列的结果作为一个单独的模型关系得到结果
        //
        //            获取底层查询生成器实例
        $columns = $this->query->getQuery()->columns ? [] : $columns;
        //将作用域应用到Eloquent的生成器实例并返回它
        $builder = $this->query->applyScopes();
        //向查询添加新的选择列
        $models = $builder->addSelect(
            //获取关系查询的select列
            $this->shouldSelect($columns)
        )->getModels();//得到不贪婪加载的水合模型
        //对模型的主表关系进行补充
        $this->hydratePivotRelation($models);

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
        //
        // 如果我们真的找到了模型，我们也会急切地加载任何被指定为需要被加载的关系
        // 这将为开发人员解决n+1的查询问题，并提高性能
        //
        if (count($models) > 0) {
            //贪婪加载的关系模型
            $models = $builder->eagerLoadRelations($models);
        }
        //创建一个新的Eloquent集合实例
        return $this->related->newCollection($models);
    }

    /**
     * Get the select columns for the relation query.
     *
     * 获取关系查询的select列
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function shouldSelect(array $columns = ['*'])
    {
        if ($columns == ['*']) {
            //                      获取与模型相关联的表
            $columns = [$this->related->getTable().'.*'];
        }
        //                              得到关于关系的主列
        return array_merge($columns, $this->aliasedPivotColumns());
    }

    /**
     * Get the pivot columns for the relation.
     *
     * 得到关于关系的主列
     *
     * "pivot_" is prefixed ot each column for easy removal later.
     *
     * 每列“pivot_”前缀不方便日后删除
     *
     * @return array
     */
    protected function aliasedPivotColumns()
    {
        $defaults = [$this->foreignKey, $this->relatedKey];
        //                                                           在每个项目上运行map
        return collect(array_merge($defaults, $this->pivotColumns))->map(function ($column) {
            return $this->table.'.'.$column.' as pivot_'.$column;
        })->unique()->all();//只返回集合数组中的唯一项->获取集合中的所有项目
    }

    /**
     * Get a paginator for the "select" statement.
     *
     * 得到一个paginator“select”的声明
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        //向查询添加新的选择列(获取关系查询的select列)
        $this->query->addSelect($this->shouldSelect($columns));
        //用给定的值调用给定的闭包，然后返回值(给定的查询分页
        return tap($this->query->paginate($perPage, $columns, $pageName, $page), function ($paginator) {
            $this->hydratePivotRelation($paginator->items());//对模型的主表关系进行补充
        });
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * 给定查询到一个简单的paginator分页
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        //向查询添加新的选择列(获取关系查询的select列)
        $this->query->addSelect($this->shouldSelect($columns));
        //用给定的值调用给定的闭包，然后返回值(给定的查询通过简单的分页查询分页
        return tap($this->query->simplePaginate($perPage, $columns, $pageName, $page), function ($paginator) {
            $this->hydratePivotRelation($paginator->items());//对模型的主表关系进行补充
        });
    }

    /**
     * Chunk the results of the query.
     *
     * 将查询的结果分块
     *
     * @param  int  $count
     * @param  callable  $callback
     * @return bool
     */
    public function chunk($count, callable $callback)
    {
        //向查询添加新的选择列(获取关系查询的select列)
        $this->query->addSelect($this->shouldSelect());
        //查询的结果块
        return $this->query->chunk($count, function ($results) use ($callback) {
            $this->hydratePivotRelation($results->all());//对模型的主表关系进行补充

            return $callback($results);
        });
    }

    /**
     * Hydrate the pivot table relationship on the models.
     *
     * 对模型的主表关系进行补充
     *
     * @param  array  $models
     * @return void
     */
    protected function hydratePivotRelation(array $models)
    {
        // To hydrate the pivot relationship, we will just gather the pivot attributes
        // and create a new Pivot model, which is basically a dynamic model that we
        // will set the attributes, table, and connections on it so it will work.
        //
        // 水合物主的关系,我们会收集主属性和创建一个新的主模型,这是一个动态模型,我们将设置属性,表,和联系工作
        //
        foreach ($models as $model) {
            //在模型中设置特定关系                 //创建一个新的主模型实例实例
            $model->setRelation('pivot', $this->newExistingPivot(
                //从模型中获得主属性
                $this->migratePivotAttributes($model)
            ));
        }
    }

    /**
     * Get the pivot attributes from a model.
     *
     * 从模型中获得主属性
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    protected function migratePivotAttributes(Model $model)
    {
        $values = [];
        //获取模型上的所有当前属性
        foreach ($model->getAttributes() as $key => $value) {
            // To get the pivots attributes we will just take any of the attributes which
            // begin with "pivot_" and add those to this arrays, as well as unsetting
            // them from the parent's models since they exist in a different table.
            //
            // 枢轴点我们将把任何属性,首先“pivot_”和那些添加到这个数组,以及取消他们从父母的模型,因为他们存在于一个不同的表
            //
            if (strpos($key, 'pivot_') === 0) {
                $values[substr($key, 6)] = $value;

                unset($model->$key);
            }
        }

        return $values;
    }

    /**
     * If we're touching the parent model, touch.
     *
     * 如果我们接触到父模型，触摸
     *
     * @return void
     */
    public function touchIfTouching()
    {
        //确定我们是否应该与父进程保持同步
        if ($this->touchingParent()) {
            //获得关系的父模型    更新模型的更新时间戳
            $this->getParent()->touch();
        }
        //                      确定模型是否触及给定的关系
        if ($this->getParent()->touches($this->relationName)) {
            //更新模型的更新时间戳
            $this->touch();
        }
    }

    /**
     * Determine if we should touch the parent on sync.
     *
     * 确定我们是否应该与父进程保持同步
     *
     * @return bool
     */
    protected function touchingParent()
    {
        //获取相关模型的关系        确定模型是否触及给定的关系   试图猜出关系的逆的名称
        return $this->getRelated()->touches($this->guessInverseRelation());
    }

    /**
     * Attempt to guess the name of the inverse of the relation.
     *
     * 试图猜出关系的逆的名称
     *
     * @return string
     */
    protected function guessInverseRelation()
    {
        //转换值为驼峰命名       获取一个英语单词的复数形式       获得关系的父模型
        return Str::camel(Str::plural(class_basename($this->getParent())));
    }

    /**
     * Touch all of the related models for the relationship.
     *
     * 联系所有相关的关系模型
     *
     * E.g.: Touch all roles associated with this user.
     *
     * 如。所有与该用户相关的角色:联系
     *
     * @return void
     */
    public function touch()
    {
        //获取相关模型的关系             从模型中获取主键
        $key = $this->getRelated()->getKeyName();

        $columns = [
            //         获取“更新at”列的名称                       为模型获取一个新的时间戳
            $this->related->getUpdatedAtColumn() => $this->related->freshTimestampString(),
        ];

        // If we actually have IDs for the relation, we will run the query to update all
        // the related model's timestamps, to make sure these all reflect the changes
        // to the parent models. This will help us keep any caching synced up here.
        //
        // 如果我们确实有关于该关系的id，我们将运行查询来更新所有相关模型的时间戳，以确保这些都反映了对父模型的更改
        // 这将帮助我们保持缓存同步
        //
        //                       获取相关模型的所有id
        if (count($ids = $this->allRelatedIds()) > 0) {
            //获取相关模型的关系   获取模型表的新查询生成器  在查询中添加“where in”子句   更新数据库中的记录
            $this->getRelated()->newQuery()->whereIn($key, $ids)->update($columns);
        }
    }

    /**
     * Get all of the IDs for the related models.
     *
     * 获取相关模型的所有id
     *
     * @return \Illuminate\Support\Collection
     */
    public function allRelatedIds()
    {
        //          获取相关模型的关系
        $related = $this->getRelated();
        //获取关系的基础查询         设置要选择的列
        return $this->getQuery()->select(
            //获取表格的键名
            $related->getQualifiedKeyName()
            //用给定列的值获取数组  从模型中获取主键
        )->pluck($related->getKeyName());
    }

    /**
     * Save a new model and attach it to the parent model.
     *
     * 保存一个新模型并将其附加到父模型
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $pivotAttributes
     * @param  bool   $touch
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function save(Model $model, array $pivotAttributes = [], $touch = true)
    {
        //将模型保存到数据库中
        $model->save(['touch' => false]);
        //向父节点添加一个模型(获取模型主键的值,)
        $this->attach($model->getKey(), $pivotAttributes, $touch);

        return $model;
    }

    /**
     * Save an array of new models and attach them to the parent model.
     *
     * 保存一组新模型并将它们附加到父模型中
     *
     * @param  \Illuminate\Support\Collection|array  $models
     * @param  array  $pivotAttributes
     * @return array
     */
    public function saveMany($models, array $pivotAttributes = [])
    {
        foreach ($models as $key => $model) {
            //保存一个新模型并将其附加到父模型    使用“点”符号从数组中获取一个项
            $this->save($model, (array) Arr::get($pivotAttributes, $key), false);
        }
        //如果我们接触到父模型，触摸
        $this->touchIfTouching();

        return $models;
    }

    /**
     * Create a new instance of the related model.
     *
     * 创建相关模型的新实例
     *
     * @param  array  $attributes
     * @param  array  $joining
     * @param  bool   $touch
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $attributes, array $joining = [], $touch = true)
    {
        //                       创建给定模型的新实例
        $instance = $this->related->newInstance($attributes);

        // Once we save the related model, we need to attach it to the base model via
        // through intermediate table so we'll use the existing "attach" method to
        // accomplish this which will insert the record and any more attributes.
        //
        // 一旦我们保存相关的模型,我们需要将它附加到基本模型通过通过中间表,所以我们将使用现有的“附加”的方法来做到这一点这将插入记录和更多的属性
        //
        // 将模型保存到数据库中
        $instance->save(['touch' => false]);
        //向父节点添加一个模型     获取模型主键的值
        $this->attach($instance->getKey(), $joining, $touch);

        return $instance;
    }

    /**
     * Create an array of new instances of the related models.
     *
     * 创建相关模型的新实例数组
     *
     * @param  array  $records
     * @param  array  $joinings
     * @return array
     */
    public function createMany(array $records, array $joinings = [])
    {
        $instances = [];

        foreach ($records as $key => $record) {
            //                 创建相关模型的新实例             使用“点”符号从数组中获取一个项
            $instances[] = $this->create($record, (array) Arr::get($joinings, $key), false);
        }
        //如果我们接触到父模型，触摸
        $this->touchIfTouching();

        return $instances;
    }

    /**
     * Add the constraints for a relationship query.
     *
     * 为关系查询添加约束
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        //获取底层查询生成器实例                  获取底层查询生成器实例
        if ($parentQuery->getQuery()->from == $query->getQuery()->from) {
            //在相同的表中添加关系查询的约束
            return $this->getRelationExistenceQueryForSelfJoin($query, $parentQuery, $columns);
        }
        //为关系查询设置连接子句
        $this->performJoin($query);
        //为内部关系存在查询添加约束
        return parent::getRelationExistenceQuery($query, $parentQuery, $columns);
    }

    /**
     * Add the constraints for a relationship query on the same table.
     *
     * 在相同的表中添加关系查询的约束
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQueryForSelfJoin(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        //设置要选择的列
        $query->select($columns);
        //设置查询对象的表            获取与模型相关联的表                获取一个关系连接表散列
        $query->from($this->related->getTable().' as '.$hash = $this->getRelationCountHash());
        //设置与模型相关联的表
        $this->related->setTable($hash);
        //为关系查询设置连接子句
        $this->performJoin($query);
        //为内部关系存在查询添加约束
        return parent::getRelationExistenceQuery($query, $parentQuery, $columns);
    }

    /**
     * Get the key for comparing against the parent key in "has" query.
     *
     * 获取在“已”查询中与父键进行比较的键
     *
     * @return string
     */
    public function getExistenceCompareKey()
    {
        //          获得与此相关的完全合格的外键
        return $this->getQualifiedForeignKeyName();
    }

    /**
     * Get a relationship join table hash.
     *
     * 获取一个关系连接表散列
     *
     * @return string
     */
    public function getRelationCountHash()
    {
        return 'laravel_reserved_'.static::$selfJoinCount++;
    }

    /**
     * Specify that the pivot table has creation and update timestamps.
     *
     * 指定主表有创建和更新时间戳
     *
     * @param  mixed  $createdAt
     * @param  mixed  $updatedAt
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function withTimestamps($createdAt = null, $updatedAt = null)
    {
        $this->pivotCreatedAt = $createdAt;
        $this->pivotUpdatedAt = $updatedAt;
        //将主表上的列设置为检索        获取“创建at”列的名称    获取“更新at”列的名称
        return $this->withPivot($this->createdAt(), $this->updatedAt());
    }

    /**
     * Get the name of the "created at" column.
     *
     * 获取“创建at”列的名称
     *
     * @return string
     */
    public function createdAt()
    {
        //                                      获取“创建at”列的名称
        return $this->pivotCreatedAt ?: $this->parent->getCreatedAtColumn();
    }

    /**
     * Get the name of the "updated at" column.
     *
     * 获取“更新at”列的名称
     *
     * @return string
     */
    public function updatedAt()
    {
        //                                            获取“创建at”列的名称
        return $this->pivotUpdatedAt ?: $this->parent->getUpdatedAtColumn();
    }

    /**
     * Get the fully qualified foreign key for the relation.
     *
     * 获得与此相关的完全合格的外键
     *
     * @return string
     */
    public function getQualifiedForeignKeyName()
    {
        return $this->table.'.'.$this->foreignKey;
    }

    /**
     * Get the fully qualified "related key" for the relation.
     *
     * 获得与此相关的完全限定的“相关密钥”
     *
     * @return string
     */
    public function getQualifiedRelatedKeyName()
    {
        return $this->table.'.'.$this->relatedKey;
    }

    /**
     * Get the intermediate table for the relationship.
     *
     * 获取关系的中间表格
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the relationship name for the relationship.
     *
     * 获取关系的名称
     *
     * @return string
     */
    public function getRelationName()
    {
        return $this->relationName;
    }
}
