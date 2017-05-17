<?php

namespace Illuminate\Database\Schema;

class MySqlBuilder extends Builder
{
    /**
     * Determine if the given table exists.
     *
     * 确定给定的表是否存在
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        //                          获取连接的表前缀
        $table = $this->connection->getTablePrefix().$table;
        //                            对数据库运行SELECT语句
        return count($this->connection->select(
            //           编译查询以确定表的列表                   获取连接数据库的名称
            $this->grammar->compileTableExists(), [$this->connection->getDatabaseName(), $table]
        )) > 0;
    }

    /**
     * Get the column listing for a given table.
     *
     * 获取给定表的列清单
     *
     * @param  string  $table
     * @return array
     */
    public function getColumnListing($table)
    {
        //                          获取连接的表前缀
        $table = $this->connection->getTablePrefix().$table;
        //                            对数据库运行SELECT语句
        $results = $this->connection->select(
            //          编译查询以确定列的列表                   获取连接数据库的名称
            $this->grammar->compileColumnListing(), [$this->connection->getDatabaseName(), $table]
        );
        //                 获取连接所使用的查询后处理器      处理列清单查询的结果
        return $this->connection->getPostProcessor()->processColumnListing($results);
    }
}
