<?php

namespace Illuminate\Validation\Rules;

use Closure;

class Unique
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
     * The column to check for uniqueness on.
     *
     * 用于检查惟一性的列
     *
     * @var string
     */
    protected $column;

    /**
     * The ID that should be ignored.
     *
     * 应该忽略的ID
     *
     * @var mixed
     */
    protected $ignore;

    /**
     * The name of the ID column.
     *
     * ID列的名称
     *
     * @var string
     */
    protected $idColumn = 'id';

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
     * Create a new unique rule instance.
     *
     * 创建一个新的惟一规则实例
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
     * Ignore the given ID during the unique check.
     *
     * 在惟一检查期间忽略给定的ID
     *
     * @param  mixed  $id
     * @param  string  $idColumn
     * @return $this
     */
    public function ignore($id, $idColumn = 'id')
    {
        $this->ignore = $id;
        $this->idColumn = $idColumn;

        return $this;
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
        //                          在每个项目上运行map
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
        return rtrim(sprintf('unique:%s,%s,%s,%s,%s',
            $this->table,
            $this->column,
            $this->ignore ? '"'.$this->ignore.'"' : 'NULL',
            $this->idColumn,
            $this->formatWheres()//where子句的格式
        ), ',');
    }
}
