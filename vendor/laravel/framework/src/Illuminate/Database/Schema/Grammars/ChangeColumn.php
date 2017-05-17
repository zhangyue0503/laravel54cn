<?php

namespace Illuminate\Database\Schema\Grammars;

use RuntimeException;
use Doctrine\DBAL\Types\Type;
use Illuminate\Support\Fluent;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Illuminate\Database\Schema\Blueprint;
use Doctrine\DBAL\Schema\AbstractSchemaManager as SchemaManager;

class ChangeColumn
{
    /**
     * Compile a change column command into a series of SQL statements.
     *
     * 将一个变更列命令编译成一系列SQL语句
     *
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection $connection
     * @return array
     *
     * @throws \RuntimeException
     */
    public static function compile($grammar, Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        //Doctrine是可用的？
        if (! $connection->isDoctrineAvailable()) {
            throw new RuntimeException(sprintf(
                'Changing columns for table "%s" requires Doctrine DBAL; install "doctrine/dbal".',
                $blueprint->getTable()//获取蓝图描述的表格
            ));
        }
        //            对于给定的变化，获取原则表的不同
        $tableDiff = static::getChangedDiff(
            //                                     获取连接的Doctrine DBAL架构管理器
            $grammar, $blueprint, $schema = $connection->getDoctrineSchemaManager()
        );

        if ($tableDiff !== false) {
            return (array) $schema->getDatabasePlatform()->getAlterTableSQL($tableDiff);
        }

        return [];
    }

    /**
     * Get the Doctrine table difference for the given changes.
     *
     * 对于给定的变化，获取原则表的不同
     *
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Doctrine\DBAL\Schema\AbstractSchemaManager  $schema
     * @return \Doctrine\DBAL\Schema\TableDiff|bool
     */
    protected static function getChangedDiff($grammar, Blueprint $blueprint, SchemaManager $schema)
    {
        //                                    获取语法的表前缀                   获取蓝图描述的表格
        $current = $schema->listTableDetails($grammar->getTablePrefix().$blueprint->getTable());

        return (new Comparator)->diffTable(
            //在列更改之后获取给定的教条表的副本
            $current, static::getTableWithColumnChanges($blueprint, $current)
        );
    }

    /**
     * Get a copy of the given Doctrine table after making the column changes.
     *
     * 在列更改之后获取给定的教条表的副本
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Doctrine\DBAL\Schema\Table  $table
     * @return \Doctrine\DBAL\Schema\Table
     */
    protected static function getTableWithColumnChanges(Blueprint $blueprint, Table $table)
    {
        $table = clone $table;
        //              获取应该更改的蓝图中的列
        foreach ($blueprint->getChangedColumns() as $fluent) {
            //             获取列更改的原则列实例
            $column = static::getDoctrineColumn($table, $fluent);

            // Here we will spin through each fluent column definition and map it to the proper
            // Doctrine column definitions - which is necessary because Laravel and Doctrine
            // use some different terminology for various column attributes on the tables.
            //
            // 这里我们将通过每个自旋流利的列定义并将其映射到适当的列定义原则——这是必要的,因为Laravel和教义不同的列属性的使用一些不同的术语表
            //
            foreach ($fluent->getAttributes() as $key => $value) {
                //                       为给定的流利属性名获取匹配原则选项
                if (! is_null($option = static::mapFluentOptionToDoctrine($key))) {
                    if (method_exists($column, $method = 'set'.ucfirst($option))) {
                        //                    获得一个给定的流利属性的匹配原则值
                        $column->{$method}(static::mapFluentValueToDoctrine($option, $value));
                    }
                }
            }
        }

        return $table;
    }

    /**
     * Get the Doctrine column instance for a column change.
     *
     * 获取列更改的原则列实例
     *
     * @param  \Doctrine\DBAL\Schema\Table  $table
     * @param  \Illuminate\Support\Fluent  $fluent
     * @return \Doctrine\DBAL\Schema\Column
     */
    protected static function getDoctrineColumn(Table $table, Fluent $fluent)
    {
        return $table->changeColumn(
            //                     获取Doctrine列更改选项
            $fluent['name'], static::getDoctrineColumnChangeOptions($fluent)
        )->getColumn($fluent['name']);
    }

    /**
     * Get the Doctrine column change options.
     *
     * 获取Doctrine列更改选项
     *
     * @param  \Illuminate\Support\Fluent  $fluent
     * @return array
     */
    protected static function getDoctrineColumnChangeOptions(Fluent $fluent)
    {
        //                         获得doctrine列类型
        $options = ['type' => static::getDoctrineColumnType($fluent['type'])];

        if (in_array($fluent['type'], ['text', 'mediumText', 'longText'])) {
            //                          计算适当的列长度以强制教条文本类型
            $options['length'] = static::calculateDoctrineTextLength($fluent['type']);
        }

        return $options;
    }

    /**
     * Get the doctrine column type.
     *
     * 获得doctrine列类型
     *
     * @param  string  $type
     * @return \Doctrine\DBAL\Types\Type
     */
    protected static function getDoctrineColumnType($type)
    {
        $type = strtolower($type);

        switch ($type) {
            case 'biginteger':
                $type = 'bigint';
                break;
            case 'smallinteger':
                $type = 'smallint';
                break;
            case 'mediumtext':
            case 'longtext':
                $type = 'text';
                break;
            case 'binary':
                $type = 'blob';
                break;
        }

        return Type::getType($type);
    }

    /**
     * Calculate the proper column length to force the Doctrine text type.
     *
     * 计算适当的列长度以强制教条文本类型
     *
     * @param  string  $type
     * @return int
     */
    protected static function calculateDoctrineTextLength($type)
    {
        switch ($type) {
            case 'mediumText':
                return 65535 + 1;
            case 'longText':
                return 16777215 + 1;
            default:
                return 255 + 1;
        }
    }

    /**
     * Get the matching Doctrine option for a given Fluent attribute name.
     *
     * 为给定的流利属性名获取匹配原则选项
     *
     * @param  string  $attribute
     * @return string|null
     */
    protected static function mapFluentOptionToDoctrine($attribute)
    {
        switch ($attribute) {
            case 'type':
            case 'name':
                return;
            case 'nullable':
                return 'notnull';
            case 'total':
                return 'precision';
            case 'places':
                return 'scale';
            default:
                return $attribute;
        }
    }

    /**
     * Get the matching Doctrine value for a given Fluent attribute.
     *
     * 获得一个给定的流利属性的匹配原则值
     *
     * @param  string  $option
     * @param  mixed  $value
     * @return mixed
     */
    protected static function mapFluentValueToDoctrine($option, $value)
    {
        return $option == 'notnull' ? ! $value : $value;
    }
}
