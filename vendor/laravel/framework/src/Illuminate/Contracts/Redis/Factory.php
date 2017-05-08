<?php

namespace Illuminate\Contracts\Redis;

interface Factory
{
    /**
     * Get a Redis connection by name.
     *
     * 通过名称获得一个Redis连接
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connection($name = null);
}
