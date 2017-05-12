<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Pivot extends Model
{
    /**
     * The parent model of the relationship.
     *
     * 关系的父模型
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $parent;

    /**
     * The name of the foreign key column.
     *
     * 外键列的名称
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The name of the "other key" column.
     *
     * “其他键”列的名称
     *
     * @var string
     */
    protected $relatedKey;

    /**
     * The attributes that aren't mass assignable.
     *
     * 这些属性不是可分配的
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Create a new pivot model instance.
     *
     * 创建一个新的枢纽模型实例
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  array   $attributes
     * @param  string  $table
     * @param  bool    $exists
     * @return void
     */
    public function __construct(Model $parent, $attributes, $table, $exists = false)
    {
        //创建一个新的Eloquent模型实例
        parent::__construct();

        // The pivot model is a "dynamic" model since we will set the tables dynamically
        // for the instance. This allows it work for any intermediate tables for the
        // many to many relationship that are defined by this developer's classes.
        //
        // pivot模型是一个“动态”模型，因为我们将动态地为实例设置表
        // 这允许它在任何中间表中为许多由开发人员的类定义的关系而工作
        //
        //   设置与模型相关联的连接          获取模型的当前连接名称
        $this->setConnection($parent->getConnectionName())
             ->setTable($table)//设置与模型相关联的表
             ->forceFill($attributes)//用属性数组填充模型。从批量赋值
             ->syncOriginal();//当前模型同步原始属性

        // We store off the parent instance so we will access the timestamp column names
        // for the model, since the pivot model timestamps aren't easily configurable
        // from the developer's point of view. We can use the parents to get these.
        //
        // 我们存储父实例，这样我们就可以访问模型的时间戳列名称，因为从开发人员的角度来看，轴心模型时间戳是不容易配置的
        // 我们可以用父母来得到这些
        //
        $this->parent = $parent;

        $this->exists = $exists;
        //                      确定主模型是否具有时间戳属性
        $this->timestamps = $this->hasTimestampAttributes();
    }

    /**
     * Create a new pivot model from raw values returned from a query.
     *
     * 从查询返回的原始值创建新的枢纽模型
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  array   $attributes
     * @param  string  $table
     * @param  bool    $exists
     * @return static
     */
    public static function fromRawAttributes(Model $parent, $attributes, $table, $exists = false)
    {
        $instance = new static($parent, $attributes, $table, $exists);
        //设置模型属性的数组
        $instance->setRawAttributes($attributes, true);

        return $instance;
    }

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
        //将基本WHERE子句添加到查询中                从模型中获取属性
        $query->where($this->foreignKey, $this->getAttribute($this->foreignKey));

        return $query->where($this->relatedKey, $this->getAttribute($this->relatedKey));
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
        //获取关于轴心的delete操作的查询构建器->从数据库中删除一个记录
        return $this->getDeleteQuery()->delete();
    }

    /**
     * Get the query builder for a delete operation on the pivot.
     *
     * 获取关于轴心的delete操作的查询构建器
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getDeleteQuery()
    {
        //获取模型表的新查询生成器->将基本WHERE子句添加到查询中
        return $this->newQuery()->where([
            //                          从模型中获取属性
            $this->foreignKey => $this->getAttribute($this->foreignKey),
            $this->relatedKey => $this->getAttribute($this->relatedKey),
        ]);
    }

    /**
     * Get the foreign key column name.
     *
     * 获取外键列名称
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the "related key" column name.
     *
     * 获取“相关键”列名称
     *
     * @return string
     */
    public function getRelatedKey()
    {
        return $this->relatedKey;
    }

    /**
     * Get the "related key" column name.
     *
     * 获取“相关键”列名称
     *
     * @return string
     */
    public function getOtherKey()
    {
        //获取“相关键”列名称
        return $this->getRelatedKey();
    }

    /**
     * Set the key names for the pivot model instance.
     *
     * 为pivot模型实例设置关键名称
     *
     * @param  string  $foreignKey
     * @param  string  $relatedKey
     * @return $this
     */
    public function setPivotKeys($foreignKey, $relatedKey)
    {
        $this->foreignKey = $foreignKey;

        $this->relatedKey = $relatedKey;

        return $this;
    }

    /**
     * Determine if the pivot model has timestamp attributes.
     *
     * 确定主模型是否具有时间戳属性
     *
     * @return bool
     */
    public function hasTimestampAttributes()
    {
        //                     获取“创建at”列的名称
        return array_key_exists($this->getCreatedAtColumn(), $this->attributes);
    }

    /**
     * Get the name of the "created at" column.
     *
     * 获取“创建at”列的名称
     *
     * @return string
     */
    public function getCreatedAtColumn()
    {
        //                   获取“创建at”列的名称
        return $this->parent->getCreatedAtColumn();
    }

    /**
     * Get the name of the "updated at" column.
     *
     * 获取“更新at”列的名称
     *
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        //                  获取“更新at”列的名称
        return $this->parent->getUpdatedAtColumn();
    }
}
