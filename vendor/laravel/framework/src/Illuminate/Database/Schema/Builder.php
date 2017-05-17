<?php

namespace Illuminate\Database\Schema;

use Closure;
use Illuminate\Database\Connection;

class Builder
{
    /**
     * The database connection instance.
     *
     * 数据库连接实例
     *
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * The schema grammar instance.
     *
     * 模式语法实例
     *
     * @var \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected $grammar;

    /**
     * The Blueprint resolver callback.
     *
     * Blueprint解析器回调
     *
     * @var \Closure
     */
    protected $resolver;

    /**
     * The default string length for migrations.
     *
     * 迁移的默认字符串长度
     *
     * @var int
     */
    public static $defaultStringLength = 255;

    /**
     * Create a new database Schema manager.
     *
     * 创建新的数据库架构管理器
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        //                      获取连接使用的查询语法
        $this->grammar = $connection->getSchemaGrammar();
    }

    /**
     * Set the default string length for migrations.
     *
     * 为迁移设置默认的字符串长度
     *
     * @param  int  $length
     * @return void
     */
    public static function defaultStringLength($length)
    {
        static::$defaultStringLength = $length;
    }

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
        //                      获取连接的表前缀
        $table = $this->connection->getTablePrefix().$table;
        //                        对数据库运行SELECT语句
        return count($this->connection->select(
            //            编译查询以确定表的列表
            $this->grammar->compileTableExists(), [$table]
        )) > 0;
    }

    /**
     * Determine if the given table has a given column.
     *
     * 确定给定的表是否有给定的列
     *
     * @param  string  $table
     * @param  string  $column
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        return in_array(
            //                                             获取给定表的列清单
            strtolower($column), array_map('strtolower', $this->getColumnListing($table))
        );
    }

    /**
     * Determine if the given table has given columns.
     *
     * 确定给定的表是否给出了列
     *
     * @param  string  $table
     * @param  array   $columns
     * @return bool
     */
    public function hasColumns($table, array $columns)
    {
        //                                       获取给定表的列清单
        $tableColumns = array_map('strtolower', $this->getColumnListing($table));

        foreach ($columns as $column) {
            if (! in_array(strtolower($column), $tableColumns)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the data type for the given column name.
     *
     * 获取给定列名的数据类型
     *
     * @param  string  $table
     * @param  string  $column
     * @return string
     */
    public function getColumnType($table, $column)
    {
        //                        获取连接的表前缀
        $table = $this->connection->getTablePrefix().$table;
        //                获取Doctrine模式列实例
        return $this->connection->getDoctrineColumn($table, $column)->getType()->getName();
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
        //                        获取连接的表前缀
        $table = $this->connection->getTablePrefix().$table;
        //                      对数据库运行SELECT语句           编译查询以确定列的列表
        $results = $this->connection->select($this->grammar->compileColumnListing($table));
        //               获取连接所使用的查询后处理器          处理列清单查询的结果
        return $this->connection->getPostProcessor()->processColumnListing($results);
    }

    /**
     * Modify a table on the schema.
     *
     * 修改模式中的表
     *
     * @param  string    $table
     * @param  \Closure  $callback
     * @return void
     */
    public function table($table, Closure $callback)
    {
        //执行构建/修改表的蓝图  创建一个带有闭包的新命令集
        $this->build($this->createBlueprint($table, $callback));
    }

    /**
     * Create a new table on the schema.
     *
     * 在模式上创建一个新表
     *
     * @param  string    $table
     * @param  \Closure  $callback
     * @return void
     */
    public function create($table, Closure $callback)
    {
        //执行构建/修改表的蓝图  用给定的值调用给定的闭包，然后返回值(创建一个带有闭包的新命令集,)
        $this->build(tap($this->createBlueprint($table), function ($blueprint) use ($callback) {
            $blueprint->create();//表示需要创建表

            $callback($blueprint);
        }));
    }

    /**
     * Drop a table from the schema.
     * 从模式中删除一个表
     *
     *
     * @param  string  $table
     * @return void
     */
    public function drop($table)
    {
        //执行构建/修改表的蓝图  用给定的值调用给定的闭包，然后返回值(创建一个带有闭包的新命令集,)
        $this->build(tap($this->createBlueprint($table), function ($blueprint) {
            $blueprint->drop();//表示应该删除表
        }));
    }

    /**
     * Drop a table from the schema if it exists.
     *
     * @param  string  $table
     * @return void
     */
    public function dropIfExists($table)
    {
        //执行构建/修改表的蓝图  用给定的值调用给定的闭包，然后返回值(创建一个带有闭包的新命令集,)
        $this->build(tap($this->createBlueprint($table), function ($blueprint) {
            $blueprint->dropIfExists();//表示如果存在表，就应该删除该表
        }));
    }

    /**
     * Rename a table on the schema.
     *
     * 在模式中重命名一个表
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    public function rename($from, $to)
    {
        //执行构建/修改表的蓝图  用给定的值调用给定的闭包，然后返回值(创建一个带有闭包的新命令集,)
        $this->build(tap($this->createBlueprint($from), function ($blueprint) use ($to) {
            $blueprint->rename($to);//将表重命名为给定的名称
        }));
    }

    /**
     * Enable foreign key constraints.
     *
     * 启用外键约束
     *
     * @return bool
     */
    public function enableForeignKeyConstraints()
    {
        //                        执行SQL语句并返回布尔结果
        return $this->connection->statement(
            //               编译命令以启用外键约束
            $this->grammar->compileEnableForeignKeyConstraints()
        );
    }

    /**
     * Disable foreign key constraints.
     *
     * 禁用外键约束
     *
     * @return bool
     */
    public function disableForeignKeyConstraints()
    {
        //                        执行SQL语句并返回布尔结果
        return $this->connection->statement(
            //                 编译命令以禁用外键约束
            $this->grammar->compileDisableForeignKeyConstraints()
        );
    }

    /**
     * Execute the blueprint to build / modify the table.
     *
     * 执行构建/修改表的蓝图
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @return void
     */
    protected function build(Blueprint $blueprint)
    {
        //对数据库执行蓝图
        $blueprint->build($this->connection, $this->grammar);
    }

    /**
     * Create a new command set with a Closure.
     *
     * 创建一个带有闭包的新命令集
     *
     * @param  string  $table
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Schema\Blueprint
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $table, $callback);
        }

        return new Blueprint($table, $callback);
    }

    /**
     * Get the database connection instance.
     *
     * 获取数据库连接实例
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the database connection instance.
     *
     * 设置数据库链接实例
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the Schema Blueprint resolver callback.
     *
     * 设置模式蓝图解析器回调
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public function blueprintResolver(Closure $resolver)
    {
        $this->resolver = $resolver;
    }
}
