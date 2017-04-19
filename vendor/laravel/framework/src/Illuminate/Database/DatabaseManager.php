<?php

namespace Illuminate\Database;

use PDO;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Database\Connectors\ConnectionFactory;

class DatabaseManager implements ConnectionResolverInterface
{
    /**
     * The application instance.
     *
     * 应用实例
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The database connection factory instance.
     *
     * 数据库连接工厂实例
     *
     * @var \Illuminate\Database\Connectors\ConnectionFactory
     */
    protected $factory;

    /**
     * The active connection instances.
     *
     * 活动连接实例
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The custom connection resolvers.
     *
     * 当前连接解析器
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * Create a new database manager instance.
     *
     * 创建一个新的数据库管理实例
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  \Illuminate\Database\Connectors\ConnectionFactory  $factory
     * @return void
     */
    public function __construct($app, ConnectionFactory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;
    }

    /**
     * Get a database connection instance.
	 *
	 * 获取数据库连接实例
     *
     * @param  string  $name
     * @return \Illuminate\Database\Connection
     */
    public function connection($name = null)
    {
        list($database, $type) = $this->parseConnectionName($name); //将连接解析为名称和读/写类型的数组

        $name = $name ?: $database;

        // If we haven't created this connection, we'll create it based on the config
        // provided in the application. Once we've created the connections we will
        // set the "fetch mode" for PDO which determines the query return types.
        //
        // 如果我们没有创建这个连接，我们将根据应用程序提供的配置创建它
        // 一旦我们创建的连接，我们将设置“读取模式”PDO决定查询返回类型
        //
        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->configure(  //准备数据库连接实例
            	//              制作数据库连接实例
                $connection = $this->makeConnection($database), $type
            );
        }

