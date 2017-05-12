<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class MorphOneOrMany extends HasOneOrMany
{
    /**
     * The foreign key type for the relationship.
     *
     * 这种关系的外键类型
     *
     * @var string
     */
    protected $morphType;

    /**
     * The class name of the parent model.
     *
     * 父模型的类名
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Create a new morph one or many relationship instance.
     *
     * 创建一个新的变形一个或多个关系实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $localKey)
    {
        $this->morphType = $type;
        //                            获取多态关系的类名
        $this->morphClass = $parent->getMorphClass();
        //创建一个新的有一个或多个关系实例
        parent::__construct($query, $parent, $id, $localKey);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * 在关系查询中设置基本约束
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            //在关系查询中设置基本约束
            parent::addConstraints();
            //将基本WHERE子句添加到查询中
            $this->query->where($this->morphType, $this->morphClass);
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
        //为关系的贪婪加载设置约束条件
        parent::addEagerConstraints($models);
        //将基本WHERE子句添加到查询中
        $this->query->where($this->morphType, $this->morphClass);
    }

    /**
     * Find a related model by its primary key or return new instance of the related model.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function findOrNew($id, $columns = ['*'])
    {
        if (is_null($instance = $this->find($id, $columns))) {
            //                         创建给定模型的新实例
            $instance = $this->related->newInstance();

            // When saving a polymorphic relationship, we need to set not only the foreign
            // key, but also the foreign key type, which is typically the class name of
            // the parent model. This makes the polymorphic item unique in the table.
            //
            // 在保存多态关系时，我们不仅需要设置外键，还需要设置外键类型，这通常是父模型的类名
            // 这使得多态条目在表中是惟一的
            //
            //       设置用于创建相关模型的外ID和类型
            $this->setForeignAttributesForCreate($instance);
        }

        return $instance;
    }

    /**
     * Get the first related model record matching the attributes or instantiate it.
     *
     * 获取与属性相关的第一个相关的模型记录，或者实例化它
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrNew(array $attributes)
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            //                         创建给定模型的新实例
            $instance = $this->related->newInstance($attributes);

            // When saving a polymorphic relationship, we need to set not only the foreign
            // key, but also the foreign key type, which is typically the class name of
            // the parent model. This makes the polymorphic item unique in the table.
            //
            // 在保存多态关系时，我们不仅需要设置外键，还需要设置外键类型，这通常是父模型的类名
            // 这使得多态条目在表中是惟一的
            //
            //         设置用于创建相关模型的外ID和类型
            $this->setForeignAttributesForCreate($instance);
        }

        return $instance;
    }

    /**
     * Get the first related record matching the attributes or create it.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrCreate(array $attributes)
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            //创建相关模型的新实例
            $instance = $this->create($attributes);
        }

        return $instance;
    }

    /**
     * Create or update a related record matching the attributes, and fill it with values.
     *
     * 创建或更新与属性匹配的相关记录，并将其填充为值
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        //用给定的值调用给定的闭包，然后返回值(获取与属性相关的第一个相关的模型记录，或者实例化它
        return tap($this->firstOrNew($attributes), function ($instance) use ($values) {
            $instance->fill($values);

            $instance->save();
        });
    }

    /**
     * Attach a model instance to the parent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function save(Model $model)
    {
        //在模型上设置给定属性(获得无表的纯变体类型名称
        $model->setAttribute($this->getMorphType(), $this->morphClass);
        //将模型实例附加到父模型
        return parent::save($model);
    }

    /**
     * Create a new instance of the related model.
     *
     * 创建相关模型的新实例
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $attributes)
    {
        //                     创建给定模型的新实例
        $instance = $this->related->newInstance($attributes);

        // When saving a polymorphic relationship, we need to set not only the foreign
        // key, but also the foreign key type, which is typically the class name of
        // the parent model. This makes the polymorphic item unique in the table.
        //
        // 在保存多态关系时，我们不仅需要设置外键，还需要设置外键类型，这通常是父模型的类名
        // 这使得多态条目在表中是惟一的
        //
        //        设置用于创建相关模型的外ID和类型
        $this->setForeignAttributesForCreate($instance);
        //将模型保存到数据库中
        $instance->save();

        return $instance;
    }

    /**
     * Set the foreign ID and type for creating a related model.
     *
     * 设置用于创建相关模型的外ID和类型
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    protected function setForeignAttributesForCreate(Model $model)
    {
        //               获得普通的外键               获取父本地键的键值
        $model->{$this->getForeignKeyName()} = $this->getParentKey();
        //       获得无表的纯变体类型名称
        $model->{$this->getMorphType()} = $this->morphClass;
    }

    /**
     * Get the relationship query.
     *
     * 获取关系查询
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        //          为关系查询添加约束                                                将基本WHERE子句添加到查询中
        return parent::getRelationExistenceQuery($query, $parentQuery, $columns)->where(
            $this->morphType, $this->morphClass
        );
    }

    /**
     * Get the foreign key "type" name.
     *
     * 获取外键“类型”的名称
     *
     * @return string
     */
    public function getQualifiedMorphType()
    {
        return $this->morphType;
    }

    /**
     * Get the plain morph type name without the table.
     *
     * 获得无表的纯变体类型名称
     *
     * @return string
     */
    public function getMorphType()
    {
        return last(explode('.', $this->morphType));
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
