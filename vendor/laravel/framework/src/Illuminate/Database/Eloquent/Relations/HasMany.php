<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;

class HasMany extends HasOneOrMany
{
    /**
     * Get the results of the relationship.
     *
     * 得到关系的结果
     *
     * @return mixed
     */
    public function getResults()
    {
        //                将查询执行为“SELECT”语句
        return $this->query->get();
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
            //在模型中设置特定关系                           创建一个新的Eloquent集合实例
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
        //将贪婪的结果与他们的父母相匹配
        return $this->matchMany($models, $results, $relation);
    }
}
