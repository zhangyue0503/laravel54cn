<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BelongsTo extends Relation
{
    /**
     * The child model instance of the relation.
     * 关系的子模型实例
     */
    protected $child;

    /**
     * The foreign key of the parent model.
     *
     * 父模型的外键
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The associated key on the parent model.
     *
     * 父模型上的相关键
     *
     * @var string
     */
    protected $ownerKey;

    /**
     * The name of the relationship.
     *
     * 关系的名字
     *
     * @var string
     */
    protected $relation;

    /**
     * The count of self joins.
     *
     * 自连接数
     *
     * @var int
     */
    protected static $selfJoinCount = 0;

    /**
     * Create a new belongs to relationship instance.
     *
     * 创建一个新的属于关系实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $child
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $relation
     * @return void
     */
    public function __construct(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        $this->ownerKey = $ownerKey;
        $this->relation = $relation;
        $this->foreignKey = $foreignKey;

        // In the underlying base relationship class, this variable is referred to as
        // the "parent" since most relationships are not inversed. But, since this
        // one is we will create a "child" variable for much better readability.
        //
        // 在底层基本关系类,这个变量被称为“父”,因为大多数的关系并不使倒转
        // 但是，由于这个原因，我们将创建一个“子”变量，以获得更好的可读性
        //
        $this->child = $child;
        //创建一个新的关系实例
        parent::__construct($query, $child);
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
        //执行查询和得到的第一个结果
        return $this->query->first();
    }

    /**
     * Set the base constraints on the relation query.
     * 在关系查询中设置基本约束
     *
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            // For belongs to relationships, which are essentially the inverse of has one
            // or has many relationships, we need to actually query on the primary key
            // of the related models matching on the foreign key that's on a parent.
            //
            // 属于关系,本质上是有一个或有许多的逆关系,我们需要查询相关模型匹配的主键外键的父母
            //
            //                    获取与模型相关联的表
            $table = $this->related->getTable();
            //将基本WHERE子句添加到查询中
            $this->query->where($table.'.'.$this->ownerKey, '=', $this->child->{$this->foreignKey});
        }
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
        // We'll grab the primary key name of the related models since it could be set to
        // a non-standard name and not "id". We will then construct the constraint for
        // our eagerly loading query so it returns the proper models from execution.
        //
        // 我们将抓住的主键名称相关的模型,因为它可以被设置为一个非标准的名字,而不是“id”
        // 然后，我们将为我们热切加载的查询构造约束，以便它从执行中返回正确的模型
        //
        //             获取与模型相关联的表
        $key = $this->related->getTable().'.'.$this->ownerKey;
        //在查询中添加“where in”子句(,从一系列相关的模型中收集关键字)
        $this->query->whereIn($key, $this->getEagerModelKeys($models));
    }

    /**
     * Gather the keys from an array of related models.
     *
     * 从一系列相关的模型中收集关键字
     *
     * @param  array  $models
     * @return array
     */
    protected function getEagerModelKeys(array $models)
    {
        $keys = [];

        // First we need to gather all of the keys from the parent models so we know what
        // to query for via the eager loading query. We will add them to an array then
        // execute a "where in" statement to gather up all of those related records.
        //
        // 首先，我们需要从父模型中收集所有的键，这样我们就知道了通过贪婪加载查询来查询什么
        // 我们将把它们添加到一个数组中，然后执行一个“where”语句来收集所有相关记录
        //
        foreach ($models as $model) {
            if (! is_null($value = $model->{$this->foreignKey})) {
                $keys[] = $value;
            }
        }

        // If there are no keys that were not null we will just return an array with either
        // null or 0 in (depending on if incrementing keys are in use) so the query wont
        // fail plus returns zero results, which should be what the developer expects.
        //
        // 如果没有键没有空我们只会返回一个数组与零或0(取决于如果使用递增键时)查询不会失败加上返回零结果,这应该是开发人员期望
        //
        if (count($keys) === 0) {
            //确定相关的模型有一个自动递增的ID
            return [$this->relationHasIncrementingId() ? 0 : null];
        }

        sort($keys);

        return array_values(array_unique($keys));
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
     * 将贪婪的结果与他们的父母相匹配
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $foreign = $this->foreignKey;

        $owner = $this->ownerKey;

        // First we will get to build a dictionary of the child models by their primary
        // key of the relationship, then we can easily match the children back onto
        // the parents using that dictionary and the primary key of the children.
        //
        // 首先我们会建立一个字典孩子的模型关系的主键,那么我们可以很容易地匹配的孩子回到父母使用词典和主键的孩子
        //
        $dictionary = [];

        foreach ($results as $result) {
            //                  从模型中获取属性
            $dictionary[$result->getAttribute($owner)] = $result;
        }

        // Once we have the dictionary constructed, we can loop through all the parents
        // and match back onto their children using these keys of the dictionary and
        // the primary key of the children to map them onto the correct instances.
        //
        // 一旦我们有词典,我们可以遍历所有父母和匹配回到他们的孩子使用这些键的字典和孩子的主键映射到正确的实例
        //
        foreach ($models as $model) {
            if (isset($dictionary[$model->{$foreign}])) {
                //在模型中设置特定关系
                $model->setRelation($relation, $dictionary[$model->{$foreign}]);
            }
        }

        return $models;
    }

    /**
     * Update the parent model on the relationship.
     *
     * 更新关于关系的父模型
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function update(array $attributes)
    {
        //得到关系的结果->用属性数组填充模型->将模型保存到数据库中
        return $this->getResults()->fill($attributes)->save();
    }

    /**
     * Associate the model instance to the given parent.
     *
     * 将模型实例与给定的父节点关联
     *
     * @param  \Illuminate\Database\Eloquent\Model|int  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associate($model)
    {
        //                                    从模型中获得一个属性
        $ownerKey = $model instanceof Model ? $model->getAttribute($this->ownerKey) : $model;
        //在模型上设置给定属性
        $this->child->setAttribute($this->foreignKey, $ownerKey);

        if ($model instanceof Model) {
            //在模型中设置特定关系
            $this->child->setRelation($this->relation, $model);
        }

        return $this->child;
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
        $this->child->setAttribute($this->foreignKey, null);
        //在模型中设置特定关系
        return $this->child->setRelation($this->relation, null);
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
        //获取底层查询生成器实例
        if ($parentQuery->getQuery()->from == $query->getQuery()->from) {
            //在相同的表中添加关系查询的约束
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }
        //设置要选择的列->向查询中添加“where”子句比较两列
        return $query->select($columns)->whereColumn(
            //获得这段关系的完全合格的外键                   获取被查询的模型实例->获取与模型相关联的表
            $this->getQualifiedForeignKey(), '=', $query->getModel()->getTable().'.'.$this->ownerKey
        );
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
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        //设置要选择的列->设置查询对象的表
        $query->select($columns)->from(
            //获取被查询的模型实例->获取与模型相关联的表                   获取一个关系连接表散列
            $query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash()
        );
        //获取被查询的模型实例->设置与模型相关联的表
        $query->getModel()->setTable($hash);
        //向查询中添加“where”子句比较两列
        return $query->whereColumn(
            //            获取被查询的模型实例->从模型中获取主键           获得这段关系的完全合格的外键
            $hash.'.'.$query->getModel()->getKeyName(), '=', $this->getQualifiedForeignKey()
        );
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
     * Determine if the related model has an auto-incrementing ID.
     *
     * 确定相关的模型有一个自动递增的ID
     *
     * @return bool
     */
    protected function relationHasIncrementingId()
    {
        //                得到的值指示标识递增
        return $this->related->getIncrementing() &&
            //                         实现自动递增键类型
                                $this->related->getKeyType() === 'int';
    }

    /**
     * Get the foreign key of the relationship.
     *
     * 获取关系的外键
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the fully qualified foreign key of the relationship.
     *
     * 获得这段关系的完全合格的外键
     *
     * @return string
     */
    public function getQualifiedForeignKey()
    {
        //获取与模型相关联的表
        return $this->child->getTable().'.'.$this->foreignKey;
    }

    /**
     * Get the associated key of the relationship.
     *
     * 获取相关的关系关键
     *
     * @return string
     */
    public function getOwnerKey()
    {
        return $this->ownerKey;
    }

    /**
     * Get the fully qualified associated key of the relationship.
     *
     * 获得与此相关的完全限定的关联密钥
     *
     * @return string
     */
    public function getQualifiedOwnerKeyName()
    {
        //获取与模型相关联的表
        return $this->related->getTable().'.'.$this->ownerKey;
    }

    /**
     * Get the name of the relationship.
     *
     * 获得关系的名称
     *
     * @return string
     */
    public function getRelation()
    {
        return $this->relation;
    }
}
