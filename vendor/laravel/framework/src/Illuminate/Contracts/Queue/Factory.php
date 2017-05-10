<?php

namespace Illuminate\Contracts\Queue;

interface Factory
{
    /**
     * Resolve a queue connection instance.
     *
     * 解析队列连接实例
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connection($name = null);
}
