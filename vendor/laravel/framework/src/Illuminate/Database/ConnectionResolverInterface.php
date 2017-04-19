<?php

namespace Illuminate\Database;

interface ConnectionResolverInterface
{
    /**
     * Get a database connection instance.
     *
     * 获取一个数据连接实例
     *
     * @param  string  $name
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function connection($name = null);

    /**
     * Get the default connection name.
     *
     * 获取默认的连接名称
     *
     * @return string
     */
    public function getDefaultConnection();

    /**
     * Set the default connection name.
     *
     * 设置默认的连接名称
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name);
}
