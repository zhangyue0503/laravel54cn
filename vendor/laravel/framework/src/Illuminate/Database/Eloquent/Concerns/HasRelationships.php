<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

trait HasRelationships
{
    /**
     * The loaded relationships for the model.
     *
     * 模型的加载关系
     *
     * @var array
     */
    protected $relations = [];

    /**
     * The relationships that should be touched on save.
     *
     * 应注意的关系保存
     *
     * @var array
     */
    protected $touches = [];

    /**
     * The many to many relationship methods.
     *
     * 多对多关系方法
     *
     * @var array
     */
    public static $manyMethods = [
        'belongsToMany', 'morphToMany', 'morphedByMany',
        'guessBelongsToManyRelation', 'findFirstMethodThatIsntRelation',
    ];

    /**
     * Define a one-to-one relationship.
     *
     * 定义一对一关系
     *
     * @param  string  $related
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);//为相关模型创建新的模型实例

        $foreignKey = $foreignKey ?: $this->getForeignKey();//获取模型的默认外键名称

        $localKey = $localKey ?: $this->getKeyName();//从模型中获取主键
        //  创建一个新的有一个或多个关系实例(获取模型表的新查询生成器,$this,获取与模型相关联的表……)
        return new HasOne($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
    }

    /**
     * Define a polymorphic one-to-one relationship.
     *
     * 定义多态一对一关系
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function morphOne($related, $name, $type = null, $id = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);//为相关模型创建新的模型实例

        list($type, $id) = $this->getMorphs($name, $type, $id);//获取多态关系列

        $table = $instance->getTable();//获取模型表的新查询生成器

        $localKey = $localKey ?: $this->getKeyName();//从模型中获取主键
        //        创建一个新的变形一个或多个关系实例(获取模型表的新查询生成器,……)
        return new MorphOne($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $localKey);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * 定义一对一或多个关系
     *
     * @param  string  $related
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        //
        // 如果没有关系的名字了，我们将使用这个调试回溯提取调用的方法的名称和使用那个名字的关系，大部分时间，这将是我们所希望使用的关系
        //
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation(); //猜测“belongs to”关系名称
        }
        //为相关模型创建新的模型实例
        $instance = $this->newRelatedInstance($related);

        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        //
        // 如果没有提供任何外键，我们可以使用回溯利用关系函数的名字猜正确的外键名称，当它与“_id”应该常规匹配列
        //
        if (is_null($foreignKey)) {
            //         将字符串转换为蛇形命名                从模型中获取主键
            $foreignKey = Str::snake($relation).'_'.$instance->getKeyName();
        }

        // Once we have the foreign key names, we'll just create a new Eloquent query
        // for the related models and returns the relationship instance which will
        // actually be responsible for retrieving and hydrating every relations.
        //
        // 一旦我们有了外键名称，我们将创建一个新的Eloquent的查询相关的模型，并返回关系实例，这实际上是负责检索和水合每一个关系
        //
        $ownerKey = $ownerKey ?: $instance->getKeyName(); //从模型中获取主键
        //创建一个新的属于关系实例
        return new BelongsTo(
            //获取模型表的新查询生成器
            $instance->newQuery(), $this, $foreignKey, $ownerKey, $relation
        );
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * 定义一个多态，逆一对一或多关系
     *
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function morphTo($name = null, $type = null, $id = null)
    {
        // If no name is provided, we will use the backtrace to get the function name
        // since that is most likely the name of the polymorphic interface. We can
        // use that to get both the class and foreign key that will be utilized.
        //
        // 如果没有提供姓名，我们将使用回溯到函数名自认为是最有可能的多态接口的名称
        // 我们可以使用它来获取将被利用的类和外键
        //
        $name = $name ?: $this->guessBelongsToRelation();//猜测“belongs to”关系名称

        list($type, $id) = $this->getMorphs(//获取多态关系列
            //将字符串转换为蛇形命名
            Str::snake($name), $type, $id
        );

        // If the type value is null it is probably safe to assume we're eager loading
        // the relationship. In this case we'll just pass in a dummy query where we
        // need to remove any eager loads that may already be defined on a model.
        //
        // 如果类型值为空，我们假定我们贪婪加载关系，这可能是安全的
        // 在这种情况下，我们只是传递一个虚拟的查询，我们需要删除任何贪婪加载，可能已经定义了一个模型
        //
        return empty($class = $this->{$type})
                    ? $this->morphEagerTo($name, $type, $id)//定义一个多态，逆一对一或多关系
                    : $this->morphInstanceTo($class, $name, $type, $id);//定义一个多态，逆一对一或多关系
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * 定义一个多态，逆一对一或多关系
     *
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    protected function morphEagerTo($name, $type, $id)
    {
        //        创建一个新的关系实例
        return new MorphTo(
            //获取模型表的新查询生成器->设置贪婪加载的关系
            $this->newQuery()->setEagerLoads([]), $this, $id, null, $type, $name
        );
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * 定义一个多态，逆一对一或多关系
     *
     * @param  string  $target
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    protected function morphInstanceTo($target, $name, $type, $id)
    {
        $instance = $this->newRelatedInstance( //为相关模型创建新的模型实例
            static::getActualClassNameForMorph($target)//检索给定的变形类的实际类名
        );
        //        创建一个新的关系实例
        return new MorphTo(
            //获取模型表的新查询生成器                    从模型中获取主键
            $instance->newQuery(), $this, $id, $instance->getKeyName(), $type, $name
        );
    }

    /**
     * Retrieve the actual class name for a given morph class.
     *
     * 检索给定的变形类的实际类名
     *
     * @param  string  $class
     * @return string
     */
    public static function getActualClassNameForMorph($class)
    {
        //使用“点”符号从数组中获取一个项  设置或获取多态关系的变形图
        return Arr::get(Relation::morphMap(), $class, $class);
    }

