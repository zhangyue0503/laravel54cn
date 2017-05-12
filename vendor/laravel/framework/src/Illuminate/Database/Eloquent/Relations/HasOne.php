<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class HasOne extends HasOneOrMany
{
    /**
     * Indicates if a default model instance should be used.
     *
     * 指示是否应该使用默认的模型实例
     *
     * Alternatively, may be a Closure or array.
     *
     * 或者，可能是一个闭包或数组
     *
     * @var \Closure|array|bool
     */
    protected $withDefault;

    /**
     * Get the results of the relationship.
     *
     * 得到关系的结果
     *
     * @return mixed
     */
    public function getResults()
    {
        //             执行查询和得到的第一个结果        获取该关系的默认值
        return $this->query->first() ?: $this->getDefaultFor($this->parent);
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
            //在模型中设置特定关系             获取该关系的默认值
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    /**
     * Get the default value for this relation.
     *
     * 获取该关系的默认值
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getDefaultFor(Model $model)
    {
        if (! $this->withDefault) {
            return;
        }
        //                    创建给定模型的新实例        在模型上设置给定属性
        $instance = $this->related->newInstance()->setAttribute(
            //获得普通的外键                      从模型中获得一个属性
            $this->getForeignKeyName(), $model->getAttribute($this->localKey)
        );

        if (is_callable($this->withDefault)) {
            return call_user_func($this->withDefault, $instance) ?: $instance;
        }

        if (is_array($this->withDefault)) {
            //用属性数组填充模型。从批量赋值
            $instance->forceFill($this->withDefault);
        }

        return $instance;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * 将贪婪的结果与他们的父母相匹配
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        //将贪婪的结果与他们的单亲父母相匹配
        return $this->matchOne($models, $results, $relation);
    }

    /**
     * Return a new model instance in case the relationship does not exist.
     *
     * 返回一个新的模型实例，以防止关系不存在
     *
     * @param  \Closure|array|bool  $callback
     * @return $this
     */
    public function withDefault($callback = true)
    {
        $this->withDefault = $callback;

        return $this;
    }
}
