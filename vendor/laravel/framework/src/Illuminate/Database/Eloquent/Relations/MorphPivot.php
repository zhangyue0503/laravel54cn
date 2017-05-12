<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;

class MorphPivot extends Pivot
{
    /**
     * The type of the polymorphic relation.
     *
     * 多态关系的类型
     *
     * Explicitly define this so it's not included in saved attributes.
     *
     * 明确地定义它，所以它不包含在保存的属性中
     *
     * @var string
     */
    protected $morphType;

    /**
     * The value of the polymorphic relation.
     *
     * 多态关系的值
     *
     * Explicitly define this so it's not included in saved attributes.
     *
     * 明确地定义它，所以它不包含在保存的属性中
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Set the keys for a save update query.
     *
     * 设置保存更新查询的键
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        //将基本WHERE子句添加到查询中
        $query->where($this->morphType, $this->morphClass);
        //        设置保存更新查询的键
        return parent::setKeysForSaveQuery($query);
    }

    /**
     * Delete the pivot model record from the database.
     *
     * 从数据库中删除主模型记录
     *
     * @return int
     */
    public function delete()
    {
        //获取关于轴心的delete操作的查询构建器
        $query = $this->getDeleteQuery();
        //将基本WHERE子句添加到查询中
        $query->where($this->morphType, $this->morphClass);
        //从数据库中删除一个记录
        return $query->delete();
    }

    /**
     * Set the morph type for the pivot.
     *
     * 为轴心设置变形类型
     *
     * @param  string  $morphType
     * @return $this
     */
    public function setMorphType($morphType)
    {
        $this->morphType = $morphType;

        return $this;
    }

    /**
     * Set the morph class for the pivot.
     *
     * 为轴心设置变形类
     *
     * @param  string  $morphClass
     * @return \Illuminate\Database\Eloquent\Relations\MorphPivot
     */
    public function setMorphClass($morphClass)
    {
        $this->morphClass = $morphClass;

        return $this;
    }
}
