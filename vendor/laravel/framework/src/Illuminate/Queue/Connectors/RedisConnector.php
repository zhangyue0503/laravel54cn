<?php

namespace Illuminate\Queue\Connectors;

use Illuminate\Support\Arr;
use Illuminate\Queue\RedisQueue;
use Illuminate\Contracts\Redis\Factory as Redis;

class RedisConnector implements ConnectorInterface
{
    /**
     * The Redis database instance.
     *
     * Redis数据库实例
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * The connection name.
     *
     * 连接名称
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new Redis queue connector instance.
     *
     * 创建一个新的Redis队列连接器实例
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @param  string|null  $connection
     * @return void
     */
    public function __construct(Redis $redis, $connection = null)
    {
        $this->redis = $redis;
        $this->connection = $connection;
    }

    /**
     * Establish a queue connection.
     *
     * 建立一个队列连接
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        //       创建一个新的Redis队列实例
        return new RedisQueue(
            $this->redis, $config['queue'],
        //使用“点”符号从数组中获取一个项
            Arr::get($config, 'connection', $this->connection),
            Arr::get($config, 'retry_after', 60)
        );
    }
}
