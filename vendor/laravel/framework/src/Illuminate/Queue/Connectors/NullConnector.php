<?php

namespace Illuminate\Queue\Connectors;

use Illuminate\Queue\NullQueue;

class NullConnector implements ConnectorInterface
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
        return new NullQueue;//空队列
    }
}
