<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

abstract class HasOneOrMany extends Relation
{
    /**
     * The foreign key of the parent model.
     *
     * 父模型的外键
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The local key of the parent model.
     *
     * 父模型的本地键
     *
     * @var string
     */
    protected $localKey;

    /**
     * The count of self joins.
     *
     * 自连接数
     *
     * @var int
     */
    protected static $selfJoinCount = 0;

    /**
     * Create a new has one or many relationship instance.
     *
     * 创建一个新的有一个或多个关系实例
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;
        //创建一个新的关系实例
        parent::__construct($query, $parent);
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
            //将基本WHERE子句添加到查询中                          获取父本地键的键值
            $this->query->where($this->foreignKey, '=', $this->getParentKey());
            //在查询中添加“where not null”子句
            $this->query->whereNotNull($this->foreignKey);
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
        //在查询中添加“where in”子句
        $this->query->whereIn(
            //                  获取一组模型的所有主键
            $this->foreignKey, $this->getKeys($models, $this->localKey)
        );
    }

    /**
     * Match the eagerly loaded results to their single parents.
     *
     * 将贪婪的结果与他们的单亲父母相匹配
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function matchOne(array $models, Collection $results, $relation)
    {
        //将贪婪加载的结果与他们的父母相匹配
        return $this->matchOneOrMany($models, $results, $relation, 'one');
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * 将贪婪的结果与他们的父母相匹配
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function matchMany(array $models, Collection $results, $relation)
    {
        //将贪婪加载的结果与他们的父母相匹配
        return $this->matchOneOrMany($models, $results, $relation, 'many');
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * 将贪婪加载的结果与他们的父母相匹配
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @param  string  $type
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        //             根据关系的外键建立模型字典
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        //
        // 一旦我们有了字典，我们就可以简单地通过父模型把它们和孩子们联系起来，使用键控字典使匹配变得非常方便和简单
        // 然后我们就返回它们
        //
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->getAttribute($this->localKey)])) {
                //在模型中设置特定关系
                $model->setRelation(
                    //              通过一个或多个类型来获取关系的值
                    $relation, $this->getRelationValue($dictionary, $key, $type)
                );
            }
        }

        return $models;
    }

    /**
     * Get the value of a relationship by one or many type.
     *
     * 通过一个或多个类型来获取关系的值
     *
     * @param  array   $dictionary
     * @param  string  $key
     * @param  string  $type
     * @return mixed
     */
    protected function getRelationValue(array $dictionary, $key, $type)
    {
        $value = $dictionary[$key];
        //                                                       创建一个新的Eloquent集合实例
        return $type == 'one' ? reset($value) : $this->related->newCollection($value);
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * 根据关系的外键建立模型字典
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        $dictionary = [];
        //                  获得普通的外键
        $foreign = $this->getForeignKeyName();

        // First we will create a dictionary of models keyed by the foreign key of the
        // relationship as this will allow us to quickly access all of the related
        // models without having to do nested looping which will be quite slow.
        //
        // 首先,我们将创建一个字典模型的外键的关系,因为这将使我们能够快速访问所有相关的模型,而不必做嵌套循环将非常缓慢
        //
        foreach ($results as $result) {
            $dictionary[$result->{$foreign}][] = $result;
        }

        return $dictionary;
    }

