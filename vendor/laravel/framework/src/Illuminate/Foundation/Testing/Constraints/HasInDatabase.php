<?php

namespace Illuminate\Foundation\Testing\Constraints;

use PHPUnit_Framework_Constraint;
use Illuminate\Database\Connection;

class HasInDatabase extends PHPUnit_Framework_Constraint
{
    /**
     * Number of records that will be shown in the console in case of failure.
     *
     * 如果出现故障，控制台将显示的记录数量
     *
     * @var int
     */
    protected $show = 3;

    /**
     * The database connection.
     *
     * 数据库连接
     *
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * The data that will be used to narrow the search in the database table.
     *
     * 用于在数据库表中缩小搜索范围的数据
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new constraint instance.
     *
     * 创建一个新的约束实例
     *
     * @param  \Illuminate\Database\Connection  $database
     * @param  array  $data
     * @return void
     */
    public function __construct(Connection $database, array $data)
    {
        $this->data = $data;

        $this->database = $database;
    }

    /**
     * Check if the data is found in the given table.
     *
     * 检查是否在给定的表中找到了数据
     *
     * @param  string  $table
     * @return bool
     */
    public function matches($table)
    {
        //                  对数据库表开始一个流式查询->将基本WHERE子句添加到查询中->检索查询的“count”结果
        return $this->database->table($table)->where($this->data)->count() > 0;
    }

    /**
     * Get the description of the failure.
     *
     * 检查是否在给定的表中找到了数据
     *
     * @param  string  $table
     * @return string
     */
    public function failureDescription($table)
    {
        return sprintf(
            "a row in the table [%s] matches the attributes %s.\n\n%s",
            //获取对象的字符串表示              获取关于在数据库表中发现的记录的附加信息
            $table, $this->toString(), $this->getAdditionalInfo($table)
        );
    }

    /**
     * Get additional info about the records found in the database table.
     *
     * 获取关于在数据库表中发现的记录的附加信息
     *
     * @param  string  $table
     * @return string
     */
    protected function getAdditionalInfo($table)
    {
        //                  对数据库表开始一个流式查询   将查询执行为“SELECT”语句
        $results = $this->database->table($table)->get();
        //确定集合是否为空
        if ($results->isEmpty()) {
            return 'The table is empty';
        }
        //                                       以第一个或最后一个{$limit}项目
        $description = 'Found: '.json_encode($results->take($this->show), JSON_PRETTY_PRINT);
        //计数集合中的项目数
        if ($results->count() > $this->show) {
            //                                         计数集合中的项目数
            $description .= sprintf(' and %s others', $results->count() - $this->show);
        }

        return $description;
    }

    /**
     * Get a string representation of the object.
     *
     * 获取对象的字符串表示
     *
     * @return string
     */
    public function toString()
    {
        return json_encode($this->data);
    }
}
