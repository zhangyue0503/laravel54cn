<?php

namespace Illuminate\Database\Eloquent;

use Exception;
use ArrayAccess;
use JsonSerializable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

abstract class Model implements ArrayAccess, Arrayable, Jsonable, JsonSerializable, QueueableEntity, UrlRoutable
{
    use Concerns\HasAttributes,
        Concerns\HasEvents,
        Concerns\HasGlobalScopes,
        Concerns\HasRelationships,
        Concerns\HasTimestamps,
        Concerns\HidesAttributes,
        Concerns\GuardsAttributes;

    /**
     * The connection name for the model.
     *
     * 模型的链接名称
     *
     * @var string
     */
    protected $connection;

    /**
     * The table associated with the model.
     *
     * 与模型相关联的表
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model.
     *
     * 模型的主键
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * 自增ID的“类型”
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * 表示ID自动是否递增
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The relations to eager load on every query.
     *
     * 每次查询时的急切负荷关系
     *
     * @var array
     */
    protected $with = [];

    /**
     * The number of models to return for pagination.
     *
     * 根据模型数量返回分页
     *
     * @var int
     */
    protected $perPage = 15;

    /**
     * Indicates if the model exists.
     *
     * 表明模型如果存在
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Indicates if the model was inserted during the current request lifecycle.
     *
     * 指示在当前请求生命周期期间是否插入该模型
     *
     * @var bool
     */
    public $wasRecentlyCreated = false;

    /**
     * The connection resolver instance.
     *
     * 连接解析器实例
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected static $resolver;

    /**
     * The event dispatcher instance.
     *
     * 事件分发实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected static $dispatcher;

    /**
     * The array of booted models.
     *
     * 启动的模型数组
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * The array of global scopes on the model.
     *
     * 模型上的全局作用域数组
     *
     * @var array
     */
    protected static $globalScopes = [];

    /**
     * The name of the "created at" column.
     *
     * “created at”列名
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * “updated at”列名
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Create a new Eloquent model instance.
	 *
	 * 创建一个新的Eloquent模型实例
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->bootIfNotBooted();//检查模型是否需要被引导，如果是的话，引导它

        $this->syncOriginal();//同步当前的原始属性

        $this->fill($attributes);//用属性数组填充模型
    }

    /**
     * Check if the model needs to be booted and if so, do it.
     *
     * 检查模型是否需要被引导，如果是的话，引导它
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        if (! isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            $this->fireModelEvent('booting', false);//触发模型的给定的事件

            static::boot();//模型的“引导”方法

            $this->fireModelEvent('booted', false);//触发模型的给定的事件
        }
    }

    /**
     * The "booting" method of the model.
     *
     * 模型的“引导”方法
     *
     * @return void
     */
    protected static function boot()
    {
        static::bootTraits();
    }

    /**
     * Boot all of the bootable traits on the model.
     *
     * 启动所有的模型的启动特性
     *
     * @return void
     */
    protected static function bootTraits()
    {
        $class = static::class;

        foreach (class_uses_recursive($class) as $trait) { //返回类所使用的所有特性、子类和它们的特征
            //                                         获取类的“basename“从给定的对象/类
            if (method_exists($class, $method = 'boot'.class_basename($trait))) {
                forward_static_call([$class, $method]);
            }
        }
    }

    /**
     * Clear the list of booted models so they will be re-booted.
     *
     * 清除启动模型的列表，以便重新启动它们
     *
     * @return void
     */
    public static function clearBootedModels()
    {
        static::$booted = [];

        static::$globalScopes = [];
    }

