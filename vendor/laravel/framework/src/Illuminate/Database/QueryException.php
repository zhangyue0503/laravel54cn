<?php

namespace Illuminate\Database;

use PDOException;
use Illuminate\Support\Str;

class QueryException extends PDOException
{
    /**
     * The SQL for the query.
     *
     * 查询SQL
     *
     * @var string
     */
    protected $sql;

    /**
     * The bindings for the query.
     *
     * 查询绑定
     *
     * @var array
     */
    protected $bindings;

    /**
     * Create a new query exception instance.
     *
     * 创建一个新的查询异常实例
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  \Exception $previous
     * @return void
     */
    public function __construct($sql, array $bindings, $previous)
    {
        parent::__construct('', 0, $previous);

        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->code = $previous->getCode();
        //                   格式化SQL错误信息
        $this->message = $this->formatMessage($sql, $bindings, $previous);

        if ($previous instanceof PDOException) {
            $this->errorInfo = $previous->errorInfo;
        }
    }

    /**
     * Format the SQL error message.
     *
     * 格式化SQL错误信息
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  \Exception $previous
     * @return string
     */
    protected function formatMessage($sql, $bindings, $previous)
    {
        //                                            用数组顺序替换字符串中的给定值
        return $previous->getMessage().' (SQL: '.Str::replaceArray('?', $bindings, $sql).')';
    }

    /**
     * Get the SQL for the query.
     *
     * 获取查询的sql
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Get the bindings for the query.
     *
     * 获取查询的绑定
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }
}
