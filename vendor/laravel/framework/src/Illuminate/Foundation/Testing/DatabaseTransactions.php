<?php

namespace Illuminate\Foundation\Testing;

trait DatabaseTransactions
{
    /**
     * Handle database transactions on the specified connections.
     *
     * 在指定的连接上处理数据库事务
     *
     * @return void
     */
    public function beginDatabaseTransaction()
    {
        $database = $this->app->make('db');//从容器中解析给定类型
        //应该有事务的数据库连接
        foreach ($this->connectionsToTransact() as $name) {
            //获取数据库连接实例->启动一个新的数据库事务
            $database->connection($name)->beginTransaction();
        }
        //注册一个回调，在应用程序被销毁之前运行
        $this->beforeApplicationDestroyed(function () use ($database) {
            //应该有事务的数据库连接
            foreach ($this->connectionsToTransact() as $name) {
                //获取数据库连接实例->回滚活动数据库事务
                $database->connection($name)->rollBack();
            }
        });
    }

    /**
     * The database connections that should have transactions.
     *
     * 应该有事务的数据库连接
     *
     * @return array
     */
    protected function connectionsToTransact()
    {
        return property_exists($this, 'connectionsToTransact')
                            ? $this->connectionsToTransact : [null];
    }
}
