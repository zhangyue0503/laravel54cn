<?php

namespace Illuminate\Validation;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Database\ConnectionResolverInterface;

class DatabasePresenceVerifier implements PresenceVerifierInterface
{
    /**
     * The database connection instance.
     *
     * 数据库连接实例
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $db;

    /**
     * The database connection to use.
     *
     * 使用数据库连接
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new database presence verifier.
     *
     * 创建一个新的数据库存在验证器
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $db
     * @return void
     */
    public function __construct(ConnectionResolverInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Count the number of objects in a collection having the given value.
     *
     * 计算具有给定值的集合中的对象的数量
     *
     * @param  string  $collection
     * @param  string  $column
     * @param  string  $value
     * @param  int     $excludeId
     * @param  string  $idColumn
     * @param  array   $extra
     * @return int
     */
    public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null, array $extra = [])
    {
        //为给定的表获取一个查询构建器       将基本WHERE子句添加到查询中
        $query = $this->table($collection)->where($column, '=', $value);

        if (! is_null($excludeId) && $excludeId != 'NULL') {
            $query->where($idColumn ?: 'id', '<>', $excludeId);
        }
        //     将给定的条件添加到查询中         检索查询的“count”结果
        return $this->addConditions($query, $extra)->count();
    }

    /**
     * Count the number of objects in a collection with the given values.
     *
     * 用给定的值计算一个集合中的对象的数量
     *
     * @param  string  $collection
     * @param  string  $column
     * @param  array   $values
     * @param  array   $extra
     * @return int
     */
    public function getMultiCount($collection, $column, array $values, array $extra = [])
    {
        //为给定的表获取一个查询构建器       在查询中添加“where in”子句
        $query = $this->table($collection)->whereIn($column, $values);
        //     将给定的条件添加到查询中         检索查询的“count”结果
        return $this->addConditions($query, $extra)->count();
    }

    /**
     * Add the given conditions to the query.
     *
     * 将给定的条件添加到查询中
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $conditions
     * @return \Illuminate\Database\Query\Builder
     */
    protected function addConditions($query, $conditions)
    {
        foreach ($conditions as $key => $value) {
            if ($value instanceof Closure) {
                //将基本WHERE子句添加到查询中
                $query->where(function ($query) use ($value) {
                    $value($query);
                });
            } else {
                //在给定的查询中添加“where”子句
                $this->addWhere($query, $key, $value);
            }
        }

        return $query;
    }

    /**
     * Add a "where" clause to the given query.
     *
     * 在给定的查询中添加“where”子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $key
     * @param  string  $extraValue
     * @return void
     */
    protected function addWhere($query, $key, $extraValue)
    {
        if ($extraValue === 'NULL') {
            $query->whereNull($key);//向查询添加“where null”子句
        } elseif ($extraValue === 'NOT_NULL') {
            $query->whereNotNull($key);//向查询添加“where not null”子句
        } elseif (Str::startsWith($extraValue, '!')) {
            $query->where($key, '!=', mb_substr($extraValue, 1));//将基本WHERE子句添加到查询中
        } else {
            $query->where($key, $extraValue);
        }
    }

    /**
     * Get a query builder for the given table.
     *
     * 为给定的表获取一个查询构建器
     *
     * @param  string  $table
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table($table)
    {
        //获取一个数据连接实例          对数据库表开始一个链式的查询       用写的PDO的查询
        return $this->db->connection($this->connection)->table($table)->useWritePdo();
    }

    /**
     * Set the connection to be used.
     *
     * 设置要使用的连接
     *
     * @param  string  $connection
     * @return void
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }
}
