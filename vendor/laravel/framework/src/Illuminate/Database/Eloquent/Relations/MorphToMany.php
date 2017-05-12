<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class MorphToMany extends BelongsToMany
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
     * The class name of the morph type constraint.
     *
     * 变形类型约束的类名
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Indicates if we are connecting the inverse of the relation.
     *
     * 如果我们把关系的逆联系起来
     *
     * This primarily affects the morphClass constraint.
     *
     * @var bool
     */
    protected $inverse;

    /**
     * Create a new morph to many relationship instance.
     *
     * 创建一个新的多态到多关系实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $relatedKey
     * @param  string  $relationName
     * @param  bool  $inverse
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $name, $table, $foreignKey, $relatedKey, $relationName = null, $inverse = false)
    {
        $this->inverse = $inverse;
        $this->morphType = $name.'_type';
        //                              获取被查询的模型实例    获取多态关系的类名           获取多态关系的类名
        $this->morphClass = $inverse ? $query->getModel()->getMorphClass() : $parent->getMorphClass();
        //创建一个新的属于许多关系实例
        parent::__construct($query, $parent, $table, $foreignKey, $relatedKey, $relationName);
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
        //为关系查询设置where子句
        parent::addWhereConstraints();
        //将基本WHERE子句添加到查询中
        $this->query->where($this->table.'.'.$this->morphType, $this->morphClass);

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
        //为关系的贪婪加载设置约束条件
        parent::addEagerConstraints($models);
        //将基本WHERE子句添加到查询中
        $this->query->where($this->table.'.'.$this->morphType, $this->morphClass);
    }

    /**
     * Create a new pivot attachment record.
     *
     * 创建一个新的主连接记录
     *
     * @param  int   $id
     * @param  bool  $timed
     * @return array
     */
    protected function baseAttachRecord($id, $timed)
    {
        //如果不存在，使用“点”表示法将一个元素添加到数组中
        return Arr::add(
            //创建一个新的主连接记录
            parent::baseAttachRecord($id, $timed), $this->morphType, $this->morphClass
        );
    }

    /**
     * Add the constraints for a relationship count query.
     *
     * 添加关系计数查询的约束
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        //为关系查询添加约束                                                          将基本WHERE子句添加到查询中
        return parent::getRelationExistenceQuery($query, $parentQuery, $columns)->where(
            $this->table.'.'.$this->morphType, $this->morphClass
        );
    }

    /**
     * Create a new query builder for the pivot table.
     *
     * 为pivot表创建一个新的查询构建器
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newPivotQuery()
    {
        //为pivot表创建一个新的查询构建器    将基本WHERE子句添加到查询中
        return parent::newPivotQuery()->where($this->morphType, $this->morphClass);
    }

    /**
     * Create a new pivot model instance.
     *
     * 创建一个新的轴心模型实例
     *
     * @param  array  $attributes
     * @param  bool   $exists
     * @return \Illuminate\Database\Eloquent\Relations\Pivot
     */
    public function newPivot(array $attributes = [], $exists = false)
    {
        $using = $this->using;

        $pivot = $using ? $using::fromRawAttributes($this->parent, $attributes, $this->table, $exists)
                        : new MorphPivot($this->parent, $attributes, $this->table, $exists);

        $pivot->setPivotKeys($this->foreignKey, $this->relatedKey)//为pivot模型实例设置关键名称
              ->setMorphType($this->morphType)//为轴心设置变形类型
              ->setMorphClass($this->morphClass);//为轴心设置变形类

        return $pivot;
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
     * Get the class name of the parent model.
     *
     * 获取父模型的类名
     *
     * @return string
     */
    public function getMorphClass()
    {
        return $this->morphClass;
    }
}