    /**
     * Find a model by its primary key or return new instance of the related model.
     *
     * 通过其主键找到一个模型，或者返回相关模型的新实例
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function findOrNew($id, $columns = ['*'])
    {
        if (is_null($instance = $this->find($id, $columns))) {
            //                     创建给定模型的新实例
            $instance = $this->related->newInstance();
            //在模型上设置给定属性          获得普通的外键                  获取父本地键的键值
            $instance->setAttribute($this->getForeignKeyName(), $this->getParentKey());
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
            //                     创建给定模型的新实例
            $instance = $this->related->newInstance($attributes);
            //在模型上设置给定属性            获得普通的外键               获取父本地键的键值
            $instance->setAttribute($this->getForeignKeyName(), $this->getParentKey());
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
     * 将模型实例附加到父模型
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function save(Model $model)
    {
        //在模型上设置给定属性(获得普通的外键,获取父本地键的键值)
        $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
        //将模型保存到数据库中
        return $model->save() ? $model : false;
    }

    /**
     * Attach a collection of models to the parent instance.
     *
     * 将模型的集合附加到父实例
     *
     * @param  \Traversable|array  $models
     * @return \Traversable|array
     */
    public function saveMany($models)
    {
        foreach ($models as $model) {
            //将模型实例附加到父模型
            $this->save($model);
        }

        return $models;
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
        //用给定的值调用给定的闭包，然后返回值(创建给定模型的新实例
        return tap($this->related->newInstance($attributes), function ($instance) {
            //                          获得普通的外键                获取父本地键的键值
            $instance->setAttribute($this->getForeignKeyName(), $this->getParentKey());

            $instance->save();
        });
    }

    /**
     * Create a Collection of new instances of the related model.
     *
     * 创建相关模型的新实例集合
     *
     * @param  array  $records
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function createMany(array $records)
    {
        //                     创建一个新的Eloquent集合实例
        $instances = $this->related->newCollection();

        foreach ($records as $record) {
            //将项目推到集合的结尾(创建相关模型的新实例)
            $instances->push($this->create($record));
        }

        return $instances;
    }

    /**
     * Perform an update on all the related models.
     *
     * 对所有相关模型执行更新
     *
     * @param  array  $attributes
     * @return int
     */
    public function update(array $attributes)
    {
        //确定模型是否使用时间戳
        if ($this->related->usesTimestamps()) {
            //获取相关模型的“更新at”列的名称                   为模型获取一个新的时间戳
            $attributes[$this->relatedUpdatedAt()] = $this->related->freshTimestampString();
        }
        //更新数据库中的记录
        return $this->query->update($attributes);
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
        if ($query->getQuery()->from == $parentQuery->getQuery()->from) {
            //            在相同的表中添加关系查询的约束
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }
        //从本质上讲，这些查询比较喜欢whereColumn列名
        return parent::getRelationExistenceQuery($query, $parentQuery, $columns);
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
        //设置查询对象的表(获取被查询的模型实例->获取与模型相关联的表)                   获取一个关系连接表散列
        $query->from($query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash());
        //获取被查询的模型实例     设置与模型相关联的表
        $query->getModel()->setTable($hash);
        //设置要选择的列                     向查询中添加“where”子句比较两列
        return $query->select($columns)->whereColumn(
            //获得完全合格的父密钥名                                       获得普通的外键
            $this->getQualifiedParentKeyName(), '=', $hash.'.'.$this->getForeignKeyName()
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
     * Get the key for comparing against the parent key in "has" query.
     *
     * 获取在“已”查询中与父键进行比较的键
     *
     * @return string
     */
    public function getExistenceCompareKey()
    {
        //获取关系的外键
        return $this->getQualifiedForeignKeyName();
    }

    /**
     * Get the key value of the parent's local key.
     *
     * 获取父本地键的键值
     *
     * @return mixed
     */
    public function getParentKey()
    {
        //                     从模型中获取属性
        return $this->parent->getAttribute($this->localKey);
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
        //              获取与模型相关联的表
        return $this->parent->getTable().'.'.$this->localKey;
    }

    /**
     * Get the plain foreign key.
     *
     * 获得普通的外键
     *
     * @return string
     */
    public function getForeignKeyName()
    {
        //                               获取关系的外键
        $segments = explode('.', $this->getQualifiedForeignKeyName());

        return $segments[count($segments) - 1];
    }

    /**
     * Get the foreign key for the relationship.
     *
     * 获取关系的外键
     *
     * @return string
     */
    public function getQualifiedForeignKeyName()
    {
        return $this->foreignKey;
    }
}