        return $this->connections[$name];
    }

    /**
     * Parse the connection into an array of the name and read / write type.
	 *
	 * 将连接解析为名称和读/写类型的数组
     *
     * @param  string  $name
     * @return array
     */
    protected function parseConnectionName($name)
    {
        $name = $name ?: $this->getDefaultConnection(); // 获取默认的连接名称

        return Str::endsWith($name, ['::read', '::write'])
                            ? explode('::', $name, 2) : [$name, null];
    }

    /**
     * Make the database connection instance.
	 *
	 * 制作数据库连接实例
     *
     * @param  string  $name
     * @return \Illuminate\Database\Connection
     */
    protected function makeConnection($name)
    {
        $config = $this->configuration($name); // 获取连接的配置

		// First we will check by the connection name to see if an extension has been
        // registered specifically for that connection. If it has we will call the
        // Closure and pass it the config allowing it to resolve the connection.
        //
        // 首先，我们将检查连接名，以查看是否已为该连接专门注册了扩展名
        // 如果有，我们将调用闭包，并通过它的配置，允许它解决连接
        //
        if (isset($this->extensions[$name])) {
            return call_user_func($this->extensions[$name], $config, $name);
        }

        // Next we will check to see if an extension has been registered for a driver
        // and will call the Closure if so, which allows us to have a more generic
        // resolver for the drivers themselves which applies to all connections.
        //
        // 接下来我们将检查一个扩展是否已经注册给一个驱动程序，并将调用闭包，如果这样，这使我们能够有一个更通用的解析器的驱动程序本身适用于所有连接
        //
        if (isset($this->extensions[$driver = $config['driver']])) {
            return call_user_func($this->extensions[$driver], $config, $name);
        }

        return $this->factory->make($config, $name); //建立了一个基于PDO连接配置
	}

    /**
     * Get the configuration for a connection.
	 *
	 * 获取连接的配置
	 *
     * @param  string  $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration($name)
    {
        $name = $name ?: $this->getDefaultConnection(); //获取默认的连接名称

        // To get the database connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        //
        // 要获得数据库连接配置，我们将只需提取每个连接配置并获得给定名称的配置
        // 如果配置不存在，我们将抛出一个异常
        //
        $connections = $this->app['config']['database.connections'];

        if (is_null($config = Arr::get($connections, $name))) {
            throw new InvalidArgumentException("Database [$name] not configured.");
        }

        return $config;
    }

    /**
     * Prepare the database connection instance.
     *
     * 准备数据库连接实例
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $type
     * @return \Illuminate\Database\Connection
     */
    protected function configure(Connection $connection, $type)
    {
        $connection = $this->setPdoForType($connection, $type); //为数据库连接实例准备读/写模式

        // First we'll set the fetch mode and a few other dependencies of the database
        // connection. This method basically just configures and prepares it to get
        // used by the application. Once we're finished we'll return it back out.
        //
        // 首先，我们将设置获取模式和数据库连接的其他一些依赖关系
        // 该方法基本上只是配置和准备它得到应用程序使用
        // 一旦我们完成，我们将返回它
        //
        if ($this->app->bound('events')) { //确定给定的抽象类型是否已绑定
            $connection->setEventDispatcher($this->app['events']);  //设置连接使用的事件调度程序
        }

        // Here we'll set a reconnector callback. This reconnector can be any callable
        // so we will set a Closure to reconnect from this manager with the name of
        // the connection, which will allow us to reconnect from the connections.
        //
        // 在这里，我们将设置一reconnector回调
        // 这reconnector可以是任何可调用所以我们将关闭重新从这个管理与该连接的名称，这将让我们重新连接从连接
        //
        $connection->setReconnector(function ($connection) { //设置连接上的重新连接实例
            $this->reconnect($connection->getName()); //重新连接给定的数据库(获取数据库连接名)
        });

        return $connection;
    }

    /**
     * Prepare the read / write mode for database connection instance.
     *
     * 为数据库连接实例准备读/写模式
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $type
     * @return \Illuminate\Database\Connection
     */
    protected function setPdoForType(Connection $connection, $type = null)
    {
        if ($type == 'read') {
            //设置PDO连接(用于读取的当前PDO连接)
            $connection->setPdo($connection->getReadPdo());
        } elseif ($type == 'write') {
            //设置用于读取的PDO连接(获取当前的PDO连接)
            $connection->setReadPdo($connection->getPdo());
        }

        return $connection;
    }

    /**
     * Disconnect from the given database and remove from local cache.
     *
     * 从给定的数据库断开并从本地缓存移除
     *
     * @param  string  $name
     * @return void
     */
    public function purge($name = null)
    {
        $name = $name ?: $this->getDefaultConnection(); //获取默认的连接名称

        $this->disconnect($name); //从给定的数据库断开

        unset($this->connections[$name]);
    }

    /**
     * Disconnect from the given database.
     *
     * 从给定的数据库断开
     *
     * @param  string  $name
     * @return void
     */
    public function disconnect($name = null)
    {
        if (isset($this->connections[$name = $name ?: $this->getDefaultConnection()])) { //获取默认的连接名称
            $this->connections[$name]->disconnect();//从给定的数据库断开
        }
    }

    /**
     * Reconnect to the given database.
     *
     * 重新连接给定的数据库
     *
     * @param  string  $name
     * @return \Illuminate\Database\Connection
     */
    public function reconnect($name = null)
    {
        $this->disconnect($name = $name ?: $this->getDefaultConnection());//从给定的数据库断开(获取默认的连接名称)

        if (! isset($this->connections[$name])) {
            return $this->connection($name); //获取数据库连接实例
        }

        return $this->refreshPdoConnections($name); //刷新在给定连接的PDO连接
    }

    /**
     * Refresh the PDO connections on a given connection.
     *
     * 刷新在给定连接的PDO连接
     *
     * @param  string  $name
     * @return \Illuminate\Database\Connection
     */
    protected function refreshPdoConnections($name)
    {
        $fresh = $this->makeConnection($name); //制作数据库连接实例

        return $this->connections[$name]
                                ->setPdo($fresh->getPdo()) ////设置PDO连接(当前的PDO连接)
                                ->setReadPdo($fresh->getReadPdo()); //设置用于读取的PDO连接(用于读取的当前PDO连接)
    }

    /**
     * Get the default connection name.
	 *
	 * 获取默认的连接名称
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->app['config']['database.default'];
    }

    /**
     * Set the default connection name.
     *
     * 设置默认的链接名称
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->app['config']['database.default'] = $name;
    }

    /**
     * Get all of the support drivers.
     *
     * 得到所有的支持驱动程序
     *
     * @return array
     */
    public function supportedDrivers()
    {
        return ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];
    }

    /**
     * Get all of the drivers that are actually available.
     *
     * 获取所有实际可用的驱动程序
     *
     * @return array
     */
    public function availableDrivers()
    {
        return array_intersect(
            $this->supportedDrivers(), //得到所有的支持驱动程序
            str_replace('dblib', 'sqlsrv', PDO::getAvailableDrivers())
        );
    }

    /**
     * Register an extension connection resolver.
     *
     * 注册扩展连接解析器
     *
     * @param  string    $name
     * @param  callable  $resolver
     * @return void
     */
    public function extend($name, callable $resolver)
    {
        $this->extensions[$name] = $resolver;
    }

    /**
     * Return all of the created connections.
     *
     * 返回所有已创建的连接
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Dynamically pass methods to the default connection.
	 *
	 * 动态传递方法到默认连接
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        //      获取数据库连接实例
        return $this->connection()->$method(...$parameters);
    }
}
