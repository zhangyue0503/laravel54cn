<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;

class MorphOne extends MorphOneOrMany
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
        //             执行查询和得到的第一个结果
        return $this->query->first();
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
            //在模型中设置特定关系
            $model->setRelation($relation, null);
        }

        return $models;
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
        //将贪婪的结果与他们的单亲父母相匹配
        return $this->matchOne($models, $results, $relation);
    }
}
