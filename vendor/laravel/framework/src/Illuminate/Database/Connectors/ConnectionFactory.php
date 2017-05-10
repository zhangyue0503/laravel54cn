<?php

namespace Illuminate\Database\Connectors;

use PDOException;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SqlServerConnection;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;

class ConnectionFactory
{
    /**
     * The IoC container instance.
     *
     * IoC容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Create a new connection factory instance.
     *
     * 创建一个新的连接工厂实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Establish a PDO connection based on the configuration.
	 *
	 * 建立了一个基于PDO连接配置
	 *
     * @param  array   $config
     * @param  string  $name
     * @return \Illuminate\Database\Connection
     */
    public function make(array $config, $name = null)
    {
        //解析和准备数据库配置
        $config = $this->parseConfig($config, $name);

        if (isset($config['read'])) {
            //创建一个单独的数据库连接实例
            return $this->createReadWriteConnection($config);
        }

        return $this->createSingleConnection($config); //创建单个数据库连接实例
    }

    /**
     * Parse and prepare the database configuration.
     *
     * 解析和准备数据库配置
     *
     * @param  array   $config
     * @param  string  $name
     * @return array
     */
    protected function parseConfig(array $config, $name)
    {
        //如果不存在，使用“点”表示法将一个元素添加到数组中
        return Arr::add(Arr::add($config, 'prefix', ''), 'name', $name);
    }

    /**
     * Create a single database connection instance.
	 *
	 * 创建单个数据库连接实例
     *
     * @param  array  $config
     * @return \Illuminate\Database\Connection
     */
    protected function createSingleConnection(array $config)
    {
        //创建一个解析到PDO实例的新闭包
        $pdo = $this->createPdoResolver($config);

        return $this->createConnection( // 创建一个新的连接实例
            $config['driver'], $pdo, $config['database'], $config['prefix'], $config
        );
    }

    /**
     * Create a single database connection instance.
     *
     * 创建一个单独的数据库连接实例
     *
     * @param  array  $config
     * @return \Illuminate\Database\Connection
     */
    protected function createReadWriteConnection(array $config)
    {
        //创建单个数据库连接实例(获取读/写连接的读配置)
        $connection = $this->createSingleConnection($this->getWriteConfig($config));
        //设置用于读取的PDO连接(为阅读创建一个新的PDO实例)
        return $connection->setReadPdo($this->createReadPdo($config));
    }

    /**
     * Create a new PDO instance for reading.
     *
     * 为阅读创建一个新的PDO实例
     *
     * @param  array  $config
     * @return \Closure
     */
    protected function createReadPdo(array $config)
    {
        //创建一个解析到PDO实例的新闭包(获取读/写连接的读配置)
        return $this->createPdoResolver($this->getReadConfig($config));
    }

    /**
     * Get the read configuration for a read / write connection.
     *
     * 获取读/写连接的读配置
     *
     * @param  array  $config
     * @return array
     */
    protected function getReadConfig(array $config)
    {
        //合并一个用于读/写连接的配置(获得读/写级别配置)
        return $this->mergeReadWriteConfig(
            $config, $this->getReadWriteConfig($config, 'read')
        );
    }

    /**
     * Get the read configuration for a read / write connection.
     *
     * 获取读/写连接的读配置
     *
     * @param  array  $config
     * @return array
     */
    protected function getWriteConfig(array $config)
    {
        //合并一个用于读/写连接的配置(获得读/写级别配置)
        return $this->mergeReadWriteConfig(
            $config, $this->getReadWriteConfig($config, 'write')
        );
    }

    /**
     * Get a read / write level configuration.
     *
     * 获得读/写级别配置
     *
     * @param  array   $config
     * @param  string  $type
     * @return array
     */
    protected function getReadWriteConfig(array $config, $type)
    {
        return isset($config[$type][0])
                        ? $config[$type][array_rand($config[$type])]
                        : $config[$type];
    }

    /**
     * Merge a configuration for a read / write connection.
     *
     * 合并一个用于读/写连接的配置
     *
     * @param  array  $config
     * @param  array  $merge
     * @return array
     */
    protected function mergeReadWriteConfig(array $config, array $merge)
    {
        //获取指定数组，除了指定的数组项
        return Arr::except(array_merge($config, $merge), ['read', 'write']);
    }

