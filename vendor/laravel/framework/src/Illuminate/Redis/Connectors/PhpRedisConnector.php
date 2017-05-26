<?php

namespace Illuminate\Redis\Connectors;

use Redis;
use RedisCluster;
use Illuminate\Support\Arr;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;

class PhpRedisConnector
{
    /**
     * Create a new clustered Predis connection.
     *
     * 创建一个新的集群Predis连接
     *
     * @param  array  $config
     * @param  array  $options
     * @return \Illuminate\Redis\PhpRedisConnection
     */
    public function connect(array $config, array $options)
    {
        //创建一个新的Predis连接               创建Redis客户端实例
        return new PhpRedisConnection($this->createClient(array_merge(
            //                 从数组中获取值，并将其移除
            $config, $options, Arr::pull($config, 'options', [])
        )));
    }

    /**
     * Create a new clustered Predis connection.
     *
     * 创建一个新的集群Predis连接
     *
     * @param  array  $config
     * @param  array  $clusterOptions
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\PhpRedisClusterConnection
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        //                                               从数组中获取值，并将其移除
        $options = array_merge($options, $clusterOptions, Arr::pull($config, 'options', []));
        //                                     创建一个新的redis集群实例
        return new PhpRedisClusterConnection($this->createRedisClusterInstance(
            array_map([$this, 'buildClusterConnectionString'], $config), $options
        ));
    }

    /**
     * Build a single cluster seed string from array.
     *
     * 从数组中构建一个单一的集群种子字符串
     *
     * @param  array  $server
     * @return string
     */
    protected function buildClusterConnectionString(array $server)
    {
        //                                                              从给定数组中获取项目的子集
        return $server['host'].':'.$server['port'].'?'.http_build_query(Arr::only($server, [
            'database', 'password', 'prefix', 'read_timeout',
        ]));
    }

    /**
     * Create the Redis client instance.
     *
     * 创建Redis客户端实例
     *
     * @param  array  $config
     * @return \Redis
     */
    protected function createClient(array $config)
    {
        //用给定的值调用给定的闭包，然后返回值
        return tap(new Redis, function ($client) use ($config) {
            $this->establishConnection($client, $config);//与Redis主机建立连接

            if (! empty($config['password'])) {
                $client->auth($config['password']);
            }

            if (! empty($config['database'])) {
                $client->select($config['database']);
            }

            if (! empty($config['prefix'])) {
                $client->setOption(Redis::OPT_PREFIX, $config['prefix']);
            }

            if (! empty($config['read_timeout'])) {
                $client->setOption(Redis::OPT_READ_TIMEOUT, $config['read_timeout']);
            }
        });
    }

    /**
     * Establish a connection with the Redis host.
     *
     * 与Redis主机建立连接
     *
     * @param  \Redis  $client
     * @param  array  $config
     * @return void
     */
    protected function establishConnection($client, array $config)
    {
        //使用“点”符号从数组中获取一个项
        $client->{Arr::get($config, 'persistent', false) === true ? 'pconnect' : 'connect'}(
            $config['host'], $config['port'], Arr::get($config, 'timeout', 0)
        );
    }

    /**
     * Create a new redis cluster instance.
     *
     * 创建一个新的redis集群实例
     *
     * @param  array  $servers
     * @param  array  $options
     * @return \RedisCluster
     */
    protected function createRedisClusterInstance(array $servers, array $options)
    {
        return new RedisCluster(
            null,
            array_values($servers),
            Arr::get($options, 'timeout', 0),//使用“点”符号从数组中获取一个项
            Arr::get($options, 'read_timeout', 0),
            isset($options['persistent']) && $options['persistent']
        );
    }
}
