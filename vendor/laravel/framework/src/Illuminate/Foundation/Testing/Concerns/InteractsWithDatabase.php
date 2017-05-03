<?php

namespace Illuminate\Foundation\Testing\Concerns;

use PHPUnit_Framework_Constraint_Not as ReverseConstraint;
use Illuminate\Foundation\Testing\Constraints\HasInDatabase;

trait InteractsWithDatabase
{
    /**
     * Assert that a given where condition exists in the database.
     *
     * 断言数据库中存在一个给定的条件
     *
     * @param  string  $table
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function assertDatabaseHas($table, array $data, $connection = null)
    {
        //评估一个PHPUnit_Framework_Constraint匹配器对象
        $this->assertThat(
            //            创建一个新的约束实例    获取数据库连接
            $table, new HasInDatabase($this->getConnection($connection), $data)
        );

        return $this;
    }

    /**
     * Assert that a given where condition does not exist in the database.
     *
     * 断言一个给定的条件在数据库中不存在
     *
     * @param  string  $table
     * @param  array  $data
     * @param  string  $connection
     * @return $this
     */
    protected function assertDatabaseMissing($table, array $data, $connection = null)
    {
        $constraint = new ReverseConstraint(
        //            创建一个新的约束实例    获取数据库连接
            new HasInDatabase($this->getConnection($connection), $data)
        );
        //评估一个PHPUnit_Framework_Constraint匹配器对象
        $this->assertThat($table, $constraint);

        return $this;
    }

    /**
     * Get the database connection.
     *
     * 获取数据库连接
     *
     * @param  string|null  $connection
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection($connection = null)
    {
        $database = $this->app->make('db'); //从容器中解析给定类型
        //获取默认的连接名称
        $connection = $connection ?: $database->getDefaultConnection();
        //获取数据库连接实例
        return $database->connection($connection);
    }

    /**
     * Seed a given database connection.
     *
     * 填充一个给定的数据库连接
     *
     * @param  string  $class
     * @return $this
     */
    public function seed($class = 'DatabaseSeeder')
    {
        //调用手工命令和返回代码
        $this->artisan('db:seed', ['--class' => $class]);

        return $this;
    }
}
