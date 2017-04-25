<?php

namespace Illuminate\Database\Eloquent;

use Closure;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Pagination\Paginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @mixin \Illuminate\Database\Query\Builder
 */
class Builder
{
    use Concerns\QueriesRelationships;

    /**
     * The base query builder instance.
     *
     * 基础查询生成器实例
     *
     * @var \Illuminate\Database\Query\Builder
     */
    protected $query;

    /**
     * The model being queried.
     *
     * 正在查询的模型
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * The relationships that should be eager loaded.
     *
     * 贪婪加载的关系
     *
     * @var array
     */
    protected $eagerLoad = [];

    /**
     * All of the globally registered builder macros.
     *
     * 所有全局注册的生成器宏
     *
     * @var array
     */
    protected static $macros = [];

    /**
     * All of the locally registered builder macros.
     *
     * 所有本地注册的生成器宏
     *
     * @var array
     */
    protected $localMacros = [];

    /**
     * A replacement for the typical delete function.
     *
     * 一个典型的删除函数的替换
     *
     * @var \Closure
     */
    protected $onDelete;

    /**
     * The methods that should be returned from query builder.
     *
     * 应该从查询生成器返回的方法
     *
     * @var array
     */
    protected $passthru = [
        'insert', 'insertGetId', 'getBindings', 'toSql',
        'exists', 'count', 'min', 'max', 'avg', 'sum', 'getConnection',
    ];

    /**
     * Applied global scopes.
     *
     * 应用全局作用域
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * Removed global scopes.
     *
     * 移除全局作用域
     *
     * @var array
     */
    protected $removedScopes = [];

    /**
     * Create a new Eloquent query builder instance.
	 *
	 * 创建一个新的Eloquent查询生成器实例
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Register a new global scope.
     *
     * 注册一个新的全局范围
     *
     * @param  string  $identifier
     * @param  \Illuminate\Database\Eloquent\Scope|\Closure  $scope
     * @return $this
     */
    public function withGlobalScope($identifier, $scope)
    {
        $this->scopes[$identifier] = $scope;

        if (method_exists($scope, 'extend')) {
            $scope->extend($this);
        }

        return $this;
    }

    /**
     * Remove a registered global scope.
     *
     * 移除注册的全局作用域
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return $this
     */
    public function withoutGlobalScope($scope)
    {
        if (! is_string($scope)) {
            $scope = get_class($scope);
        }

        unset($this->scopes[$scope]);

        $this->removedScopes[] = $scope;

        return $this;
    }

    /**
     * Remove all or passed registered global scopes.
     *
     * 删除所有或经过注册的全局作用域
     *
     * @param  array|null  $scopes
     * @return $this
     */
    public function withoutGlobalScopes(array $scopes = null)
    {
        if (is_array($scopes)) {
            foreach ($scopes as $scope) {
                $this->withoutGlobalScope($scope);//移除注册的全局作用域
            }
        } else {
            $this->scopes = [];
        }

        return $this;
    }

    /**
     * Get an array of global scopes that were removed from the query.
     *
     * 获取从查询中移除的全局作用域数组
     *
     * @return array
     */
    public function removedScopes()
    {
        return $this->removedScopes;
    }

    /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * 如果给定的“值”为真，则应用回调的查询更改
     *
     * @param  bool  $value
     * @param  \Closure  $callback
     * @param  \Closure  $default
     * @return $this
     */
    public function when($value, $callback, $default = null)
    {
        $builder = $this;

        if ($value) {
            $builder = $callback($builder);
        } elseif ($default) {
            $builder = $default($builder);
        }

        return $builder;
    }

    /**
     * Add a where clause on the primary key to the query.
     *
     * 在查询的主键上添加WHERE子句
     *
     * @param  mixed  $id
     * @return $this
     */
    public function whereKey($id)
    {
        if (is_array($id) || $id instanceof Arrayable) {
            // 在查询中添加“where”子句           获取表格的键名
            $this->query->whereIn($this->model->getQualifiedKeyName(), $id);

            return $this;
        }
        //       将基本WHERE子句添加到查询中      获取表格的键名
        return $this->where($this->model->getQualifiedKeyName(), '=', $id);
    }

