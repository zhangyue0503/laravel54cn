<?php

namespace Illuminate\Validation\Rules;

use Closure;

class Exists
{
    /**
     * The table to run the query against.
     *
     * 用于运行查询的表
     *
     * @var string
     */
    protected $table;

    /**
     * The column to check for existence on.
     *
     * 用来检查是否存在的列
     *
     * @var string
     */
    protected $column;

    /**
     * There extra where clauses for the query.
     *
     * 查询中有额外的子句
     *
     * @var array
     */
    protected $wheres = [];

    /**
     * The custom query callback.
     *
     * 自定义查询回调
     *
     * @var \Closure|null
     */
    protected $using;

    /**
     * Create a new exists rule instance.
     *
     * 创建一个新的存在规则实例
     *
     * @param  string  $table
     * @param  string  $column
     * @return void
     */
    public function __construct($table, $column = 'NULL')
    {
        $this->table = $table;
        $this->column = $column;
    }

    /**
     * Set a "where" constraint on the query.
     *
     * 在查询中设置“where”约束
     *
     * @param  string  $column
     * @param  string  $value
     * @return $this
     */
    public function where($column, $value = null)
    {
        if ($column instanceof Closure) {
            //注册一个定制的查询回调
            return $this->using($column);
        }

        $this->wheres[] = compact('column', 'value');

        return $this;
    }

    /**
     * Set a "where not" constraint on the query.
     *
     * 在查询中设置“where not”约束
     *
     * @param  string  $column
     * @param  string  $value
     * @return $this
     */
    public function whereNot($column, $value)
    {
        //在查询中设置“where”约束
        return $this->where($column, '!'.$value);
    }

    /**
     * Set a "where null" constraint on the query.
     *
     * 在查询中设置“where null”约束
     *
     * @param  string  $column
     * @return $this
     */
    public function whereNull($column)
    {
        //在查询中设置“where”约束
        return $this->where($column, 'NULL');
    }

    /**
     * Set a "where not null" constraint on the query.
     *
     * 在查询中设置“where not null”约束
     *
     * @param  string  $column
     * @return $this
     */
    public function whereNotNull($column)
    {
        //在查询中设置“where”约束
        return $this->where($column, 'NOT_NULL');
    }

    /**
     * Register a custom query callback.
     *
     * 注册一个定制的查询回调
     *
     * @param  \Closure $callback
     * @return $this
     */
    public function using(Closure $callback)
    {
        $this->using = $callback;

        return $this;
    }

    /**
     * Format the where clauses.
     *
     * where子句的格式
     *
     * @return string
     */
    protected function formatWheres()
    {
        //                         在每个项目上运行map
        return collect($this->wheres)->map(function ($where) {
            return $where['column'].','.$where['value'];
        })->implode(',');//一个给定的键连接的值作为一个字符串
    }

    /**
     * Get the custom query callbacks for the rule.
     *
     * 获取规则的自定义查询回调
     *
     * @return array
     */
    public function queryCallbacks()
    {
        return $this->using ? [$this->using] : [];
    }

    /**
     * Convert the rule to a validation string.
     *
     * 将规则转换为验证字符串
     *
     * @return string
     */
    public function __toString()
    {
        return rtrim(sprintf('exists:%s,%s,%s',
            $this->table,
            $this->column,
            $this->formatWheres()//where子句的格式
        ), ',');
    }
}
