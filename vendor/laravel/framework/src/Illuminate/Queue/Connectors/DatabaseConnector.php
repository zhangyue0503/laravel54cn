<?php

namespace Illuminate\Queue\Connectors;

use Illuminate\Support\Arr;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseConnector implements ConnectorInterface
{
    /**
     * Database connections.
     *
     * 数据库连接
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $connections;

    /**
     * Create a new connector instance.
     *
     * 创建一个新的连接器实例
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $connections
     * @return void
     */
    public function __construct(ConnectionResolverInterface $connections)
    {
        $this->connections = $connections;
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
        //    创建一个新的数据库队列实例
        return new DatabaseQueue(
            //              获取一个数据连接实例    使用“点”符号从数组中获取一个项
            $this->connections->connection(Arr::get($config, 'connection')),
            $config['table'],
            $config['queue'],
        //使用“点”符号从数组中获取一个项
            Arr::get($config, 'retry_after', 60)
        );
    }
}