    /**
     * Guess the "belongs to" relationship name.
     *
     * 猜测“belongs to”关系名称
     *
     * @return string
     */
    protected function guessBelongsToRelation()
    {
        list($one, $two, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return $caller['function'];
    }

    /**
     * Define a one-to-many relationship.
     *
     * 定义一对多关系
     *
     * @param  string  $related
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related); //为相关模型创建新的模型实例

        $foreignKey = $foreignKey ?: $this->getForeignKey();//获取模型的默认外键名称

        $localKey = $localKey ?: $this->getKeyName();//从模型中获取主键

        return new HasMany(
            //获取模型表的新查询生成器           获取与模型相关联的表
            $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }

    /**
     * Define a has-many-through relationship.
     *
     * 定义一个有许多通过关系
     *
     * @param  string  $related
     * @param  string  $through
     * @param  string|null  $firstKey
     * @param  string|null  $secondKey
     * @param  string|null  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function hasManyThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null)
    {
        $through = new $through;

        $firstKey = $firstKey ?: $this->getForeignKey();//获取模型的默认外键名称

        $secondKey = $secondKey ?: $through->getForeignKey();//获取模型的默认外键名称

        $localKey = $localKey ?: $this->getKeyName();//从模型中获取主键

        $instance = $this->newRelatedInstance($related);//为相关模型创建新的模型实例
        //      创建一个新的有许多通过关系实例(获取模型表的新查询生成器)
        return new HasManyThrough($instance->newQuery(), $this, $through, $firstKey, $secondKey, $localKey);
    }

    /**
     * Define a polymorphic one-to-many relationship.
     *
     * 定义多态的一对多关系
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function morphMany($related, $name, $type = null, $id = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related); //为相关模型创建新的模型实例

        // Here we will gather up the morph type and ID for the relationship so that we
        // can properly query the intermediate table of a relation. Finally, we will
        // get the table and create the relationship instances for the developers.
        //
        // 在这里，我们将收集的多态类型和ID的关系，以便我们可以正确地查询中间表的关系
        // 最后，我们将获取表并为开发人员创建关系实例。
        //
        list($type, $id) = $this->getMorphs($name, $type, $id); //获取多态关系列

        $table = $instance->getTable();//获取与模型相关联的表

        $localKey = $localKey ?: $this->getKeyName();//从模型中获取主键
        //                           获取模型表的新查询生成器
        return new MorphMany($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $localKey);
    }

    /**
     * Define a many-to-many relationship.
     *
     * 定义多对多关系
     *
     * @param  string  $related
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $relatedKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function belongsToMany($related, $table = null, $foreignKey = null, $relatedKey = null, $relation = null)
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        //
        // 如果没有关系的名字被通过，我们将回朔到调用函数的名字
        // 我们将使用该函数名称作为该关系的名称，因为这是一个极好的惯例适用
        //
        if (is_null($relation)) {
            $relation = $this->guessBelongsToManyRelation();//得到关系的名字属于多
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        //
        // 首先，我们需要确定外键和“其他键”的关系
        // 一旦我们确定了键，我们将生成查询实例以及我们需要的关系实例
        //
        $instance = $this->newRelatedInstance($related);//为相关模型创建新的模型实例

        $foreignKey = $foreignKey ?: $this->getForeignKey();//获取模型的默认外键名称

        $relatedKey = $relatedKey ?: $instance->getForeignKey();//获取模型的默认外键名称

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        //
        // 如果没有提供表名，我们可以猜测它将两模型使用下划线的字母顺序
        // 两模型的名字从默认的驼峰命名转化为蛇形命名
        //
        if (is_null($table)) {
            //获取多对多关系的连接表名
            $table = $this->joiningTable($related);
        }

        return new BelongsToMany(//创建一个新的属于许多关系实例
            //获取模型表的新查询生成器
            $instance->newQuery(), $this, $table, $foreignKey, $relatedKey, $relation
        );
    }

    /**
     * Define a polymorphic many-to-many relationship.
     *
     * 定义多态多对多关系
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $relatedKey
     * @param  bool  $inverse
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function morphToMany($related, $name, $table = null, $foreignKey = null, $relatedKey = null, $inverse = false)
    {
        $caller = $this->guessBelongsToManyRelation();//得到关系的名字属于多

        // First, we will need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we will make the query
        // instances, as well as the relationship instances we need for these.
        //
        // 首先，我们需要确定的外键和“其他键”的关系
        // 一旦我们确定了键，我们将生成查询实例，以及我们需要的关系实例
        //
        $instance = $this->newRelatedInstance($related);//为相关模型创建新的模型实例

        $foreignKey = $foreignKey ?: $name.'_id';

        $relatedKey = $relatedKey ?: $instance->getForeignKey();//获取模型的默认外键名称

        // Now we're ready to create a new query builder for this related model and
        // the relationship instances for this relation. This relations will set
        // appropriate query constraints then entirely manages the hydrations.
        //
        // 现在我们准备为这个相关模型创建一个新的查询生成器和关系的关系实例
        // 这种关系将设置适当的查询约束然后完全管理水合
        //
        $table = $table ?: Str::plural($name); //获取一个英语单词的复数形式

        return new MorphToMany(//创建一个新的多态到多关系实例
            //获取模型表的新查询生成器
            $instance->newQuery(), $this, $name, $table,
            $foreignKey, $relatedKey, $caller, $inverse
        );
    }

    /**
     * Define a polymorphic, inverse many-to-many relationship.
     *
     * 定义一个多态的逆多对多关系
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $relatedKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function morphedByMany($related, $name, $table = null, $foreignKey = null, $relatedKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();//获取模型的默认外键名称

        // For the inverse of the polymorphic many-to-many relations, we will change
        // the way we determine the foreign and other keys, as it is the opposite
        // of the morph-to-many method since we're figuring out these inverses.
        //
        // 对于逆多态多对多的关系，我们将改变我们确定外键和其他的键，因为它是变形的许多方法相反的因为我们解决了这些逆
        //
        $relatedKey = $relatedKey ?: $name.'_id';
        //        定义多态多对多关系
        return $this->morphToMany($related, $name, $table, $foreignKey, $relatedKey, true);
    }

    /**
     * Get the relationship name of the belongs to many.
     *
     * 得到关系的名字属于多
     *
     * @return string
     */
    protected function guessBelongsToManyRelation()
    {
        $caller = Arr::first(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), function ($trace) {
            return ! in_array($trace['function'], Model::$manyMethods);
        });

        return ! is_null($caller) ? $caller['function'] : null;
    }

