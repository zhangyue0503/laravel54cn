<?php

namespace Illuminate\Database\Eloquent\Relations;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;

abstract class Relation
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The Eloquent query builder instance.
     *
     * Eloquent的查询构建器实例
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * The parent model instance.
     *
     * 父模型实例
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $parent;

    /**
     * The related model instance.
     *
     * 相关的模型实例
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $related;

    /**
     * Indicates if the relation is adding constraints.
     *
     * 表示关系是否添加约束
     *
     * @var bool
     */
    protected static $constraints = true;

    /**
     * An array to map class names to their morph names in database.
     *
     * 将类名映射到数据库中的类名的数组
     *
     * @var array
     */
    protected static $morphMap = [];

    /**
     * Create a new relation instance.
     *
     * 创建一个新的关系实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return void
     */
    public function __construct(Builder $query, Model $parent)
    {
        $this->query = $query;
        $this->parent = $parent;
        $this->related = $query->getModel();//获取被查询的模型实例
        //在关系查询中设置基本约束
        $this->addConstraints();
    }

    /**
     * Run a callback with constraints disabled on the relation.
     *
     * 在关系上禁用约束的回调
     *
     * @param  \Closure  $callback
     * @return mixed
     */
    public static function noConstraints(Closure $callback)
    {
        $previous = static::$constraints;

        static::$constraints = false;

        // When resetting the relation where clause, we want to shift the first element
        // off of the bindings, leaving only the constraints that the developers put
        // as "extra" on the relationships, and not original relation constraints.
        //
        // 重置where子句的关系时,我们要把第一个元素的绑定,只留下的限制开发人员把“额外的”关系,而不是原来的约束关系
        //
        try {
            return call_user_func($callback);
        } finally {
            static::$constraints = $previous;
        }
    }

    /**
     * Set the base constraints on the relation query.
     *
     * 在关系查询中设置基本约束
     *
     * @return void
     */
    abstract public function addConstraints();

    /**
     * Set the constraints for an eager load of the relation.
     *
     * 为关系的贪婪加载设置约束
     *
     * @param  array  $models
     * @return void
     */
    abstract public function addEagerConstraints(array $models);

    /**
     * Initialize the relation on a set of models.
     *
     * 在一组模型上初始化关系
     *
     * @param  array   $models
     * @param  string  $relation
     * @return array
     */
    abstract public function initRelation(array $models, $relation);

    /**
     * Match the eagerly loaded results to their parents.
     *
     * 将贪婪加载的结果与父类相匹配
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    abstract public function match(array $models, Collection $results, $relation);

    /**
     * Get the results of the relationship.
     *
     * 得到关系的结果
     *
     * @return mixed
     */
    abstract public function getResults();

    /**
     * Get the relationship for eager loading.
     *
     * 得到贪婪加载的关系
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEager()
    {
        return $this->get();
    }

    /**
     * Touch all of the related models for the relationship.
     *
     * 联系所有相关的关系模型
     *
     * @return void
     */
    public function touch()
    {
        //        获取相关模型的关系->获取“更新at”列的名称
        $column = $this->getRelated()->getUpdatedAtColumn();
        //对基本查询运行一个原始更新                             为模型获取一个新的时间戳
        $this->rawUpdate([$column => $this->getRelated()->freshTimestampString()]);
    }

    /**
     * Run a raw update against the base query.
     *
     * 对基本查询运行一个原始更新
     *
     * @param  array  $attributes
     * @return int
     */
    public function rawUpdate(array $attributes = [])
    {
        //                更新数据库中的记录
        return $this->query->update($attributes);
    }

    /**
     * Add the constraints for a relationship count query.
     *
     * 为关系计数查询添加约束
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceCountQuery(Builder $query, Builder $parentQuery)
    {
        //为内部关系存在查询添加约束
        return $this->getRelationExistenceQuery(
            $query, $parentQuery, new Expression('count(*)')
        );
    }

    /**
     * Add the constraints for an internal relationship existence query.
     *
     * 为内部关系存在查询添加约束
     *
     * Essentially, these queries compare on column names like whereColumn.
     *
     * 从本质上讲，这些查询比较喜欢whereColumn列名
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        //设置要选择的列->向查询中添加“where”子句比较两列
        return $query->select($columns)->whereColumn(
            //获得完全合格的父密钥名                             获取在“已”查询中与父键进行比较的键
            $this->getQualifiedParentKeyName(), '=', $this->getExistenceCompareKey()
        );
    }

    /**
     * Get all of the primary keys for an array of models.
     *
     * 获取一组模型的所有主键
     *
     * @param  array   $models
     * @param  string  $key
     * @return array
     */
    protected function getKeys(array $models, $key = null)
    {
        //                       在每个项目上运行map
        return collect($models)->map(function ($value) use ($key) {
            return $key ? $value->getAttribute($key) : $value->getKey();
            //重置基础阵列上的键->只返回集合数组中的唯一项->通过回调来对每个项目进行排序->获取集合中的所有项目
        })->values()->unique()->sort()->all();
    }

    /**
     * Get the underlying query for the relation.
     *
     * 获取关系的基础查询
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get the base query builder driving the Eloquent builder.
     *
     * 让基础查询构建器驱动Eloquent的构建器
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getBaseQuery()
    {
        return $this->query->getQuery();//获取底层查询生成器实例
    }

    /**
     * Get the parent model of the relation.
     *
     * 获得关系的父模型
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get the fully qualified parent key name.
     *
     * 获得完全合格的父密钥名
     *
     * @return string
     */
    public function getQualifiedParentKeyName()
    {
        //                      获取表格的键名
        return $this->parent->getQualifiedKeyName();
    }

    /**
     * Get the related model of the relation.
     *
     * 获取相关模型的关系
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getRelated()
    {
        return $this->related;
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
        //                     获取“创建at”列的名称
        return $this->parent->getCreatedAtColumn();
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
        //                   获取“更新at”列的名称
        return $this->parent->getUpdatedAtColumn();
    }

    /**
     * Get the name of the related model's "updated at" column.
     *
     * 获取相关模型的“更新at”列的名称
     *
     * @return string
     */
    public function relatedUpdatedAt()
    {
        //                   获取“更新at”列的名称
        return $this->related->getUpdatedAtColumn();
    }

    /**
     * Set or get the morph map for polymorphic relations.
     *
     * 设置或获取多态关系的变形图
     *
     * @param  array|null  $map
     * @param  bool  $merge
     * @return array
     */
    public static function morphMap(array $map = null, $merge = true)
    {
        //              从模型类名构建一个表键数组
        $map = static::buildMorphMapFromModels($map);

        if (is_array($map)) {
            static::$morphMap = $merge && static::$morphMap
                            ? array_merge(static::$morphMap, $map) : $map;
        }

        return static::$morphMap;
    }

    /**
     * Builds a table-keyed array from model class names.
     *
     * 从模型类名构建一个表键数组
     *
     * @param  string[]|null  $models
     * @return array|null
     */
    protected static function buildMorphMapFromModels(array $models = null)
    {
        //                          确定数组是否为关联
        if (is_null($models) || Arr::isAssoc($models)) {
            return $models;
        }

        return array_combine(array_map(function ($model) {
            return (new $model)->getTable();
        }, $models), $models);
    }

    /**
     * Handle dynamic method calls to the relationship.
     *
     * 处理动态方法调用关系
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        //检查宏是否已注册
        if (static::hasMacro($method)) {
            //          动态调用类的调用
            return $this->macroCall($method, $parameters);
        }

        $result = call_user_func_array([$this->query, $method], $parameters);

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }

    /**
     * Force a clone of the underlying query builder when cloning.
     *
     * 在克隆时强制执行底层查询构建器的克隆
     *
     * @return void
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }
}
