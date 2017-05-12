<?php

namespace Illuminate\Database\Eloquent\Relations;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MorphTo extends BelongsTo
{
    /**
     * The type of the polymorphic relation.
     *
     * 多态关系的类型
     *
     * @var string
     */
    protected $morphType;

    /**
     * The models whose relations are being eager loaded.
     *
     * 那些关系热切的模型
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $models;

    /**
     * All of the models keyed by ID.
     *
     * 所有以ID为键的模型
     *
     * @var array
     */
    protected $dictionary = [];

    /**
     * A buffer of dynamic calls to query macros.
     *
     * 用于查询宏的动态调用的缓冲区
     *
     * @var array
     */
    protected $macroBuffer = [];

    /**
     * Create a new morph to relationship instance.
     *
     * 创建一个新的关系实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $type
     * @param  string  $relation
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation)
    {
        $this->morphType = $type;
        //创建一个新的属于关系实例
        parent::__construct($query, $parent, $foreignKey, $ownerKey, $relation);
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
        //用模型构建一个字典                   创建一个新的集合实例，如果该值不是一个准备好的
        $this->buildDictionary($this->models = Collection::make($models));
    }

    /**
     * Build a dictionary with the models.
     *
     * 用模型构建一个字典
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    protected function buildDictionary(Collection $models)
    {
        foreach ($models as $model) {
            if ($model->{$this->morphType}) {
                $this->dictionary[$model->{$this->morphType}][$model->{$this->foreignKey}][] = $model;
            }
        }
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
        //                               执行查询和得到的第一个结果
        return $this->ownerKey ? $this->query->first() : null;
    }

    /**
     * Get the results of the relationship.
     *
     * 得到关系的结果
     *
     * Called via eager load method of Eloquent query builder.
     *
     * 通过Eloquent的查询构建器来调用
     *
     * @return mixed
     */
    public function getEager()
    {
        foreach (array_keys($this->dictionary) as $type) {
            //将给定类型的结果与他们的父母匹配           获取类型的所有关系结果
            $this->matchToMorphParents($type, $this->getResultsByType($type));
        }

        return $this->models;
    }

    /**
     * Get all of the relation results for a type.
     *
     * 获取类型的所有关系结果
     *
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getResultsByType($type)
    {
        //               按类型创建一个新的模型实例
        $instance = $this->createModelByType($type);
        //重放存储在实际相关实例上的宏调用          获取模型表的新查询生成器
        $query = $this->replayMacros($instance->newQuery())
            //将约束从另一个查询合并到当前查询
                            ->mergeConstraintsFrom($this->getQuery())
            //设置应该加载的关系(获取关系的基础查询->得到的关系的贪婪加载)
                            ->with($this->getQuery()->getEagerLoads());
        //在查询中添加“where in”子句
        return $query->whereIn(
            //获取与模型相关联的表              从模型中获取主键              收集给定类型的所有外键
            $instance->getTable().'.'.$instance->getKeyName(), $this->gatherKeysByType($type)
        )->get();//将查询执行为“SELECT”语句
    }

    /**
     * Gather all of the foreign keys for a given type.
     *
     * 收集给定类型的所有外键
     *
     * @param  string  $type
     * @return array
     */
    protected function gatherKeysByType($type)
    {
        //                                     在每个项目上运行map
        return collect($this->dictionary[$type])->map(function ($models) {
            return head($models)->{$this->foreignKey};
        })->values()->unique()->all();//重置基础阵列上的键->只返回集合数组中的唯一项->获取集合中的所有项目
    }

    /**
     * Create a new model instance by type.
     *
     * 按类型创建一个新的模型实例
     *
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModelByType($type)
    {
        //              检索给定的变形类的实际类名
        $class = Model::getActualClassNameForMorph($type);

        return new $class;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * 将贪婪加载的结果与他们的父母相匹配
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        return $models;
    }

    /**
     * Match the results for a given type to their parents.
     *
     * 将给定类型的结果与他们的父母匹配
     *
     * @param  string  $type
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @return void
     */
    protected function matchToMorphParents($type, Collection $results)
    {
        foreach ($results as $result) {
            if (isset($this->dictionary[$type][$result->getKey()])) {
                foreach ($this->dictionary[$type][$result->getKey()] as $model) {
                    //在模型中设置特定关系
                    $model->setRelation($this->relation, $result);
                }
            }
        }
    }

    /**
     * Associate the model instance to the given parent.
     *
     * 将模型实例与给定的父节点关联
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associate($model)
    {
        //在模型上设置给定属性                            获取模型主键的值
        $this->parent->setAttribute($this->foreignKey, $model->getKey());
        //                                                  获取多态关系的类名
        $this->parent->setAttribute($this->morphType, $model->getMorphClass());
        //在模型中设置特定关系
        return $this->parent->setRelation($this->relation, $model);
    }

    /**
     * Dissociate previously associated model from the given parent.
     *
     * 将之前关联的模型与给定的父节点分离
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function dissociate()
    {
        //在模型上设置给定属性
        $this->parent->setAttribute($this->foreignKey, null);

        $this->parent->setAttribute($this->morphType, null);
        //在模型中设置特定关系
        return $this->parent->setRelation($this->relation, null);
    }

    /**
     * Get the foreign key "type" name.
     *
     * 获取外键“类型”的名称
     *
     * @return string
     */
    public function getMorphType()
    {
        return $this->morphType;
    }

    /**
     * Get the dictionary used by the relationship.
     *
     * 获取关系使用的字典
     *
     * @return array
     */
    public function getDictionary()
    {
        return $this->dictionary;
    }

    /**
     * Replay stored macro calls on the actual related instance.
     *
     * 重放存储在实际相关实例上的宏调用
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function replayMacros(Builder $query)
    {
        foreach ($this->macroBuffer as $macro) {
            $query->{$macro['method']}(...$macro['parameters']);
        }

        return $query;
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
        try {
            //处理动态方法调用关系
            return parent::__call($method, $parameters);
        }

        // If we tried to call a method that does not exist on the parent Builder instance,
        // we'll assume that we want to call a query macro (e.g. withTrashed) that only
        // exists on related models. We will just store the call and replay it later.
            //
            // 如果我们试图调用一个方法,不存在父施工实例,我们假设我们想打电话查询宏(例如withTrashed)只存在于相关的模型
            // 我们将只存储该调用并在稍后重放它
            //
        catch (BadMethodCallException $e) {
            $this->macroBuffer[] = compact('method', 'parameters');

            return $this;
        }
    }
}
