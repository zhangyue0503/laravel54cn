<?php

namespace Illuminate\Database\Capsule;

use PDO;
use Illuminate\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Traits\CapsuleManagerTrait;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Connectors\ConnectionFactory;

class Manager
{
    use CapsuleManagerTrait;

    /**
     * The database manager instance.
     *
     * 数据库管理器实例
     *
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $manager;

    /**
     * Create a new database capsule manager.
     *
     * 创建一个新的数据库胶囊管理器
     *
     * @param  \Illuminate\Container\Container|null  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        //设置IoC容器实例
        $this->setupContainer($container ?: new Container);

        // Once we have the container setup, we will setup the default configuration
        // options in the container "config" binding. This will make the database
        // manager work correctly out of the box without extreme configuration.
        //
        // 一旦我们有了容器设置，我们将在容器“config”绑定中设置默认的配置选项
        // 这将使数据库管理器在没有极端配置的情况下正确地工作
        //
        //设置默认的数据库配置选项
        $this->setupDefaultConfiguration();
        //构建数据库管理器实例
        $this->setupManager();
    }

    /**
     * Setup the default database configuration options.
     *
     * 设置默认的数据库配置选项
     *
     * @return void
     */
    protected function setupDefaultConfiguration()
    {
        $this->container['config']['database.fetch'] = PDO::FETCH_OBJ;

        $this->container['config']['database.default'] = 'default';
    }

    /**
     * Build the database manager instance.
     *
     * 构建数据库管理器实例
     *
     * @return void
     */
    protected function setupManager()
    {
        //创建一个新的连接工厂实例
        $factory = new ConnectionFactory($this->container);
        //创建一个新的数据库管理实例
        $this->manager = new DatabaseManager($this->container, $factory);
    }

    /**
     * Get a connection instance from the global manager.
     *
     * 从全局管理器获取连接实例
     *
     * @param  string  $connection
     * @return \Illuminate\Database\Connection
     */
    public static function connection($connection = null)
    {
        return static::$instance->getConnection($connection);
    }

    /**
     * Get a fluent query builder instance.
     *
     * 获得一个流畅的查询构建器实例
     *
     * @param  string  $table
     * @param  string  $connection
     * @return \Illuminate\Database\Query\Builder
     */
    public static function table($table, $connection = null)
    {
        return static::$instance->connection($connection)->table($table);
    }

    /**
     * Get a schema builder instance.
     *
     * 获取模式构建器实例
     *
     * @param  string  $connection
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function schema($connection = null)
    {
        return static::$instance->connection($connection)->getSchemaBuilder();
    }

    /**
     * Get a registered connection instance.
     *
     * 获得注册的连接实例
     *
     * @param  string  $name
     * @return \Illuminate\Database\Connection
     */
    public function getConnection($name = null)
    {
        //获取数据库连接实例
        return $this->manager->connection($name);
    }

    /**
     * Register a connection with the manager.
     *
     * 与管理器注册一个连接
     *
     * @param  array   $config
     * @param  string  $name
     * @return void
     */
    public function addConnection(array $config, $name = 'default')
    {
        $connections = $this->container['config']['database.connections'];

        $connections[$name] = $config;

        $this->container['config']['database.connections'] = $connections;
    }

    /**
     * Bootstrap Eloquent so it is ready for usage.
     *
     * 引导程序，它已经准备好使用了
     *
     * @return void
     */
    public function bootEloquent()
    {
        //设置连接解析器实例
        Eloquent::setConnectionResolver($this->manager);

        // If we have an event dispatcher instance, we will go ahead and register it
        // with the Eloquent ORM, allowing for model callbacks while creating and
        // updating "model" instances; however, it is not necessary to operate.
        //
        // 如果我们有一个事件调度器实例,我们将继续注册用雄辩的ORM,允许模型回调而创建和更新“模型”实例;然而,它没有必要操作
        //
        //                    获取当前事件调度程序实例
        if ($dispatcher = $this->getEventDispatcher()) {
            //             设置事件调度器实例
            Eloquent::setEventDispatcher($dispatcher);
        }
    }

    /**
     * Set the fetch mode for the database connections.
     *
     * 为数据库连接设置获取模式
     *
     * @param  int  $fetchMode
     * @return $this
     */
    public function setFetchMode($fetchMode)
    {
        $this->container['config']['database.fetch'] = $fetchMode;

        return $this;
    }

    /**
     * Get the database manager instance.
     *
     * 获取数据库管理器实例
     *
     * @return \Illuminate\Database\DatabaseManager
     */
    public function getDatabaseManager()
    {
        return $this->manager;
    }

    /**
     * Get the current event dispatcher instance.
     *
     * 获取当前事件调度程序实例
     *
     * @return \Illuminate\Contracts\Events\Dispatcher|null
     */
    public function getEventDispatcher()
    {
        //                   确定给定的抽象类型是否已绑定
        if ($this->container->bound('events')) {
            return $this->container['events'];
        }
    }

    /**
     * Set the event dispatcher instance to be used by connections.
     *
     * 设置事件调度程序实例，以供连接使用
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public function setEventDispatcher(Dispatcher $dispatcher)
    {
        //在容器中注册一个已存在的实例
        $this->container->instance('events', $dispatcher);
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        //从全局管理器获取连接实例
        return static::connection()->$method(...$parameters);
    }
}
