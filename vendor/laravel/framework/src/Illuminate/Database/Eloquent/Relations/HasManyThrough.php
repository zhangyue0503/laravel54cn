<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class HasManyThrough extends Relation
{
    /**
     * The "through" parent model instance.
     *
     * “通过”父模型实例
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $throughParent;

    /**
     * The far parent model instance.
     *
     * 远父模型实例
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $farParent;

    /**
     * The near key on the relationship.
     *
     * 关系的键
     *
     * @var string
     */
    protected $firstKey;

    /**
     * The far key on the relationship.
     *
     * 这是关系的远键
     *
     * @var string
     */
    protected $secondKey;

    /**
     * The local key on the relationship.
     *
     * 关于关系的本地键
     *
     * @var string
     */
    protected $localKey;

    /**
     * Create a new has many through relationship instance.
     *
     * 创建一个新的有许多通过关系实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $farParent
     * @param  \Illuminate\Database\Eloquent\Model  $throughParent
     * @param  string  $firstKey
     * @param  string  $secondKey
     * @param  string  $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $farParent, Model $throughParent, $firstKey, $secondKey, $localKey)
    {
        $this->localKey = $localKey;
        $this->firstKey = $firstKey;
        $this->secondKey = $secondKey;
        $this->farParent = $farParent;
        $this->throughParent = $throughParent;
        //创建一个新的关系实例
        parent::__construct($query, $throughParent);
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
        $localValue = $this->farParent[$this->localKey];
        //在查询中设置连接子句
        $this->performJoin();

        if (static::$constraints) {
            //将基本WHERE子句添加到查询中    在“通过”模型中获得合格的外键
            $this->query->where($this->getQualifiedFirstKeyName(), '=', $localValue);
        }
    }

    /**
     * Set the join clause on the query.
     *
     * 在查询中设置连接子句
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return void
     */
    protected function performJoin(Builder $query = null)
    {
        $query = $query ?: $this->query;
        //           在“通过”模型中获得合格的外键
        $farKey = $this->getQualifiedFarKeyName();
        //向查询添加联接子句              获取与模型相关联的表         获得完全合格的父密钥名
        $query->join($this->throughParent->getTable(), $this->getQualifiedParentKeyName(), '=', $farKey);
        //确定“通过”关系的父类是否使用软删除
        if ($this->throughParentSoftDeletes()) {
            //      向查询添加“where null”子句          获得完全合格的父密钥名
            $query->whereNull($this->throughParent->getQualifiedDeletedAtColumn());
        }
    }

    /**
     * Determine whether "through" parent of the relation uses Soft Deletes.
     *
     * 确定“通过”关系的父类是否使用软删除
     *
     * @return bool
     */
    public function throughParentSoftDeletes()
    {
        //                                     返回类所使用的所有特性、子类和它们的特征
        return in_array(SoftDeletes::class, class_uses_recursive(
            get_class($this->throughParent)
        ));
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
        //在查询中添加“where in”子句
        $this->query->whereIn(
            //在“通过”模型中获得合格的外键                 获取一组模型的所有主键
            $this->getQualifiedFirstKeyName(), $this->getKeys($models, $this->localKey)
        );
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
            //在模型中设置特定关系                  创建一个新的Eloquent集合实例
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * 将贪婪的结果与他们的父母相匹配
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

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        //
        // 一旦我们有了字典，我们就可以简单地通过父模型把它们和孩子们联系起来，使用键控字典使匹配变得非常方便和简单
        // 然后我们就返回它们
        //
        foreach ($models as $model) {
            //                              获取模型主键的值
            if (isset($dictionary[$key = $model->getKey()])) {
                //在模型中设置特定关系
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
        $dictionary = [];

        // First we will create a dictionary of models keyed by the foreign key of the
        // relationship as this will allow us to quickly access all of the related
        // models without having to do nested looping which will be quite slow.
        //
        // 首先,我们将创建一个字典模型的外键的关系,因为这将使我们能够快速访问所有相关的模型,而不必做嵌套循环将非常缓慢
        //
        foreach ($results as $result) {
            $dictionary[$result->{$this->firstKey}][] = $result;
        }

        return $dictionary;
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
        //                将基本WHERE子句添加到查询中        执行查询和得到的第一个结果
        if (is_null($instance = $this->where($attributes)->first())) {
            //创建给定模型的新实例
            $instance = $this->related->newInstance($attributes);
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
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        // 获取与属性相关的第一个相关的模型记录，或者实例化它
        $instance = $this->firstOrNew($attributes);
        //用属性数组填充模型           将模型保存到数据库中
        $instance->fill($values)->save();

        return $instance;
    }

    /**
     * Execute the query and get the first related model.
     *
     * 执行查询并获得第一个相关模型
     *
     * @param  array   $columns
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        //      别名设置查询的“limit”值
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
        //                   执行查询并获得第一个相关模型
        if (! is_null($model = $this->first($columns))) {
            return $model;
        }
        //                             设置受影响的Eloquent型和实例ids
        throw (new ModelNotFoundException)->setModel(get_class($this->related));
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
        if (is_array($id)) {
            //通过主键找到多个相关的模型
            return $this->findMany($id, $columns);
        }
        //将基本WHERE子句添加到查询中
        return $this->where(
            //获取相关模型的关系        获取表格的键名
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
        if (empty($ids)) {
            //获取相关模型的关系             创建一个新的Eloquent集合实例
            return $this->getRelated()->newCollection();
        }
        //在查询中添加“在哪里”子句
        return $this->whereIn(
            //获取相关模型的关系        获取表格的键名
            $this->getRelated()->getQualifiedKeyName(), $ids
        )->get($columns);
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
        //通过它的主键找到相关的模型
        $result = $this->find($id, $columns);

        if (is_array($id)) {
            if (count($result) == count(array_unique($id))) {
                return $result;
            }
        } elseif (! is_null($result)) {
            return $result;
        }
        //                                 设置受影响的Eloquent型和实例ids
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
        //                          获取底层查询生成器实例
        $columns = $this->query->getQuery()->columns ? [] : $columns;
        //                     将作用域应用到Eloquent的生成器实例并返回它
        $builder = $this->query->applyScopes();
        //                   向查询中添加一个新的select列
        $models = $builder->addSelect(
            //为关系查询设置select子句
            $this->shouldSelect($columns)
        )->getModels();//得到不贪婪加载的水合模型

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
        //
        // 如果我们真的找到了模型，我们也会急切地加载任何被指定为需要被加载的关系
        // 这将为开发人员解决n+1的查询问题，并提高性能
        //
        if (count($models) > 0) {
            //                贪婪加载的关系模型
            $models = $builder->eagerLoadRelations($models);
        }
        //         创建一个新的Eloquent集合实例
        return $this->related->newCollection($models);
    }

    /**
     * Get a paginator for the "select" statement.
     *
     * 得到一个paginator“选择”的声明
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        //向查询中添加一个新的select列        为关系查询设置select子句
        $this->query->addSelect($this->shouldSelect($columns));
        //                   给定的查询分页
        return $this->query->paginate($perPage, $columns, $pageName, $page);
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
        //向查询中添加一个新的select列         为关系查询设置select子句
        $this->query->addSelect($this->shouldSelect($columns));
        //                   给定的查询通过简单的分页查询分页
        return $this->query->simplePaginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Set the select clause for the relation query.
     *
     * 为关系查询设置select子句
     *
     * @param  array  $columns
     * @return array
     */
    protected function shouldSelect(array $columns = ['*'])
    {
        if ($columns == ['*']) {
            //                   获取与模型相关联的表
            $columns = [$this->related->getTable().'.*'];
        }
        //                                  在“通过”模型中获得合格的外键
        return array_merge($columns, [$this->getQualifiedFirstKeyName()]);
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
        //在查询中设置连接子句
        $this->performJoin($query);
        //           设置要选择的列              添加一个“where”子句，将两个列与查询进行比较
        return $query->select($columns)->whereColumn(
            //获取在“has”查询中与父键进行比较的键                   在“通过”模型中获得合格的外键
            $this->getExistenceCompareKey(), '=', $this->getQualifiedFirstKeyName()
        );
    }

    /**
     * Get the key for comparing against the parent key in "has" query.
     *
     * 获取在“has”查询中与父键进行比较的键
     *
     * @return string
     */
    public function getExistenceCompareKey()
    {
        //                         获取表格的键名
        return $this->farParent->getQualifiedKeyName();
    }

    /**
     * Get the qualified foreign key on the related model.
     *
     * 在相关的模型中获得合格的外键
     *
     * @return string
     */
    public function getQualifiedFarKeyName()
    {
        //在相关的模型中获得合格的外键
        return $this->getQualifiedForeignKeyName();
    }

    /**
     * Get the qualified foreign key on the related model.
     *
     * 在相关的模型中获得合格的外键
     *
     * @return string
     */
    public function getQualifiedForeignKeyName()
    {
        //             获取与模型相关联的表
        return $this->related->getTable().'.'.$this->secondKey;
    }

    /**
     * Get the qualified foreign key on the "through" model.
     *
     * 在“通过”模型中获得合格的外键
     *
     * @return string
     */
    public function getQualifiedFirstKeyName()
    {
        //             获取与模型相关联的表
        return $this->throughParent->getTable().'.'.$this->firstKey;
    }
}
