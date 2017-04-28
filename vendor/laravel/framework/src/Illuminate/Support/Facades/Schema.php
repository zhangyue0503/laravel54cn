<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Database\Schema\Builder
 */
class Schema extends Facade
{
    /**
     * Get a schema builder instance for a connection.
     *
     * 获取连接的架构生成器实例
     *
     * @param  string  $name
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function connection($name)
    {
        //                      获取数据库连接实例      获取连接的架构生成器实例
        return static::$app['db']->connection($name)->getSchemaBuilder();
    }

    /**
     * Get a schema builder instance for the default connection.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected static function getFacadeAccessor()
    {
        //                      获取数据库连接实例      获取连接的架构生成器实例
        return static::$app['db']->connection()->getSchemaBuilder();
    }
}
