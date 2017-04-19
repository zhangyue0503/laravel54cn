<?php

namespace Illuminate\Database;

use Illuminate\Database\Query\Expression;

abstract class Grammar
{
    /**
     * The grammar table prefix.
     *
     * 表名前缀
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * Wrap an array of values.
     *
     * 包装数组的值
     *
     * @param  array  $values
     * @return array
     */
    public function wrapArray(array $values)
    {
        //                   $this->warp 在关键字标识符中包装值
        return array_map([$this, 'wrap'], $values);
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * 在关键字标识符中包装表
     *
     * @param  \Illuminate\Database\Query\Expression|string  $table
     * @return string
     */
    public function wrapTable($table)
    {
        if (! $this->isExpression($table)) { //确定给定值是否为原始表达式
            return $this->wrap($this->tablePrefix.$table, true); //在关键字标识符中包装值
        }

        return $this->getValue($table); //获取原始表达式的值
    }

    /**
     * Wrap a value in keyword identifiers.
     *
     * 在关键字标识符中包装值
     *
     * @param  \Illuminate\Database\Query\Expression|string  $value
     * @param  bool    $prefixAlias
     * @return string
     */
    public function wrap($value, $prefixAlias = false)
    {
        if ($this->isExpression($value)) {//确定给定值是否为原始表达式
            return $this->getValue($value);//获取原始表达式的值
        }

        // If the value being wrapped has a column alias we will need to separate out
        // the pieces so we can wrap each of the segments of the expression on it
        // own, and then joins them both back together with the "as" connector.
        //
        // 如果被包裹的值有一个列别名，我们将需要分离出这些片断，这样我们就可以将表达式的每个片段包起来，然后将它们与“作为”连接器一起返回
        //
        if (strpos(strtolower($value), ' as ') !== false) {
            return $this->wrapAliasedValue($value, $prefixAlias); // 包装一个有别名的值
        }

        return $this->wrapSegments(explode('.', $value)); // 包装给定值片段
    }

    /**
     * Wrap a value that has an alias.
     *
     * 包装一个有别名的值
     *
     * @param  string  $value
     * @param  bool  $prefixAlias
     * @return string
     */
    protected function wrapAliasedValue($value, $prefixAlias = false)
    {
        $segments = preg_split('/\s+as\s+/i', $value);

        // If we are wrapping a table we need to prefix the alias with the table prefix
        // as well in order to generate proper syntax. If this is a column of course
        // no prefix is necessary. The condition will be true when from wrapTable.
        //
        // 如果我们正在包装一个表，我们需要前缀前缀与表前缀，以及为了产生适当的语法
        // 如果这是一个列当然没有前缀是必然的
        // 当来自wrapTable时条件为真正
        //
        if ($prefixAlias) {
            $segments[1] = $this->tablePrefix.$segments[1];
        }

        return $this->wrap( // 在关键字标识符中包装值
            $segments[0]).' as '.$this->wrapValue($segments[1] //在关键字标识符中包装单个字符串
        );
    }

    /**
     * Wrap the given value segments.
     *
     * 包装给定值片段
     *
     * @param  array  $segments
     * @return string
     */
    protected function wrapSegments($segments)
    {
        //                         在每个项目上运行map
        return collect($segments)->map(function ($segment, $key) use ($segments) {
            return $key == 0 && count($segments) > 1
                            ? $this->wrapTable($segment) //在关键字标识符中包装表
                            : $this->wrapValue($segment);//在关键字标识符中包装单个字符串
        })->implode('.'); //一个给定的键连接的值作为一个字符串
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * 在关键字标识符中包装单个字符串
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value !== '*') {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }

    /**
     * Convert an array of column names into a delimited string.
     *
     * 将列名称的数组转换为分隔字符串
     *
     * @param  array   $columns
     * @return string
     */
    public function columnize(array $columns)
    {
        return implode(', ', array_map([$this, 'wrap'], $columns));
    }

    /**
     * Create query parameter place-holders for an array.
     *
     * 为数组创建查询参数占位符
     *
     * @param  array   $values
     * @return string
     */
    public function parameterize(array $values)
    {
        return implode(', ', array_map([$this, 'parameter'], $values));
    }

    /**
     * Get the appropriate query parameter place-holder for a value.
     *
     * 获取适当的查询参数占位符
     *
     * @param  mixed   $value
     * @return string
     */
    public function parameter($value)
    {
        //       确定给定值是否为原始表达式         获取原始表达式的值
        return $this->isExpression($value) ? $this->getValue($value) : '?';
    }

    /**
     * Determine if the given value is a raw expression.
     *
     * 确定给定值是否为原始表达式
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isExpression($value)
    {
        return $value instanceof Expression;
    }

    /**
     * Get the value of a raw expression.
     *
     * 获取原始表达式的值
     *
     * @param  \Illuminate\Database\Query\Expression  $expression
     * @return string
     */
    public function getValue($expression)
    {
        return $expression->getValue(); // 得到表达式的值
    }

    /**
     * Get the format for database stored dates.
     *
     * 获取数据库存储日期的格式
     *
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Get the grammar's table prefix.
     *
     * 获取语法的表前缀
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Set the grammar's table prefix.
     *
     * 设置语法的表前缀
     *
     * @param  string  $prefix
     * @return $this
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;

        return $this;
    }
}
