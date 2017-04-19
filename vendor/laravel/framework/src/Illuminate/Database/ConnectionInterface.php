<?php

namespace Illuminate\Database;

use Closure;

interface ConnectionInterface
{
    /**
     * Begin a fluent query against a database table.
     *
     * 对数据库表开始一个链式的查询
     *
     * @param  string  $table
     * @return \Illuminate\Database\Query\Builder
     */
    public function table($table);

    /**
     * Get a new raw query expression.
     *
     * 获取新的原始查询表达式
     *
     * @param  mixed  $value
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value);

    /**
     * Run a select statement and return a single result.
     *
     * 运行SELECT语句并返回单个结果
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return mixed
     */
    public function selectOne($query, $bindings = []);

    /**
     * Run a select statement against the database.
     *
     * 对数据库运行SELECT语句
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return array
     */
    public function select($query, $bindings = []);

    /**
     * Run an insert statement against the database.
     *
     * 对数据库运行INSERT语句
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function insert($query, $bindings = []);

    /**
     * Run an update statement against the database.
     *
     * 对数据库运行UPDATE语句
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function update($query, $bindings = []);

    /**
     * Run a delete statement against the database.
     *
     * 对数据库运行DELETE语句
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function delete($query, $bindings = []);

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * 执行SQL语句并返回布尔结果
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function statement($query, $bindings = []);

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * 执行SQL语句并返回影响行数
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = []);

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * 运行一个原始语句，准备对PDO连接查询
     *
     * @param  string  $query
     * @return bool
     */
    public function unprepared($query);

    /**
     * Prepare the query bindings for execution.
     *
     * 为执行准备查询绑定
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings);

    /**
     * Execute a Closure within a transaction.
     *
     * 在事务中执行闭包
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(Closure $callback, $attempts = 1);

    /**
     * Start a new database transaction.
     *
     * 启动一个新的数据库事务
     *
     * @return void
     */
    public function beginTransaction();

    /**
     * Commit the active database transaction.
     *
     * 提交活动数据库事务
     *
     * @return void
     */
    public function commit();

    /**
     * Rollback the active database transaction.
     *
     * 回滚活动数据库事务
     *
     * @return void
     */
    public function rollBack();

    /**
     * Get the number of active transactions.
     *
     * 获取活动事务数
     *
     * @return int
     */
    public function transactionLevel();

    /**
     * Execute the given callback in "dry run" mode.
     *
     * 在“干运行”模式下执行给定回调
     *
     * @param  \Closure  $callback
     * @return array
     */
    public function pretend(Closure $callback);
}
