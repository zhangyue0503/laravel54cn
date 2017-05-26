<?php

namespace Illuminate\Redis;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Contracts\Redis\Factory;

class RedisManager implements Factory
{
    /**
     * The name of the default driver.
     *
     * 默认驱动程序的名称
     *
     * @var string
     */
    protected $driver;

    /**
     * The Redis server configurations.
     *
     * Redis服务器配置
     *
     * @var array
     */
    protected $config;

    /**
     * The Redis connections.
     *
     * Redis连接
     *
     * @var mixed
     */
    protected $connections;

    /**
     * Create a new Redis manager instance.
	 *
	 * 创建一个新的Redis管理实例
     *
     * @param  string  $driver
     * @param  array  $config
     */
    public function __construct($driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config;
    }

    /**
     * Get a Redis connection by name.
	 *
	 * 根据名字获取一个Redis连接
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connection($name = null)
    {
        $name = $name ?: 'default';

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }
        //                                   通过名称解析给定的连接
        return $this->connections[$name] = $this->resolve($name);
    }

    /**
     * Resolve the given connection by name.
     *
     * 通过名称解析给定的连接
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Connections\Connection
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        //使用“点”符号从数组中获取一个项
        $options = Arr::get($this->config, 'options', []);

        if (isset($this->config[$name])) {
            //获取当前驱动程序的连接器实例   创建连接
            return $this->connector()->connect($this->config[$name], $options);
        }

        if (isset($this->config['clusters'][$name])) {
            //通过名称解析给定的集群连接
            return $this->resolveCluster($name);
        }

        throw new InvalidArgumentException(
            "Redis connection [{$name}] not configured."
        );
    }

    /**
     * Resolve the given cluster connection by name.
     *
     * 通过名称解析给定的集群连接
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function resolveCluster($name)
    {
        //使用“点”符号从数组中获取一个项
        $clusterOptions = Arr::get($this->config, 'clusters.options', []);
        //获取当前驱动程序的连接器实例   创建集群连接
        return $this->connector()->connectToCluster(
            $this->config['clusters'][$name], $clusterOptions, Arr::get($this->config, 'options', [])
        );
    }

    /**
     * Get the connector instance for the current driver.
     *
     * 获取当前驱动程序的连接器实例
     *
     * @return \Illuminate\Redis\Connectors\PhpRedisConnector|\Illuminate\Redis\Connectors\PredisConnector
     */
    protected function connector()
    {
        switch ($this->driver) {
            case 'predis':
                return new Connectors\PredisConnector;
            case 'phpredis':
                return new Connectors\PhpRedisConnector;
        }
    }

    /**
     * Pass methods onto the default Redis connection.
     *
     * 将方法传递到默认的Redis连接
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        //根据名字获取一个Redis连接
        return $this->connection()->{$method}(...$parameters);
    }
}