    /**
     * Get the joining table name for a many-to-many relation.
     *
     * 获取多对多关系的连接表名
     *
     * @param  string  $related
     * @return string
     */
    public function joiningTable($related)
    {
        // The joining table name, by convention, is simply the snake cased models
        // sorted alphabetically and concatenated with an underscore, so we can
        // just sort the models and join them together to get the table name.
        //
        // 按惯例，连接表名只是简单地按字母顺序排列并与下划线连在一起的蛇模型，所以我们只需对模型进行排序并将它们组合在一起即可得到表名
        //
        $models = [
            Str::snake(class_basename($related)),//将字符串转换为蛇形命名
            Str::snake(class_basename($this)),//将字符串转换为蛇形命名
        ];

        // Now that we have the model names in an array we can just sort them and
        // use the implode function to join them together with an underscores,
        // which is typically used by convention within the database system.
        //
        // 现在我们有了模型名称数组中我们可以把它们和使用爆炸功能加入他们一起强调，这通常是由数据库系统内的惯例
        //
        sort($models);

        return strtolower(implode('_', $models));
    }

    /**
     * Determine if the model touches a given relation.
     *
     * 确定模型是否触及给定的关系
     *
     * @param  string  $relation
     * @return bool
     */
    public function touches($relation)
    {
        return in_array($relation, $this->touches);
    }