    /**
     * Fill the model with an array of attributes.
     *
     * 用属性数组填充模型
     *
     * @param  array  $attributes
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded(); //确定模型是否完全保护
        //            获取给定数组的填充属性
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            $key = $this->removeTableFromKey($key); //从给定的键移除表名

            // The developers may choose to place some attributes in the "fillable" array
            // which means only those attributes may be set through mass assignment to
            // the model, and all others will just get ignored for security reasons.
            //
            // 开发者可以选择一些属性在“填充”数组意味着只有那些属性可以通过设置批量赋值的模型，而所有其它的因为安全原因被忽视
            //
            if ($this->isFillable($key)) { //确定给定的属性是否可以被批量赋值
                $this->setAttribute($key, $value);//在模型上设置给定属性
            } elseif ($totallyGuarded) {
                throw new MassAssignmentException($key);
            }
        }

        return $this;
    }

    /**
     * Fill the model with an array of attributes. Force mass assignment.
     *
     * 用属性数组填充模型。从批量赋值
     *
     * @param  array  $attributes
     * @return $this
     */
    public function forceFill(array $attributes)
    {
        // 当无守护时运行给定调用
        return static::unguarded(function () use ($attributes) {
            return $this->fill($attributes); //用属性数组填充模型
        });
    }

    /**
     * Remove the table name from a given key.
     *
     * 从给定的键移除表名
     *
     * @param  string  $key
     * @return string
     */
    protected function removeTableFromKey($key)
    {
        return Str::contains($key, '.') ? last(explode('.', $key)) : $key;
    }

    /**
     * Create a new instance of the given model.
	 *
	 * 创建给定模型的新实例
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Eloquent query builder instances.
        //
        // 该方法为我们生成当前模型的新模型实例提供了一种方便的方法
        // 这是特别有用的水合过程中的新对象，通过Eloquent的查询建设者实例。
        //
        $model = new static((array) $attributes);

        $model->exists = $exists;

        $model->setConnection( //设置与模型相关联的连接
            $this->getConnectionName()//获取模型的当前连接名称
        );

        return $model;
    }

    /**
     * Create a new model instance that is existing.
	 *
	 * 创建一个新的模型实例
     *
     * @param  array  $attributes
     * @param  string|null  $connection
     * @return static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = $this->newInstance([], true); //创建给定模型的新实例

        $model->setRawAttributes((array) $attributes, true); //通过数组设置模型类实例的attributes属性

        $model->setConnection($connection ?: $this->getConnectionName());//设置与模型相关联的连接(获取模型的当前连接名称)

        return $model;
    }

    /**
     * Begin querying the model on a given connection.
     *
     * 在给定的连接上开始查询模型
     *
     * @param  string|null  $connection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function on($connection = null)
    {
        // First we will just create a fresh instance of this model, and then we can
        // set the connection on the model so that it is be used for the queries
        // we execute, as well as being set on each relationship we retrieve.
        //
        // 首先，我们将创建一个新的实例，这个模型，然后我们可以设置模型上的连接，以便它被用于我们执行的查询，以及被设置在我们检索的每一个关系
        //
        $instance = new static;

        $instance->setConnection($connection); //设置与模型相关联的连接

        return $instance->newQuery();//根据模型类对应的数据表生成一个新的查询构造器
    }

    /**
     * Begin querying the model on the write connection.
     *
     * 开始在写连接上查询模型
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public static function onWriteConnection()
    {
        $instance = new static;
        //根据模型类对应的数据表生成一个新的查询构造器->用写的PDO的查询
        return $instance->newQuery()->useWritePdo();
    }

    /**
     * Get all of the models from the database.
	 *
	 * 从数据库获取所有模型
     *
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function all($columns = ['*'])
    {
		//创建一个新的Eloquent模型实例->获取模型表的新查询生成器
        return (new static)->newQuery()->get(
            is_array($columns) ? $columns : func_get_args()
        );
    }

    /**
     * Begin querying a model with eager loading.
     *
     * 开始查询一个贪婪加载的模型
     *
     * @param  array|string  $relations
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function with($relations)
    {
        //创建一个新的Eloquent模型实例->Illuminate\Database\Eloquent\Builder::with()设置应该加载的关系
        return (new static)->newQuery()->with(
            is_string($relations) ? func_get_args() : $relations
        );
    }

    /**
     * Eager load relations on the model.
     *
     * 模型上的贪婪加载关系
     *
     * @param  array|string  $relations
     * @return $this
     */
    public function load($relations)
    {
        //创建一个新的Eloquent模型实例->Illuminate\Database\Eloquent\Builder::with()设置应该加载的关系
        $query = $this->newQuery()->with(
            is_string($relations) ? func_get_args() : $relations
        );

        $query->eagerLoadRelations([$this]);//贪婪加载的关系模型

        return $this;
    }

