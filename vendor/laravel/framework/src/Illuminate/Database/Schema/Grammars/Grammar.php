<?php

namespace Illuminate\Database\Schema\Grammars;

use Illuminate\Support\Fluent;
use Doctrine\DBAL\Schema\TableDiff;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Grammar as BaseGrammar;
use Doctrine\DBAL\Schema\AbstractSchemaManager as SchemaManager;

abstract class Grammar extends BaseGrammar
{
    /**
     * If this Grammar supports schema changes wrapped in a transaction.
     *
     * 如果该语法支持在事务中包装的模式更改
     *
     * @var bool
     */
    protected $transactions = false;

    /**
     * Compile a rename column command.
     *
     * 编译重命名列命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return array
     */
    public function compileRenameColumn(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        //                编译重命名列命令
        return RenameColumn::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Compile a change column command into a series of SQL statements.
     *
     * 将一个变更列命令编译成一系列SQL语句
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection $connection
     * @return array
     *
     * @throws \RuntimeException
     */
    public function compileChange(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        //              将一个变更列命令编译成一系列SQL语句
        return ChangeColumn::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Compile a foreign key command.
     *
     * 编译一个外键命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileForeign(Blueprint $blueprint, Fluent $command)
    {
        // We need to prepare several of the elements of the foreign key definition
        // before we can create the SQL, such as wrapping the tables and convert
        // an array of columns to comma-delimited strings for the SQL queries.
        //
        // 我们需要准备一些元素的外键的定义之前,我们可以创建SQL,如包装表和列的数组转换为SQL查询以逗号分隔的字符串
        //
        $sql = sprintf('alter table %s add constraint %s ',
            $this->wrapTable($blueprint),//用关键字标识包装一个表
            $this->wrap($command->index)//在关键字标识符中包装一个值
        );

        // Once we have the initial portion of the SQL statement we will add on the
        // key name, table name, and referenced columns. These will complete the
        // main portion of the SQL statement and this SQL will almost be done.
        //
        // 一旦我们有了SQL语句的初始部分，我们将添加键名、表名和引用的列
        // 这些将完成SQL语句的主要部分，并且几乎可以完成这个SQL语句
        //
        $sql .= sprintf('foreign key (%s) references %s (%s)',
            $this->columnize($command->columns),//将列名称的数组转换为分隔字符串
            $this->wrapTable($command->on),//用关键字标识包装一个表
            $this->columnize((array) $command->references)//将列名称的数组转换为分隔字符串
        );

        // Once we have the basic foreign key creation statement constructed we can
        // build out the syntax for what should happen on an update or delete of
        // the affected columns, which will get something like "cascade", etc.
        //
        // 一旦我们有了基本的外键创建语句构造我们可以构建的语法应该发生在一个更新或删除受影响的列,这将得到类似“级联”,等等
        //
        if (! is_null($command->onDelete)) {
            $sql .= " on delete {$command->onDelete}";
        }

        if (! is_null($command->onUpdate)) {
            $sql .= " on update {$command->onUpdate}";
        }

        return $sql;
    }

    /**
     * Compile the blueprint's column definitions.
     *
     * 编译blueprint的列定义
     *
     * @param  \Illuminate\Database\Schema\Blueprint $blueprint
     * @return array
     */
    protected function getColumns(Blueprint $blueprint)
    {
        $columns = [];
        //获取应该添加的蓝图中的列
        foreach ($blueprint->getAddedColumns() as $column) {
            // Each of the column types have their own compiler functions which are tasked
            // with turning the column definition into its SQL format for this platform
            // used by the connection. The column's modifiers are compiled and added.
            //
            // 每个列类型都有自己的编译器函数，它们的任务是将列定义转换为该平台使用的这个平台的SQL格式
            // 列的修饰符是编译和添加的
            //
            //        在关键字标识符中包装一个值         获取列数据类型的SQL
            $sql = $this->wrap($column).' '.$this->getType($column);
            //                    在定义中添加列修饰符
            $columns[] = $this->addModifiers($sql, $blueprint, $column);
        }

        return $columns;
    }

    /**
     * Get the SQL for the column data type.
     *
     * 获取列数据类型的SQL
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function getType(Fluent $column)
    {
        return $this->{'type'.ucfirst($column->type)}($column);
    }

    /**
     * Add the column modifiers to the definition.
     *
     * 在定义中添加列修饰符
     *
     * @param  string  $sql
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function addModifiers($sql, Blueprint $blueprint, Fluent $column)
    {
        foreach ($this->modifiers as $modifier) {
            if (method_exists($this, $method = "modify{$modifier}")) {
                $sql .= $this->{$method}($blueprint, $column);
            }
        }

        return $sql;
    }

    /**
     * Get the primary key command if it exists on the blueprint.
     *
     * 如果它存在于blueprint中，就获得主键命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  string  $name
     * @return \Illuminate\Support\Fluent|null
     */
    protected function getCommandByName(Blueprint $blueprint, $name)
    {
        //使用给定的名称获取所有命令
        $commands = $this->getCommandsByName($blueprint, $name);

        if (count($commands) > 0) {
            return reset($commands);
        }
    }

    /**
     * Get all of the commands with a given name.
     *
     * 使用给定的名称获取所有命令
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  string  $name
     * @return array
     */
    protected function getCommandsByName(Blueprint $blueprint, $name)
    {
        //                      获取蓝图上的命令
        return array_filter($blueprint->getCommands(), function ($value) use ($name) {
            return $value->name == $name;
        });
    }

    /**
     * Add a prefix to an array of values.
     *
     * 在一个值数组中添加一个前缀
     *
     * @param  string  $prefix
     * @param  array   $values
     * @return array
     */
    public function prefixArray($prefix, array $values)
    {
        return array_map(function ($value) use ($prefix) {
            return $prefix.' '.$value;
        }, $values);
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * 用关键字标识包装一个表
     *
     * @param  mixed   $table
     * @return string
     */
    public function wrapTable($table)
    {
        //在关键字标识符中包装表
        return parent::wrapTable(
            //                                   获取蓝图描述的表格
            $table instanceof Blueprint ? $table->getTable() : $table
        );
    }

    /**
     * Wrap a value in keyword identifiers.
     *
     * 在关键字标识符中包装一个值
     *
     * @param  \Illuminate\Database\Query\Expression|string  $value
     * @param  bool    $prefixAlias
     * @return string
     */
    public function wrap($value, $prefixAlias = false)
    {
        //在关键字标识符中包装值
        return parent::wrap(
            $value instanceof Fluent ? $value->name : $value, $prefixAlias
        );
    }

    /**
     * Format a value so that it can be used in "default" clauses.
     *
     * 格式化一个值，以便它可以在“缺省”子句中使用
     *
     * @param  mixed   $value
     * @return string
     */
    protected function getDefaultValue($value)
    {
        if ($value instanceof Expression) {
            return $value;
        }

        return is_bool($value)
                    ? "'".(int) $value."'"
                    : "'".strval($value)."'";
    }

    /**
     * Create an empty Doctrine DBAL TableDiff from the Blueprint.
     *
     * 从蓝图中创建一个空的Doctrine DBAL TableDiff
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Doctrine\DBAL\Schema\AbstractSchemaManager  $schema
     * @return \Doctrine\DBAL\Schema\TableDiff
     */
    public function getDoctrineTableDiff(Blueprint $blueprint, SchemaManager $schema)
    {
        //          获取语法的表前缀              获取蓝图描述的表格
        $table = $this->getTablePrefix().$blueprint->getTable();
        //用给定的值调用给定的闭包，然后返回值
        return tap(new TableDiff($table), function ($tableDiff) use ($schema, $table) {
            $tableDiff->fromTable = $schema->listTableDetails($table);
        });
    }

    /**
     * Check if this Grammar supports schema changes wrapped in a transaction.
     *
     * 检查该语法是否支持在事务中包装的模式更改
     *
     * @return bool
     */
    public function supportsSchemaTransactions()
    {
        return $this->transactions;
    }
}
