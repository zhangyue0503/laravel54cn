<?php

namespace Illuminate\Database\Query\Processors;

use Illuminate\Database\Query\Builder;
//处理器
class Processor
{
    /**
     * Process the results of a "select" query.
	 *
	 * 处理“select”查询的结果
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $results
     * @return array
     */
    public function processSelect(Builder $query, $results)
    {
        return $results;
    }

    /**
     * Process an  "insert get ID" query.
     *
     * 处理“插入获取ID”查询
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array   $values
     * @param  string  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        //获取数据库链接实例           对数据库运行INSERT语句
        $query->getConnection()->insert($sql, $values);
        //   获取数据库链接实例          获取当前的PDO连接
        $id = $query->getConnection()->getPdo()->lastInsertId($sequence);

        return is_numeric($id) ? (int) $id : $id;
    }

    /**
     * Process the results of a column listing query.
     *
     * 处理列清单查询的结果
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        return $results;
    }
}