    /**
     * Increment a column's value by a given amount.
     *
     * 按给定值递增列的值
     *
     * @param  string  $column
     * @param  int  $amount
     * @param  array  $extra
     * @return int
     */
    protected function increment($column, $amount = 1, array $extra = [])
    {
        return $this->incrementOrDecrement($column, $amount, $extra, 'increment');//在模型上运行递增或递减方法
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * 按给定数量递减列的值
     *
     * @param  string  $column
     * @param  int  $amount
     * @param  array  $extra
     * @return int
     */
    protected function decrement($column, $amount = 1, array $extra = [])
    {
        return $this->incrementOrDecrement($column, $amount, $extra, 'decrement');//在模型上运行递增或递减方法
    }

    /**
     * Run the increment or decrement method on the model.
     *
     * 在模型上运行递增或递减方法
     *
     * @param  string  $column
     * @param  int  $amount
     * @param  array  $extra
     * @param  string  $method
     * @return int
     */
    protected function incrementOrDecrement($column, $amount, $extra, $method)
    {
        $query = $this->newQuery();////创建一个新的Eloquent模型实例

        if (! $this->exists) {
            return $query->{$method}($column, $amount, $extra);
        }

        $this->incrementOrDecrementAttributeValue($column, $amount, $method);//增加基础属性值并与原始同步

        return $query->where( //将基本WHERE子句添加到查询中
            //从模型中获取主键      获取模型主键的值
            $this->getKeyName(), $this->getKey()
        )->{$method}($column, $amount, $extra);
    }

    /**
     * Increment the underlying attribute value and sync with original.
     *
     * 增加基础属性值并与原始同步
     *
     * @param  string  $column
     * @param  int  $amount
     * @param  string  $method
     * @return void
     */
    protected function incrementOrDecrementAttributeValue($column, $amount, $method)
    {
        $this->{$column} = $this->{$column} + ($method == 'increment' ? $amount : $amount * -1);

        $this->syncOriginalAttribute($column);//将单个原始属性与当前值同步
    }

    /**
     * Update the model in the database.
     *
     * 更新数据库中的模型
     *
     * @param  array  $attributes
     * @param  array  $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        if (! $this->exists) {
            return false;
        }
        //       用属性数组填充模型         将模型保存到数据库中
        return $this->fill($attributes)->save($options);
    }

    /**
     * Save the model and all of its relationships.
     *
     * 保存模型及其所有关系
     *
     * @return bool
     */
    public function push()
    {
        if (! $this->save()) {//将模型保存到数据库中
            return false;
        }

        // To sync all of the relationships to the database, we will simply spin through
        // the relationships and save each model via this "push" method, which allows
        // us to recurse into all of these nested relations for the model instance.
        //
        // 同步所有的关系数据库，我们可以简单的通过旋转的关系和保存每个模型通过“推”的方法，这使我们递归遍历所有这些嵌套关系模型实例
        //
        foreach ($this->relations as $models) {
            $models = $models instanceof Collection
                        //获取集合中的所有项目
                        ? $models->all() : [$models];

            foreach (array_filter($models) as $model) {
                if (! $model->push()) {//保存模型及其所有关系
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Save the model to the database.
     *
     * 将模型保存到数据库中
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        $query = $this->newQueryWithoutScopes(); //获取一个新的查询生成器，它没有任何全局作用域

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        //
        // 如果“保存”事件返回false，我们将从保存中返回并返回false，指示保存失败
        // 这提供了一个对任何听众取消保存操作的机会，如果验证失败或什么的
        //
        //触发模型的给定的事件
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        //
        // 如果该模型已经存在于数据库中，我们就可以更新我们的记录，已经在这个数据库中使用当前IDs在这个“where”子句只更新此模型
        // 否则，我们只会插入它们
        //
        if ($this->exists) {
            $saved = $this->isDirty() ? //确定模型或特定的属性是否已经修改
                        $this->performUpdate($query) : true;  //执行模型更新操作
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        //
        // 如果模型是全新的，我们将将其插入到我们的数据库中，并将模型中的id属性设置为新插入的行ID的值，该ID通常是由数据库管理的自动增量值
        //
        else {
            $saved = $this->performInsert($query); //执行模型插入操作
        }

        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.
        //
        // 如果模型成功保存，我们需要做一些事情，一旦完成
        // 我们将调用这里的“保存”方法来运行我们在模型成功保存后需要发生的任何操作
        //
        if ($saved) {
            $this->finishSave($options); //在模型保存后执行任何必要的操作
        }

        return $saved;
    }

    /**
     * Save the model to the database using transaction.
     *
     * 使用事务将模型保存到数据库中
     *
     * @param  array  $options
     * @return bool
     *
     * @throws \Throwable
     */
    public function saveOrFail(array $options = [])
    {
        //获取模型的数据库连接->在事务中执行闭包()
        return $this->getConnection()->transaction(function () use ($options) {
            return $this->save($options); //将模型保存到数据库中
        });
    }

    /**
     * Perform any actions that are necessary after the model is saved.
     *
     * 在模型保存后执行任何必要的操作
     *
     * @param  array  $options
     * @return void
     */
    protected function finishSave(array $options)
    {
        $this->fireModelEvent('saved', false); //触发模型的给定的事件

        $this->syncOriginal(); //当前模型同步原始属性

        if (Arr::get($options, 'touch', true)) {
            $this->touchOwners();//触发模型的拥有关系
        }
    }

    /**
     * Perform a model update operation.
     *
     * 执行模型更新操作
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        // If the updating event returns false, we will cancel the update operation so
        // developers can hook Validation systems into their models and cancel this
        // operation if the model does not pass validation. Otherwise, we update.
        //
        // 如果更新事件返回false，我们将取消更新操作，以便开发人员可以将验证系统与它们的模型挂钩，并在模型未通过验证时取消此操作
        // 否则，我们更新
        //
        if ($this->fireModelEvent('updating') === false) { //触发模型的给定的事件
            return false;
        }

        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        //
        // 首先，我们需要创建一个新的查询实例，并触摸创建和更新的时间戳的模型，这是我们维护的开发者方便
        // 然后我们将继续保存模型实例
        //
        if ($this->usesTimestamps()) { //确定模型使用时间戳
            $this->updateTimestamps(); //更新的创建和更新时间戳
        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
        //
        // 一旦我们运行了更新操作，我们将为这个模型实例触发“更新”事件
        // 这将允许开发人员在这些模型更新后加入这些模型，让他们有机会做任何特殊处理
        //
        $dirty = $this->getDirty();//获取上次同步后已更改的属性

        if (count($dirty) > 0) {
            //设置保存更新查询的键->更新数据库中的记录
            $this->setKeysForSaveQuery($query)->update($dirty);

            $this->fireModelEvent('updated', false); //触发模型的给定的事件
        }

        return true;
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
        //将基本WHERE子句添加到查询中(从模型中获取主键,'=',获取保存查询的主键值)
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());

        return $query;
    }

    /**
     * Get the primary key value for a save query.
     *
     * 获取保存查询的主键值
     *
     * @return mixed
     */
    protected function getKeyForSaveQuery()
    {
        return isset($this->original[$this->getKeyName()]) //从模型中获取主键
                        ? $this->original[$this->getKeyName()] //从模型中获取主键
                        : $this->getAttribute($this->getKeyName());//从模型中获取属性(从模型中获取主键)
    }

    /**
     * Perform a model insert operation.
     *
     * 执行模型插入操作
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        if ($this->fireModelEvent('creating') === false) { //触发模型的给定的事件
            return false;
        }

        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        //
        // 首先我们需要创建一个新的查询实例和触摸的创建和更新时间戳在这个模型中，这是由我们的开发人员提供方便
        // 之后，我们将继续保存这些模型实例
        //
        if ($this->usesTimestamps()) { //确定模型使用时间戳
            $this->updateTimestamps(); //更新的创建和更新时间戳
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        //
        // 如果模型有一个递增的key，我们可以用“insertGetId”方法在查询生成器，这将让我们回到最后插入该表从数据库ID
        // 虽然不是所有的表都要递增
        //
        $attributes = $this->attributes;

        if ($this->getIncrementing()) {//得到的值指示标识递增
            $this->insertAndSetId($query, $attributes);//插入给定的属性并在模型上设置id
        }

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        //
        // 如果表不递增，干脆我们就将这些属性作为他们
        // 这些属性数组必须包含以前由开发人员放置的“id”列，作为这些模型的手动确定的键
        //
        else {
            if (empty($attributes)) {
                return true;
            }

            $query->insert($attributes); //将新记录插入数据库
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        //
        // 我们将继续将现有属性设置为true，以便在创建的事件被触发时设置它，以防开发者在事件期间更新它
        // 这将允许他们这样做，并在这里运行更新
        //
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false); //触发模型的给定的事件

        return true;
    }

    /**
     * Insert the given attributes and set the ID on the model.
     *
     * 插入给定的属性并在模型上设置id
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $attributes
     * @return void
     */
    protected function insertAndSetId(Builder $query, $attributes)
    {
        //插入新记录并获取主键的值                                   从模型中获取主键
        $id = $query->insertGetId($attributes, $keyName = $this->getKeyName());

        $this->setAttribute($keyName, $id); //在模型上设置给定属性
    }

    /**
     * Destroy the models for the given IDs.
     *
     * 根据给定的IDs销毁模型
     *
     * @param  array|int  $ids
     * @return int
     */
    public static function destroy($ids)
    {
        // We'll initialize a count here so we will return the total number of deletes
        // for the operation. The developers can then check this number as a boolean
        // type value or get this total count of records deleted for logging, etc.
        //
        // 我们将在这里初始化一个计数，以便返回操作的总数
        // 然后，开发人员可以检查这个数字作为布尔类型值或获取记录的总计数删除记录等
        //
        $count = 0;

        $ids = is_array($ids) ? $ids : func_get_args();

        // We will actually pull the models from the database table and call delete on
        // each of them individually so that their events get fired properly with a
        // correct set of attributes in case the developers wants to check these.
        //
        // 实际上，我们将从数据库表中拔出模型并逐个调用它们，这样他们的事件会被正确的属性集合触发，以防开发者想检查这些属性
        //
        //   返回给定对象                       从模型中获取主键
        $key = with($instance = new static)->getKeyName();
        //          在查询中添加“在哪里”子句         将查询执行为“SELECT”语句
        foreach ($instance->whereIn($key, $ids)->get() as $model) {
            if ($model->delete()) { //从数据库删除这个模型
                $count++;
            }
        }

        return $count;
    }

    /**
     * Delete the model from the database.
     *
     * 从数据库删除这个模型
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete()
    {
        if (is_null($this->getKeyName())) { //从模型中获取主键
            throw new Exception('No primary key defined on model.');
        }

        // If the model doesn't exist, there is nothing to delete so we'll just return
        // immediately and not do anything else. Otherwise, we will continue with a
        // deletion process on the model, firing the proper events, and so forth.
        //
        // 如果模型不存在，就没有什么可删除的，所以我们只会立即返回，不做任何其他事情
        // 否则，我们将继续对模型进行删除过程，引发适当的事件，等等
        //
        if (! $this->exists) {
            return;
        }

        if ($this->fireModelEvent('deleting') === false) { //触发模型的给定的事件
            return false;
        }

        // Here, we'll touch the owning models, verifying these timestamps get updated
        // for the models. This will allow any caching to get broken on the parents
        // by the timestamp. Then we will go ahead and delete the model instance.
        //
        // 在这里，我们可以接触到拥有的模型，验证这些时间戳得到更新的模型
        // 这将允许任何缓存被打破的时间戳的父母
        // 然后我们将继续并删除模型实例
        //
        //触发模型的拥有关系
        $this->touchOwners();
        //在此模型实例上执行实际的删除查询
        $this->performDeleteOnModel();

        $this->exists = false;

        // Once the model has been deleted, we will fire off the deleted event so that
        // the developers may hook into post-delete operations. We will then return
        // a boolean true as the delete is presumably successful on the database.
        //
        // 一旦模型被删除，我们将关闭已删除的事件，以便开发人员可以插入到删除操作之后
        // 然后，我们将返回一个布尔真值，因为在数据库中删除可能是成功的
        //
        $this->fireModelEvent('deleted', false);//触发模型的给定的事件

        return true;
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * 强制删除软删除模型
     *
     * This method protects developers from running forceDelete when trait is missing.
     *
     * 该方法从运行时保护开发者forceDelete特质是失踪
     *
     * @return bool|null
     */
    public function forceDelete()
    {
        return $this->delete();//从数据库删除这个模型
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * 在此模型实例上执行实际的删除查询
     *
     * @return void
     */
    protected function performDeleteOnModel()
    {
        //设置保存更新查询的键(获取查询构造器)->从数据库中删除一个记录
        $this->setKeysForSaveQuery($this->newQueryWithoutScopes())->delete();
    }

    /**
     * Begin querying the model.
     *
     * 开始查询模型
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function query()
    {
        return (new static)->newQuery(); //根据模型类对应的数据表生成一个新的查询构造器
    }

    /**
     * Get a new query builder for the model's table.
	 *
	 * 获取模型表的新查询生成器
	 * *根据模型类对应的数据表生成一个新的查询构造器
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
		//获取一个新的查询生成器，它没有任何全局作用域
        $builder = $this->newQueryWithoutScopes();
        //            获取此类实例的全局作用域
        foreach ($this->getGlobalScopes() as $identifier => $scope) {
            $builder->withGlobalScope($identifier, $scope); //注册一个新的全局范围
        }

        return $builder;
    }

    /**
     * Get a new query builder that doesn't have any global scopes.
	 *
	 * 获取一个新的查询生成器，它没有任何全局作用域
	 * * 获取查询构造器
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newQueryWithoutScopes()
    {
		//        为模型创建一个新的Eloquent查询生成器(获取连接的新查询生成器实例)
        $builder = $this->newEloquentBuilder($this->newBaseQueryBuilder());

        // Once we have the query builders, we will set the model instances so the
        // builder can easily access any information it may need from the model
        // while it is constructing and executing various queries against it.
        //
        // 一旦我们有了查询生成器，我们将设置模型实例，这样生成器就可以轻松地访问模型中可能需要的任何信息，而在构建和执行对它的各种查询时，生成器都可以使用它
        //
        //               为被查询的模型设置模型实例->开始查询一个贪婪加载的模型
        return $builder->setModel($this)->with($this->with);
    }

    /**
     * Get a new query instance without a given scope.
     *
     * 获取没有给定范围的新查询实例
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryWithoutScope($scope)
    {
        $builder = $this->newQuery();//根据模型类对应的数据表生成一个新的查询构造器

        return $builder->withoutGlobalScope($scope);//移除注册的全局范围
    }

    /**
     * Create a new Eloquent query builder for the model.
	 *
	 * 为模型创建一个新的Eloquent查询生成器
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
		//创建一个新的Eloquent查询生成器实例
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
	 *
	 * 获取连接的新查询生成器实例
	 * * 获取针对一个连接的查询构造器
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
		//获取模型的数据库连接
		$connection = $this->getConnection();
        //创建一个新的查询构造器实例
        return new QueryBuilder(
            //                获取连接所使用的查询语法             获取连接所使用的查询后处理器
            $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
        );
    }

    /**
     * Create a new Eloquent Collection instance.
	 *
	 * 创建一个新的Eloquent集合实例
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
		//创建一个新集合
        return new Collection($models);
    }

    /**
     * Create a new pivot model instance.
     *
     * 创建一个新的枢纽模型实例
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  array  $attributes
     * @param  string  $table
     * @param  bool  $exists
     * @param  string|null  $using
     * @return \Illuminate\Database\Eloquent\Relations\Pivot
     */
    public function newPivot(Model $parent, array $attributes, $table, $exists, $using = null)
    {

        return $using ? $using::fromRawAttributes($parent, $attributes, $table, $exists)//从查询返回的原始值创建新的枢纽模型
                      : new Pivot($parent, $attributes, $table, $exists);//创建一个新的枢纽模型实例
    }

    /**
     * Convert the model instance to an array.
     *
     * 将模型实例转换为数组
     *
     * @return array
     */
    public function toArray()
    {
        //                    将模型的属性转换为数组        以数组形式获取模型的关系
        return array_merge($this->attributesToArray(), $this->relationsToArray());
    }

    /**
     * Convert the model instance to JSON.
     *
     * 将模型实例转换为JSON
     *
     * @param  int  $options
     * @return string
     *
     * @throws \Illuminate\Database\Eloquent\JsonEncodingException
     */
    public function toJson($options = 0)
    {
        //                         将JSON序列化对象的东西
        $json = json_encode($this->jsonSerialize(), $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw JsonEncodingException::forModel($this, json_last_error_msg()); //为模型创建新的JSON编码异常
        }

        return $json;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * 将JSON序列化对象的东西
     *
     * @return array
     */
    public function jsonSerialize()
    {
        //将模型实例转换为数组
        return $this->toArray();
    }

    /**
     * Reload a fresh model instance from the database.
     *
     * 从数据库重新加载一个新模型实例
     *
     * @param  array|string  $with
     * @return static|null
     */
    public function fresh($with = [])
    {
        if (! $this->exists) {
            return;
        }
        //               获取查询构造器
        return static::newQueryWithoutScopes()
                        ->with(is_string($with) ? func_get_args() : $with) //设置应该加载的关系
                        ->where($this->getKeyName(), $this->getKey())//将基本WHERE子句添加到查询中(从模型中获取主键,获取模型主键的值)
                        ->first();//执行查询和得到的第一个结果
    }

    /**
     * Clone the model into a new, non-existing instance.
     *
     * 将模型复制到一个新的，不存在的实例
     *
     * @param  array|null  $except
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function replicate(array $except = null)
    {
        $defaults = [
            $this->getKeyName(),//从模型中获取主键
            $this->getCreatedAtColumn(),//获取“created at”列的名称
            $this->getUpdatedAtColumn(),//获取“updated at”列的名称
        ];

        $attributes = Arr::except( //获取指定数组，除了指定的数组项
            $this->attributes, $except ? array_unique(array_merge($except, $defaults)) : $defaults
        );

        return tap(new static, function ($instance) use ($attributes) { //用给定的值调用给定的闭包，然后返回值
            $instance->setRawAttributes($attributes);//通过数组设置模型类实例的attributes属性

            $instance->setRelations($this->relations);//在模型上设置整个关系数组
        });
    }

    /**
     * Determine if two models have the same ID and belong to the same table.
     *
     * 确定两个模型是否具有相同的ID并且属于同一个表
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    public function is(Model $model)
    {
        return $this->getKey() === $model->getKey() && //获取模型主键的值
               $this->getTable() === $model->getTable() && //获取与模型相关联的表
               $this->getConnectionName() === $model->getConnectionName();//获取模型的当前连接名称
    }

    /**
     * Get the database connection for the model.
	 *
	 * 获取模型的数据库连接
	 *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
		//获取一个连接实例(获取模型的当前连接名称)
        return static::resolveConnection($this->getConnectionName());
    }

    /**
     * Get the current connection name for the model.
	 *
	 * 获取模型的当前连接名称
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * Set the connection associated with the model.
     *
     * 设置与模型相关联的连接
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnection($name)
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * Resolve a connection instance.
	 *
	 * 解析连接实例
	 * * 获取一个连接实例
     *
     * @param  string|null  $connection
     * @return \Illuminate\Database\Connection
     */
    public static function resolveConnection($connection = null)
    {
		//                      获取一个数据连接实例
        return static::$resolver->connection($connection);
    }

    /**
     * Get the connection resolver instance.
     *
     * 获取连接解析器实例
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public static function getConnectionResolver()
    {
        return static::$resolver;
    }

    /**
     * Set the connection resolver instance.
     *
     * 设置连接解析器实例
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public static function setConnectionResolver(Resolver $resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * Unset the connection resolver for models.
     *
     * 删除模型的连接解析器实例
     *
     * @return void
     */
    public static function unsetConnectionResolver()
    {
        static::$resolver = null;
    }

    /**
     * Get the table associated with the model.
	 *
	 * 获取与模型相关联的表
     *
     * @return string
     */
    public function getTable()
    {
        if (! isset($this->table)) {
			//                           将字符串转换为蛇形命名( 获取一个英语单词的复数形式 )
            return str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
        }

        return $this->table;
    }

    /**
     * Set the table associated with the model.
     *
     * 设置与模型相关联的表
     *
     * @param  string  $table
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Get the primary key for the model.
     *
     * 从模型中获取主键
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Set the primary key for the model.
     *
     * 设置模型的主键
     *
     * @param  string  $key
     * @return $this
     */
    public function setKeyName($key)
    {
        $this->primaryKey = $key;

        return $this;
    }

    /**
     * Get the table qualified key name.
     *
     * 获取表格的键名
     *
     * @return string
     */
    public function getQualifiedKeyName()
    {
        //           获取与模型相关联的表        从模型中获取主键
        return $this->getTable().'.'.$this->getKeyName();
    }

    /**
     * Get the auto incrementing key type.
     *
     * 实现自动递增键类型
     *
     * @return string
     */
    public function getKeyType()
    {
        return $this->keyType;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * 得到的值指示标识递增
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return $this->incrementing;
    }

    /**
     * Set whether IDs are incrementing.
     *
     * 设置IDs是否递增
     *
     * @param  bool  $value
     * @return $this
     */
    public function setIncrementing($value)
    {
        $this->incrementing = $value;

        return $this;
    }

    /**
     * Get the value of the model's primary key.
     *
     * 获取模型主键的值
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * Get the queueable identity for the entity.
     *
     * 获取实体的队列身份
     *
     * @return mixed
     */
    public function getQueueableId()
    {
        return $this->getKey();//获取模型主键的值
    }

    /**
     * Get the value of the model's route key.
     *
     * 获取模型路由键的值
     *
     * @return mixed
     */
    public function getRouteKey()
    {
        //        从模型中获取属性(从模型中获取路由键值)
        return $this->getAttribute($this->getRouteKeyName());
    }

    /**
     * Get the route key for the model.
     *
     * 从模型中获取路由键值
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->getKeyName();//从模型中获取主键
    }

    /**
     * Get the default foreign key name for the model.
     *
     * 获取模型的默认外键名称
     *
     * @return string
     */
    public function getForeignKey()
    {
        //将字符串转换为蛇形命名
        return Str::snake(class_basename($this)).'_'.$this->primaryKey;
    }

    /**
     * Get the number of models to return per page.
     *
     * 获取每个页面返回的模型数
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Set the number of models to return per page.
     *
     * 设置每个页面返回的模型数
     *
     * @param  int  $perPage
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * 动态检索模型上的属性
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        //从模型中获取属性
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * 在模型上动态设置属性
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        //在模型上设置给定属性
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if the given attribute exists.
     *
     * 确定给定属性是否存在
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * Get the value for a given offset.
     *
     * 获取给定属性的值
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Set the value for a given offset.
     *
     * 设置给定属性的值
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * 删除给定属性的值
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * 确定模型上是否存在属性或关系
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return ! is_null($this->getAttribute($key));//从模型中获取属性
    }

    /**
     * Unset an attribute on the model.
     *
     * 删除模型上的属性
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * 将动态方法调用处理到模型中
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, ['increment', 'decrement'])) {
            return $this->$method(...$parameters);
        }
        //        获取模型表的新查询生成器
        return $this->newQuery()->$method(...$parameters);
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * 将动态静态方法调用处理到方法中
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    /**
     * Convert the model to its string representation.
     *
     * 将模型转换为字符串表示形式
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson(); //将模型实例转换为JSON
    }

    /**
     * When a model is being unserialized, check if it needs to be booted.
     *
     * 当一个模型是不可序列化的，检查它是否需要启动
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->bootIfNotBooted(); //检查模型是否需要被引导，如果是的话，引导它
    }
}