    /**
     * Touch the owning relations of the model.
     *
     * 触摸模型的拥有关系
     *
     * @return void
     */
    public function touchOwners()
    {
        foreach ($this->touches as $relation) {
            $this->$relation()->touch();

            if ($this->$relation instanceof self) {
                $this->$relation->fireModelEvent('saved', false);

                $this->$relation->touchOwners();
            } elseif ($this->$relation instanceof Collection) {
                $this->$relation->each(function (Model $relation) {
                    $relation->touchOwners();
                });
            }
        }
    }

    /**
     * Get the polymorphic relationship columns.
     *
     * 获取多态关系列
     *
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @return array
     */
    protected function getMorphs($name, $type, $id)
    {
        return [$type ?: $name.'_type', $id ?: $name.'_id'];
    }

    /**
     * Get the class name for polymorphic relations.
     *
     * 获取多态关系的类名
     *
     * @return string
     */
    public function getMorphClass()
    {
        $morphMap = Relation::morphMap();//设置或获取多态关系的变形图

        if (! empty($morphMap) && in_array(static::class, $morphMap)) {
            return array_search(static::class, $morphMap, true);
        }

        return static::class;
    }

    /**
     * Create a new model instance for a related model.
     *
     * 为相关模型创建新的模型实例
     *
     * @param  string  $class
     * @return mixed
     */
    protected function newRelatedInstance($class)
    {
        return tap(new $class, function ($instance) {
            if (! $instance->getConnectionName()) { //获取模型的当前连接名称
                $instance->setConnection($this->connection); //设置与模型相关联的连接
            }
        });
    }

    /**
     * Get all the loaded relations for the instance.
     *
     * 获取实例的所有加载关系
     *
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Get a specified relationship.
     *
     * 得到指定的关系
     *
     * @param  string  $relation
     * @return mixed
     */
    public function getRelation($relation)
    {
        return $this->relations[$relation];
    }

    /**
     * Determine if the given relation is loaded.
     *
     * 确定给定的关系是否加载
     *
     * @param  string  $key
     * @return bool
     */
    public function relationLoaded($key)
    {
        return array_key_exists($key, $this->relations);
    }

    /**
     * Set the specific relationship in the model.
     *
     * 在模型中设置特定关系
     *
     * @param  string  $relation
     * @param  mixed  $value
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;

        return $this;
    }

    /**
     * Set the entire relations array on the model.
     *
     * 在模型上设置整个关系数组
     *
     * @param  array  $relations
     * @return $this
     */
    public function setRelations(array $relations)
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * Get the relationships that are touched on save.
     *
     * 得到的关系，触及节省
     *
     * @return array
     */
    public function getTouchedRelations()
    {
        return $this->touches;
    }

    /**
     * Set the relationships that are touched on save.
     *
     * 设置被保存的关系
     *
     * @param  array  $touches
     * @return $this
     */
    public function setTouchedRelations(array $touches)
    {
        $this->touches = $touches;

        return $this;
    }
}