    /**
     * Add a basic where clause to the query.
     *
     * 将基本WHERE子句添加到查询中
     *
     * @param  string|\Closure  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column instanceof Closure) {
            $query = $this->model->newQueryWithoutScopes();//获取一个新的查询生成器，它没有任何全局作用域

            $column($query);
            //    将另一个查询生成器作为嵌套在查询生成器中(获取模型表的新查询生成器)
            $this->query->addNestedWhereQuery($query->getQuery(), $boolean);
        } else {
            $this->query->where(...func_get_args());//将基本WHERE子句添加到查询中
        }

        return $this;
    }

    /**
     * Add an "or where" clause to the query.
     *
     * 向查询添加“or where”子句
     *
     * @param  string|\Closure  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        //将基本WHERE子句添加到查询中
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Create a collection of models from plain arrays.
	 *
	 * 从普通数组创建模型集合
     *
     * @param  array  $items
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function hydrate(array $items)
    {
        $instance = $this->model->newInstance();//创建给定模型的新实例
		//       创建一个新的Eloquent集合实例
        return $instance->newCollection(array_map(function ($item) use ($instance) {
            return $instance->newFromBuilder($item); //创建一个新的模型实例
        }, $items));
    }

    /**
     * Create a collection of models from a raw query.
     *
     * 创建一个模型连接从原始查询
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function fromQuery($query, $bindings = [])
    {
        $instance = $this->model->newInstance();//创建给定模型的新实例

        return $this->hydrate(//从普通数组创建模型集合
            //获取模型的数据库连接->对数据库运行SELECT语句
            $instance->getConnection()->select($query, $bindings)
        );
    }

    /**
     * Find a model by its primary key.
     *
     * 通过主键找到模型
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        if (is_array($id)) {
            return $this->findMany($id, $columns); //通过主键找到多个模型
        }
        //    在查询的主键上添加WHERE子句->执行查询和得到的第一个结果
        return $this->whereKey($id)->first($columns);
    }

    /**
     * Find multiple models by their primary keys.
     *
     * 通过主键找到多个模型
     *
     * @param  array  $ids
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findMany($ids, $columns = ['*'])
    {
        if (empty($ids)) {
            return $this->model->newCollection();//创建一个新的Eloquent集合实例
        }
        //    在查询的主键上添加WHERE子句->将查询执行为“SELECT”语句
        return $this->whereKey($ids)->get($columns);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * 通过主键找到模型或抛出异常
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, $columns = ['*'])
    {
        $result = $this->find($id, $columns);//通过主键找到模型

        if (is_array($id)) {
            if (count($result) == count(array_unique($id))) {
                return $result;
            }
        } elseif (! is_null($result)) {
            return $result;
        }

        throw (new ModelNotFoundException)->setModel(//设置受影响的Eloquent型和实例ids
            get_class($this->model), $id
        );
    }

    /**
     * Find a model by its primary key or return fresh model instance.
     *
     * 通过主键或返回新模型实例找到模型
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function findOrNew($id, $columns = ['*'])
    {
        //                       通过主键找到模型
        if (! is_null($model = $this->find($id, $columns))) {
            return $model;
        }
        //             创建给定模型的新实例->设置与模型相关联的连接
        return $this->model->newInstance()->setConnection(
            $this->query->getConnection()->getName()//获取数据库链接实例->获取数据库连接名
        );
    }

    /**
     * Get the first record matching the attributes or instantiate it.
     *
     * 获取与属性匹配的第一个记录或实例化它
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrNew(array $attributes, array $values = [])
    {
        //                       将基本WHERE子句添加到查询中->执行查询和得到的第一个结果
        if (! is_null($instance = $this->where($attributes)->first())) {
            return $instance;
        }
        //             创建给定模型的新实例->设置与模型相关联的连接
        return $this->model->newInstance($attributes + $values)->setConnection(
            $this->query->getConnection()->getName()//获取数据库链接实例->获取数据库连接名
        );
    }

    /**
     * Get the first record matching the attributes or create it.
     *
     * 获取与属性匹配的第一个记录或创建它
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrCreate(array $attributes, array $values = [])
    {
        //                       将基本WHERE子句添加到查询中->执行查询和得到的第一个结果
        if (! is_null($instance = $this->where($attributes)->first())) {
            return $instance;
        }
        //             创建给定模型的新实例->设置与模型相关联的连接
        $instance = $this->model->newInstance($attributes + $values)->setConnection(
            $this->query->getConnection()->getName()//获取数据库链接实例->获取数据库连接名
        );

        $instance->save(); //将模型保存到数据库中

        return $instance;
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * 创建或更新与属性匹配的记录，并将其填充值
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        //用给定的值调用给定的闭包，然后返回值(获取与属性匹配的第一个记录或实例化它,)
        return tap($this->firstOrNew($attributes), function ($instance) use ($values) {
            $instance->fill($values)->save();//用属性数组填充模型->将模型保存到数据库中
        });
    }

    /**
     * Execute the query and get the first result.
     *
     * 执行查询和得到的第一个结果
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static|null
     */
    public function first($columns = ['*'])
    {
        //别名设置查询的“limit”值->将查询执行为“SELECT”语句->执行查询和得到的第一个结果
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * 执行查询并得到第一个结果或抛出异常
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function firstOrFail($columns = ['*'])
    {
        //                           执行查询和得到的第一个结果
        if (! is_null($model = $this->first($columns))) {
            return $model;
        }
        //                                    设置受影响的Eloquent型和实例ids
        throw (new ModelNotFoundException)->setModel(get_class($this->model));
    }

    /**
     * Execute the query and get the first result or call a callback.
     *
     * 执行查询并获得第一个结果或调用回调
     *
     * @param  \Closure|array  $columns
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Model|static|mixed
     */
    public function firstOr($columns = ['*'], Closure $callback = null)
    {
        if ($columns instanceof Closure) {
            $callback = $columns;

            $columns = ['*'];
        }

        if (! is_null($model = $this->first($columns))) { //执行查询和得到的第一个结果
            return $model;
        }

        return call_user_func($callback);
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * 从查询的第一个结果中获取单个列的值
     *
     * @param  string  $column
     * @return mixed
     */
    public function value($column)
    {
        //                  执行查询和得到的第一个结果
        if ($result = $this->first([$column])) {
            return $result->{$column};
        }
    }

    /**
     * Execute the query as a "select" statement.
	 *
	 * 将查询执行为“SELECT”语句
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function get($columns = ['*'])
    {
        $builder = $this->applyScopes();//将作用域应用到Eloquent的生成器实例并返回它

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded, which will solve the
        // n+1 query issue for the developers to avoid running a lot of queries.
        //
        // 如果我们真的发现模型，我们也将急于加载任何指定的关系，需要贪婪加载，这将解决N + 1查询问题的开发人员，以避免运行大量的查询
		//
		//                     获取模型类实例的集合
        if (count($models = $builder->getModels($columns)) > 0) {
            $models = $builder->eagerLoadRelations($models);//贪婪加载的关系模型
        }
        //     获取被查询的模型实例->创建一个新的Eloquent集合实例
        return $builder->getModel()->newCollection($models);
    }

    /**
     * Get the hydrated models without eager loading.
	 *
	 * 得到不贪婪加载的水合模型
	 * * 获取模型类实例的集合
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model[]
     */
    public function getModels($columns = ['*'])
    {
		//从普通数组创建模型集合
        return $this->model->hydrate(
            $this->query->get($columns)->all(),//将查询执行为“SELECT”语句->获取集合中的所有项目()
            $this->model->getConnectionName()//获取模型的当前连接名称
        )->all();//获取集合中的所有项目
    }

    /**
     * Eager load the relationships for the models.
     *
     * 贪婪加载的关系模型
     *
     * @param  array  $models
     * @return array
     */
    public function eagerLoadRelations(array $models)
    {
        foreach ($this->eagerLoad as $name => $constraints) {
            // For nested eager loads we'll skip loading them here and they will be set as an
            // eager load on the query to retrieve the relation so that they will be eager
            // loaded on that query, because that is where they get hydrated as models.
            //
            // 对于嵌套的贪婪加载，我们将跳过加载它们在这里，他们将被设置为一个贪婪加载查询，以检索关系，使他们将贪婪加载在该查询，因为这是他们得到水合模型
            //
            if (strpos($name, '.') === false) {
                $models = $this->eagerLoadRelation($models, $name, $constraints);//在一组模型上的贪婪加载关系
            }
        }

        return $models;
    }

    /**
     * Eagerly load the relationship on a set of models.
     *
     * 在一组模型上的贪婪加载关系
     *
     * @param  array  $models
     * @param  string  $name
     * @param  \Closure  $constraints
     * @return array
     */
    protected function eagerLoadRelation(array $models, $name, Closure $constraints)
    {
        // First we will "back up" the existing where conditions on the query so we can
        // add our eager constraints. Then we will merge the wheres that were on the
        // query back to it in order that any where conditions might be specified.
        //
        // 首先，我们将“备份”现有的查询条件，以便我们可以添加我们的贪婪约束
        // 然后我们将在哪里，在查询要回它的任何有条件的地方可以指定
        //
        //                获取给定关系名的关系实例
        $relation = $this->getRelation($name);

        $relation->addEagerConstraints($models);//为关系的贪婪加载设置约束

        $constraints($relation);

        // Once we have the results, we just match those back up to their parent models
        // using the relationship instance. Then we just return the finished arrays
        // of models which have been eagerly hydrated and are readied for return.
        //
        // 一旦我们得到了结果，我们只使用关系实例将这些备份与父模型匹配
        // 然后我们就回到了阵列已经急切地水合，准备回归模型
        //
        return $relation->match( //将贪婪加载的结果与父类相匹配
            $relation->initRelation($models, $name),//在一组模型上初始化关系
            $relation->getEager(), $name //得到贪婪加载的关系
        );
    }

    /**
     * Get the relation instance for the given relation name.
     *
     * 获取给定关系名的关系实例
     *
     * @param  string  $name
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function getRelation($name)
    {
        // We want to run a relationship query without any constrains so that we will
        // not have to remove these where clauses manually which gets really hacky
        // and error prone. We don't want constraints because we add eager ones.
        //
        // 我们要运行一个关系查询没有任何约束，我们将不删除这些条款，很容易在手动和容易出错的
        // 我们不需要约束，因为我们添加了渴望的约束
        //
        $relation = Relation::noConstraints(function () use ($name) {//在关系上禁用约束的回调
            try {
                return $this->getModel()->{$name}();//获取被查询的模型实例
            } catch (BadMethodCallException $e) {
                //                    创建一个新的异常实例(获取被查询的模型实例)
                throw RelationNotFoundException::make($this->getModel(), $name);
            }
        });

        $nested = $this->relationsNestedUnder($name); //为给定的顶层关系获取深度嵌套关系

        // If there are nested relationships set on the query, we will put those onto
        // the query instances so that they can be handled after this relationship
        // is loaded. In this way they will all trickle down as they are loaded.
        //
        // 如果在查询中设置了嵌套关系，我们将将这些关系放到查询实例上，以便在关系加载后处理这些关系
        // 这样，他们都会滴下来，因为他们加载
        //
        if (count($nested) > 0) {
            //获取关系的基础查询->设置应该加载的关系
            $relation->getQuery()->with($nested);
        }

        return $relation;
    }

    /**
     * Get the deeply nested relations for a given top-level relation.
     *
     * 为给定的顶层关系获取深度嵌套关系
     *
     * @param  string  $relation
     * @return array
     */
    protected function relationsNestedUnder($relation)
    {
        $nested = [];

        // We are basically looking for any relationships that are nested deeper than
        // the given top-level relationship. We will just check for any relations
        // that start with the given top relations and adds them to our arrays.
        //
        // 我们基本上是在寻找比给定顶层关系嵌套更深的关系
        // 我们将只检查从给定的顶级关系开始的任何关系，并将它们添加到我们的数组中
        //
        foreach ($this->eagerLoad as $name => $constraints) {
            if ($this->isNestedUnder($relation, $name)) {//确定关系是否嵌套
                $nested[substr($name, strlen($relation.'.'))] = $constraints;
            }
        }

        return $nested;
    }

    /**
     * Determine if the relationship is nested.
     *
     * 确定关系是否嵌套
     *
     * @param  string  $relation
     * @param  string  $name
     * @return bool
     */
    protected function isNestedUnder($relation, $name)
    {
        //   确定一个给定的字符串包含另一个字符串     确定给定的子字符串是否属于给定的字符串
        return Str::contains($name, '.') && Str::startsWith($name, $relation.'.');
    }

    /**
     * Get a generator for the given query.
     *
     * 获取给定查询的生成器
     *
     * @return \Generator
     */
    public function cursor()
    {
        //将作用域应用到Eloquent的生成器实例并返回它->query->获取给定查询的生成器
        foreach ($this->applyScopes()->query->cursor() as $record) {
            yield $this->model->newFromBuilder($record);//创建一个新的模型实例
        }
    }

    /**
     * Chunk the results of the query.
     *
     * 查询的结果块
     *
     * @param  int  $count
     * @param  callable  $callback
     * @return bool
     */
    public function chunk($count, callable $callback)
    {
        $this->enforceOrderBy();//如果查询没有排序语句则添加一个通用的“order by”子句

        $page = 1;

        do {
            // We'll execute the query for the given page and get the results. If there are
            // no results we can just break and return from here. When there are results
            // we will call the callback with the current chunk of these results here.
            //
            // 我们将执行给定页面的查询并得到结果
            // 如果没有结果，我们就可以在这里休息和返回
            // 当有结果时，我们将用这些结果的当前块调用回调函数
            //
            //设置给定页的限制和偏移量->将查询执行为“SELECT”语句
            $results = $this->forPage($page, $count)->get();

            $countResults = $results->count();//计数集合中的项目数

            if ($countResults == 0) {
                break;
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            //
            // 在每个块结果集上，我们将将它们传递给回调函数，然后让开发人员处理回调过程中的所有内容，这使我们能够保持低内存，以便通过大的结果集进行工作
            //
            if ($callback($results) === false) {
                return false;
            }

            $page++;
        } while ($countResults == $count);

        return true;
    }

    /**
     * Chunk the results of a query by comparing numeric IDs.
     *
     * 通过比较数值IDs来查询结果块
     *
     * @param  int  $count
     * @param  callable  $callback
     * @param  string  $column
     * @param  string|null  $alias
     * @return bool
     */
    public function chunkById($count, callable $callback, $column = null, $alias = null)
    {
        //                             获取被查询的模型实例->从模型中获取主键
        $column = is_null($column) ? $this->getModel()->getKeyName() : $column;

        $alias = is_null($alias) ? $column : $alias;

        $lastId = 0;

        do {
            $clone = clone $this;

            // We'll execute the query for the given page and get the results. If there are
            // no results we can just break and return from here. When there are results
            // we will call the callback with the current chunk of these results here.
            //
            // 我们将执行给定页面的查询并得到结果
            // 如果没有结果，我们就可以在这里休息和返回
            // 当有结果时，我们将用这些结果的当前块调用回调函数
            //
            //           将查询限制到给定ID后的结果的下一个“页面” ->将查询执行为“SELECT”语句
            $results = $clone->forPageAfterId($count, $lastId, $column)->get();

            $countResults = $results->count();//计数集合中的项目数

            if ($countResults == 0) {
                break;
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            //
            // 在每个块结果集上，我们将将它们传递给回调函数，然后让开发人员处理回调过程中的所有内容，这使我们能够保持低内存，以便通过大的结果集进行工作
            //
            if ($callback($results) === false) {
                return false;
            }
            //           从集合中获取最后一个项
            $lastId = $results->last()->{$alias};
        } while ($countResults == $count);

        return true;
    }

    /**
     * Add a generic "order by" clause if the query doesn't already have one.
     *
     * 如果查询没有排序语句则添加一个通用的“order by”子句
     *
     * @return void
     */
    protected function enforceOrderBy()
    {
        if (empty($this->query->orders) && empty($this->query->unionOrders)) {
            //将“订单”子句添加到查询中(获取表格的键名,)
            $this->orderBy($this->model->getQualifiedKeyName(), 'asc');
        }
    }

    /**
     * Execute a callback over each item while chunking.
     *
     * 分块执行一个回调在每个项目
     *
     * @param  callable  $callback
     * @param  int  $count
     * @return bool
     */
    public function each(callable $callback, $count = 1000)
    {
        //       查询的结果块
        return $this->chunk($count, function ($results) use ($callback) {
            foreach ($results as $key => $value) {
                if ($callback($value, $key) === false) {
                    return false;
                }
            }
        });
    }

    /**
     * Get an array with the values of a given column.
     *
     * 用给定列的值获取数组
     *
     * @param  string  $column
     * @param  string|null  $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null)
    {
        //           获取基础查询生成器实例->用给定列的值获取数组
        $results = $this->toBase()->pluck($column, $key);

        // If the model has a mutator for the requested column, we will spin through
        // the results and mutate the values so that the mutated version of these
        // columns are returned as you would expect from these Eloquent models.
        //
        // 如果模型有一个要求的列的突变，我们将通过旋转的结果和突变值使突变版本的这些列如你期望从这些功能模式返回
        //
        if (! $this->model->hasGetMutator($column) && //确定是否得到一个属性赋值的存在
            ! $this->model->hasCast($column) &&//确定属性是否应被转换为本机类型
            ! in_array($column, $this->model->getDates())) {//获取应该转换为日期的属性
            return $results;
        }

        return $results->map(function ($value) use ($column) {//在每个项目上运行map
            return $this->model->newFromBuilder([$column => $value])->{$column};//创建一个新的模型实例
        });
    }

    /**
     * Paginate the given query.
     *
     * 给定的查询分页
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);//解析当前页或返回默认值

        $perPage = $perPage ?: $this->model->getPerPage();//获取每个页面返回的模型数
        //           获取基础查询生成器实例->得到的分页程序的总记录数
        $results = ($total = $this->toBase()->getCountForPagination())
                                    ? $this->forPage($page, $perPage)->get($columns)//设置给定页的限制和偏移量->将查询执行为“SELECT”语句
                                    : $this->model->newCollection();//创建一个新的Eloquent集合实例
        //创建一个新的页面实例
        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(), //解决当前请求路径或返回默认值
            'pageName' => $pageName,
        ]);
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * 给定的查询通过简单的分页查询分页
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);//解析当前页或返回默认值

        $perPage = $perPage ?: $this->model->getPerPage();//获取每个页面返回的模型数

        // Next we will set the limit and offset for this query so that when we get the
        // results we get the proper section of results. Then, we'll create the full
        // paginator instances for these results with the given page and per page.
        //
        // 接下来，我们将设置这个查询的限制和偏移，这样当我们得到的结果，我们得到适当的部分的结果
        // 然后，我们将创建这些结果与给定的页面，每页的页码的情况下全
        //
        //  别名设置查询的“偏移”值              别名设置查询的“limit”值
        $this->skip(($page - 1) * $perPage)->take($perPage + 1);
        //创建一个新的页面实例(将查询执行为“SELECT”语句,)
        return new Paginator($this->get($columns), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),//解决当前请求路径或返回默认值
            'pageName' => $pageName,
        ]);
    }

    /**
     * Save a new model and return the instance.
     *
     * 保存新模型并返回实例
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $attributes = [])
    {
        //               创建给定模型的新实例->设置与模型相关联的连接
        $instance = $this->model->newInstance($attributes)->setConnection(
            $this->query->getConnection()->getName()//获取数据库链接实例->获取数据库连接名
        );

        $instance->save();//将模型保存到数据库中

        return $instance;
    }

    /**
     * Save a new model and return the instance. Allow mass-assignment.
     *
     * 保存新模型并返回实例。允许量分配
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function forceCreate(array $attributes)
    {
        //               创建给定模型的新实例->设置与模型相关联的连接
        $instance = $this->model->newInstance()->setConnection(
            $this->query->getConnection()->getName()//获取数据库链接实例->获取数据库连接名
        );
        //         当无守护时运行给定调用
        return $this->model->unguarded(function () use ($attributes, $instance) {
            return $instance->create($attributes);//保存新模型并返回实例
        });
    }

    /**
     * Update a record in the database.
     *
     * 更新数据库中的记录
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        //获取基础查询生成器实例->更新数据库中的记录(将“updated at”列添加到值数组)
        return $this->toBase()->update($this->addUpdatedAtColumn($values));
    }

    /**
     * Increment a column's value by a given amount.
     *
     * 按给定值递增列的值
     *
     * @param  string  $column
     * @param  int  $amount
     * @param  array  $extra
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        //获取基础查询生成器实例->按给定值递增列的值(将“updated at”列添加到值数组)
        return $this->toBase()->increment(
            $column, $amount, $this->addUpdatedAtColumn($extra)
        );
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * 按给定数量递减列的值
     *
     * @param  string  $column
     * @param  int  $amount
     * @param  array  $extra
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        //获取基础查询生成器实例->按给定数量递减列的值(将“updated at”列添加到值数组)
        return $this->toBase()->decrement(
            $column, $amount, $this->addUpdatedAtColumn($extra)
        );
    }

    /**
     * Add the "updated at" column to an array of values.
     *
     * 将“updated at”列添加到值数组
     *
     * @param  array  $values
     * @return array
     */
    protected function addUpdatedAtColumn(array $values)
    {
        if (! $this->model->usesTimestamps()) {//确定模型使用时间戳
            return $values;
        }

        return Arr::add(//如果不存在，使用“点”表示法将一个元素添加到数组中
            $values, $this->model->getUpdatedAtColumn(),//获取“updated at”列的名称
            $this->model->freshTimestampString()//为模型获取新的时间戳
        );
    }

    /**
     * Delete a record from the database.
     *
     * 从数据库中删除一个记录
     *
     * @return mixed
     */
    public function delete()
    {
        if (isset($this->onDelete)) {
            return call_user_func($this->onDelete, $this);
        }
        //获取基础查询生成器实例->从数据库中删除记录
        return $this->toBase()->delete();
    }

    /**
     * Run the default delete function on the builder.
     *
     * 在生成器上运行默认删除函数
     *
     * Since we do not apply scopes here, the row will actually be deleted.
     *
     * @return mixed
     */
    public function forceDelete()
    {
        return $this->query->delete();//从数据库中删除记录
    }

    /**
     * Register a replacement for the default delete function.
     *
     * 注册一个替换默认删除函数
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function onDelete(Closure $callback)
    {
        $this->onDelete = $callback;
    }

    /**
     * Call the given local model scopes.
     *
     * 调用给定的局部模型域
     *
     * @param  array  $scopes
     * @return mixed
     */
    public function scopes(array $scopes)
    {
        $builder = $this;

        foreach ($scopes as $scope => $parameters) {
            // If the scope key is an integer, then the scope was passed as the value and
            // the parameter list is empty, so we will format the scope name and these
            // parameters here. Then, we'll be ready to call the scope on the model.
            //
            // 如果作用域键是整数，则作用域被传递为值，参数列表为空，所以我们将在这里格式化作用域名称和这些参数
            // 然后，我们将准备调用模型上的作用域
            //
            if (is_int($scope)) {
                list($scope, $parameters) = [$parameters, []];
            }

            // Next we'll pass the scope callback to the callScope method which will take
            // care of groping the "wheres" correctly so the logical order doesn't get
            // messed up when adding scopes. Then we'll return back out the builder.
            //
            // 接下来我们将通过范围回调到callScope方法将照顾摸索“wheres”正确的逻辑顺序没有搞砸了，当添加范围。然后我们将返回建设者
            //
            $builder = $builder->callScope(//将给定的范围应用于当前生成器实例
                [$this->model, 'scope'.ucfirst($scope)],
                (array) $parameters
            );
        }

        return $builder;
    }

    /**
     * Apply the scopes to the Eloquent builder instance and return it.
     *
     * 将作用域应用到Eloquent的生成器实例并返回它
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function applyScopes()
    {
        if (! $this->scopes) {
            return $this;
        }

        $builder = clone $this;

        foreach ($this->scopes as $scope) {
            ////将给定的范围应用于当前生成器实例
            $builder->callScope(function (Builder $builder) use ($scope) {
                // If the scope is a Closure we will just go ahead and call the scope with the
                // builder instance. The "callScope" method will properly group the clauses
                // that are added to this query so "where" clauses maintain proper logic.
                //
                // 如果作用域是闭包，我们将继续使用生成器实例调用作用域
                // “callScope”方法将正常组添加到该查询，“where”条款保持适当的逻辑的条款
                //
                if ($scope instanceof Closure) {
                    $scope($builder);
                }

                // If the scope is a scope object, we will call the apply method on this scope
                // passing in the builder and the model instance. After we run all of these
                // scopes we will return back the builder instance to the outside caller.
                //
                // 如果作用域是一个作用域对象，我们将调用在生成器和模型实例中传递的作用域方法
                // 在运行所有这些作用域之后，我们将将生成器实例返回给外部调用方
                //
                if ($scope instanceof Scope) {
                    //将范围应用于给定的Eloquent的查询生成器(,获取被查询的模型实例)
                    $scope->apply($builder, $this->getModel());
                }
            });
        }

        return $builder;
    }

    /**
     * Apply the given scope on the current builder instance.
     *
     * 将给定的范围应用于当前生成器实例
     *
     * @param  callable $scope
     * @param  array $parameters
     * @return mixed
     */
    protected function callScope(callable $scope, $parameters = [])
    {
        array_unshift($parameters, $this);

        $query = $this->getQuery();//获取底层查询生成器实例

        // We will keep track of how many wheres are on the query before running the
        // scope so that we can properly group the added scope constraints in the
        // query as their own isolated nested where statement and avoid issues.
        //
        // 我们将跟踪有多少而在查询之前的运行范围，使我们能够正确组添加范围约束在查询自己的孤立的嵌套语句和避免的问题
        //
        $originalWhereCount = count($query->wheres);

        $result = $scope(...array_values($parameters)) ?: $this;

        if (count($query->wheres) > $originalWhereCount) {
            $this->addNewWheresWithinGroup($query, $originalWhereCount);//嵌套条件，将它们按给定的位置切片
        }

        return $result;
    }

    /**
     * Nest where conditions by slicing them at the given where count.
     *
     * 嵌套条件，将它们按给定的位置切片
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $originalWhereCount
     * @return void
     */
    protected function addNewWheresWithinGroup(QueryBuilder $query, $originalWhereCount)
    {
        // Here, we totally remove all of the where clauses since we are going to
        // rebuild them as nested queries by slicing the groups of wheres into
        // their own sections. This is to prevent any confusing logic order.
        //
        // 在这里，我们完全删除所有的where子句，因为我们要重建他们的嵌套查询的切片在组织把自己的部分
        // 这是为了防止任何混乱的逻辑顺序
        //
        $allWheres = $query->wheres;

        $query->wheres = [];

        $this->groupWhereSliceForScope(//在给定偏移量的条件下，将它们添加到查询作为嵌套条件
            $query, array_slice($allWheres, 0, $originalWhereCount)
        );

        $this->groupWhereSliceForScope(//在给定偏移量的条件下，将它们添加到查询作为嵌套条件
            $query, array_slice($allWheres, $originalWhereCount)
        );
    }

    /**
     * Slice where conditions at the given offset and add them to the query as a nested condition.
     *
     * 在给定偏移量的条件下，将它们添加到查询作为嵌套条件
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $whereSlice
     * @return void
     */
    protected function groupWhereSliceForScope(QueryBuilder $query, $whereSlice)
    {
        $whereBooleans = collect($whereSlice)->pluck('boolean');// 获取给定键的值

        // Here we'll check if the given subset of where clauses contains any "or"
        // booleans and in this case create a nested where expression. That way
        // we don't add any unnecessary nesting thus keeping the query clean.
        //
        // 在这里，我们将检查给定的子集的WHERE子句中包含任何”或“布尔值，在这种情况下，创建一个嵌套的表达
        // 这样我们就不会添加任何不必要的嵌套从而保持查询的干净
        //
        //  确定集合中是否存在项
        if ($whereBooleans->contains('or')) {
            $query->wheres[] = $this->createNestedWhere(//创建一个具有嵌套条件的数组
                $whereSlice, $whereBooleans->first()//从集合中获取第一项
            );
        } else {
            $query->wheres = array_merge($query->wheres, $whereSlice);
        }
    }

    /**
     * Create a where array with nested where conditions.
     *
     * 创建一个具有嵌套条件的数组
     *
     * @param  array  $whereSlice
     * @param  string  $boolean
     * @return array
     */
    protected function createNestedWhere($whereSlice, $boolean = 'and')
    {
        //获取底层查询生成器实例->为嵌套的条件创建新的查询实例
        $whereGroup = $this->getQuery()->forNestedWhere();

        $whereGroup->wheres = $whereSlice;

        return ['type' => 'Nested', 'query' => $whereGroup, 'boolean' => $boolean];
    }

    /**
     * Set the relationships that should be eager loaded.
     *
     * 设置应该加载的关系
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function with($relations)
    {
        //              将关系列表解析为个体
        $eagerLoad = $this->parseWithRelations(is_string($relations) ? func_get_args() : $relations);

        $this->eagerLoad = array_merge($this->eagerLoad, $eagerLoad);

        return $this;
    }

    /**
     * Prevent the specified relations from being eager loaded.
     *
     * 防止指定的关系被贪婪加载
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function without($relations)
    {
        $this->eagerLoad = array_diff_key($this->eagerLoad, array_flip(
            is_string($relations) ? func_get_args() : $relations
        ));

        return $this;
    }

    /**
     * Parse a list of relations into individuals.
     *
     * 将关系列表解析为个体
     *
     * @param  array  $relations
     * @return array
     */
    protected function parseWithRelations(array $relations)
    {
        $results = [];

        foreach ($relations as $name => $constraints) {
            // If the "relation" value is actually a numeric key, we can assume that no
            // constraints have been specified for the eager load and we'll just put
            // an empty Closure with the loader so that we can treat all the same.
            //
            // 如果“关系”值实际上是一个数字键，我们可以假设没有指定约束的贪婪加载，我们只会把空闭包与加载程序，以便我们可以对待所有相同的
            //
            if (is_numeric($name)) {
                $name = $constraints;

                list($name, $constraints) = Str::contains($name, ':')//确定一个给定的字符串包含另一个字符串
                            ? $this->createSelectWithConstraint($name)//创建一个约束以选择关系的给定列
                            : [$name, function () {
                                //
                            }];
            }

            // We need to separate out any nested includes. Which allows the developers
            // to load deep relationships using "dots" without stating each level of
            // the relationship with its own key in the array of eager load names.
            //
            // 我们需要分离出任何嵌套的包含
            // 它允许开发人员使用“点”加载深度关系，而无需说明每个级别的关系与自己的键在数组中的贪婪加载名称
            //
            $results = $this->addNestedWiths($name, $results);//解析关系中的嵌套关系

            $results[$name] = $constraints;
        }

        return $results;
    }

    /**
     * Create a constraint to select the given columns for the relation.
     *
     * 创建一个约束以选择关系的给定列
     *
     * @param  string  $name
     * @return array
     */
    protected function createSelectWithConstraint($name)
    {
        return [explode(':', $name)[0], function ($query) use ($name) {
            $query->select(explode(',', explode(':', $name)[1]));//对数据库运行SELECT语句
        }];
    }

    /**
     * Parse the nested relationships in a relation.
     *
     * 解析关系中的嵌套关系
     *
     * @param  string  $name
     * @param  array   $results
     * @return array
     */
    protected function addNestedWiths($name, $results)
    {
        $progress = [];

        // If the relation has already been set on the result array, we will not set it
        // again, since that would override any constraints that were already placed
        // on the relationships. We will only set the ones that are not specified.
        //
        // 如果关系已经设置在结果数组上，我们将不再设置它，因为这将覆盖已经放置在关系上的任何约束
        // 我们只会设置那些没有指定
        //
        foreach (explode('.', $name) as $segment) {
            $progress[] = $segment;

            if (! isset($results[$last = implode('.', $progress)])) {
                $results[$last] = function () {
                    //
                };
            }
        }

        return $results;
    }

    /**
     * Get the underlying query builder instance.
     *
     * 获取底层查询生成器实例
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set the underlying query builder instance.
     *
     * 设置底层查询生成器实例
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get a base query builder instance.
     *
     * 获取基础查询生成器实例
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function toBase()
    {
        //        将作用域应用到Eloquent的生成器实例并返回它->获取底层查询生成器实例
        return $this->applyScopes()->getQuery();
    }

    /**
     * Get the relationships being eagerly loaded.
     *
     * 得到的关系的贪婪加载
     *
     * @return array
     */
    public function getEagerLoads()
    {
        return $this->eagerLoad;
    }

    /**
     * Set the relationships being eagerly loaded.
     *
     * 设置贪婪加载的关系
     *
     * @param  array  $eagerLoad
     * @return $this
     */
    public function setEagerLoads(array $eagerLoad)
    {
        $this->eagerLoad = $eagerLoad;

        return $this;
    }

    /**
     * Get the model instance being queried.
     *
     * 获取被查询的模型实例
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set a model instance for the model being queried.
	 *
	 * 为被查询的模型设置模型实例
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
		//           设置查询对象的表(获取与模型相关联的表)
        $this->query->from($model->getTable());

        return $this;
    }

    /**
     * Get the given macro by name.
     *
     * 通过名称获取宏
     *
     * @param  string  $name
     * @return \Closure
     */
    public function getMacro($name)
    {
        return Arr::get($this->localMacros, $name);//使用“点”符号从数组中获取一个项
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * 动态处理到查询实例中的调用
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($method === 'macro') {
            $this->localMacros[$parameters[0]] = $parameters[1];

            return;
        }

        if (isset($this->localMacros[$method])) {
            array_unshift($parameters, $this);

            return $this->localMacros[$method](...$parameters);
        }

        if (isset(static::$macros[$method]) and static::$macros[$method] instanceof Closure) {
            return call_user_func_array(static::$macros[$method]->bindTo($this, static::class), $parameters);
        }

        if (isset(static::$macros[$method])) {
            return call_user_func_array(static::$macros[$method]->bindTo($this, static::class), $parameters);
        }

        if (method_exists($this->model, $scope = 'scope'.ucfirst($method))) {
            return $this->callScope([$this->model, $scope], $parameters);//将给定的范围应用于当前生成器实例
        }

        if (in_array($method, $this->passthru)) {
            return $this->toBase()->{$method}(...$parameters);//获取基础查询生成器实例
        }

        $this->query->{$method}(...$parameters);

        return $this;
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * 动态处理到查询实例中的调用
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $parameters)
    {
        if ($method === 'macro') {
            static::$macros[$parameters[0]] = $parameters[1];

            return;
        }

        if (! isset(static::$macros[$method])) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        if (static::$macros[$method] instanceof Closure) {
            return call_user_func_array(Closure::bind(static::$macros[$method], null, static::class), $parameters);
        }

        return call_user_func_array(static::$macros[$method], $parameters);
    }

    /**
     * Force a clone of the underlying query builder when cloning.
     *
     * 克隆时强制基础查询生成器的克隆
     *
     * @return void
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }
}
