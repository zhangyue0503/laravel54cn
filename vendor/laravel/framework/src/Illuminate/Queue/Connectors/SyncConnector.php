<?php

namespace Illuminate\Queue\Connectors;

use Illuminate\Queue\SyncQueue;

class SyncConnector implements ConnectorInterface
{
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
        return new SyncQueue;//同步队列
    }
}
