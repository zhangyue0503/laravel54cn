<?php

namespace Illuminate\Database\Migrations;

abstract class Migration
{
    /**
     * The name of the database connection to use.
     *
     * 要使用的数据库连接的名称
     *
     * @var string
     */
    protected $connection;

    /**
     * Get the migration connection name.
     *
     * 获取迁移连接名称
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