    /**
     * Create a new Closure that resolves to a PDO instance.
     *
     * 创建一个解析到PDO实例的新闭包
     *
     * @param  array  $config
     * @return \Closure
     */
    protected function createPdoResolver(array $config)
    {
        return array_key_exists('host', $config)
                            ? $this->createPdoResolverWithHosts($config)//创建一个新的闭包，该闭包将解析为带有特定主机或主机数组的PDO实例
                            : $this->createPdoResolverWithoutHosts($config);//创建一个新的闭包，它将解析为没有配置主机的PDO实例
    }

    /**
     * Create a new Closure that resolves to a PDO instance with a specific host or an array of hosts.
     *
     * 创建一个新的闭包，该闭包将解析为带有特定主机或主机数组的PDO实例
     *
     * @param  array  $config
     * @return \Closure
     */
    protected function createPdoResolverWithHosts(array $config)
    {
        return function () use ($config) {
            //     对给定数组进行洗牌并返回结果          将主机配置项解析为一个数组
            foreach (Arr::shuffle($hosts = $this->parseHosts($config)) as $key => $host) {
                $config['host'] = $host;

                try {
                    //      根据配置创建一个连接器实例           建立数据库连接
                    return $this->createConnector($config)->connect($config);
                } catch (PDOException $e) {
                    //                                                   确定给定的抽象类型是否已绑定
                    if (count($hosts) - 1 === $key && $this->container->bound(ExceptionHandler::class)) {
                        //从容器中解析给定类型                                报告或记录异常
                        $this->container->make(ExceptionHandler::class)->report($e);
                    }
                }
            }

            throw $e;
        };
    }

    /**
     * Parse the hosts configuration item into an array.
     *
     * 将主机配置项解析为一个数组
     *
     * @param  array  $config
     * @return array
     */
    protected function parseHosts(array $config)
    {
        //如果给定值不是数组，请将其包在一个数组中
        $hosts = array_wrap($config['host']);

        if (empty($hosts)) {
            throw new InvalidArgumentException('Database hosts array is empty.');
        }

        return $hosts;
    }

    /**
     * Create a new Closure that resolves to a PDO instance where there is no configured host.
     *
     * 创建一个新的闭包，它将解析为没有配置主机的PDO实例
     *
     * @param  array  $config
     * @return \Closure
     */
    protected function createPdoResolverWithoutHosts(array $config)
    {
        return function () use ($config) {
            //根据配置创建一个连接器实例              建立数据库连接
            return $this->createConnector($config)->connect($config);
        };
    }

    /**
     * Create a connector instance based on the configuration.
     *
     * 根据配置创建一个连接器实例
     *
     * @param  array  $config
     * @return \Illuminate\Database\Connectors\ConnectorInterface
     *
     * @throws \InvalidArgumentException
     */
    public function createConnector(array $config)
    {
        if (! isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }
        //      确定给定的抽象类型是否已绑定
        if ($this->container->bound($key = "db.connector.{$config['driver']}")) {
            return $this->container->make($key);//从容器中解析给定类型
        }

        switch ($config['driver']) {
            case 'mysql':
                return new MySqlConnector;
            case 'pgsql':
                return new PostgresConnector;
            case 'sqlite':
                return new SQLiteConnector;
            case 'sqlsrv':
                return new SqlServerConnector;
        }

        throw new InvalidArgumentException("Unsupported driver [{$config['driver']}]");
    }

    /**
     * Create a new connection instance.
	 *
	 * 创建一个新的连接实例
     *
     * @param  string   $driver
     * @param  \PDO|\Closure     $connection
     * @param  string   $database
     * @param  string   $prefix
     * @param  array    $config
     * @return \Illuminate\Database\Connection
     *
     * @throws \InvalidArgumentException
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        //             获取给定驱动程序的连接解析器
        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config);
        }

        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connection, $database, $prefix, $config);
            case 'pgsql':
                return new PostgresConnection($connection, $database, $prefix, $config);
            case 'sqlite':
                return new SQLiteConnection($connection, $database, $prefix, $config);
            case 'sqlsrv':
                return new SqlServerConnection($connection, $database, $prefix, $config);
        }

        throw new InvalidArgumentException("Unsupported driver [$driver]");
    }
}
