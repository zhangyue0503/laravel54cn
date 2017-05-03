<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel;

trait DatabaseMigrations
{
    /**
     * Define hooks to migrate the database before and after each test.
     *
     * 定义钩子在每次测试之前和之后迁移数据库
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        ////调用手工命令和返回代码
        $this->artisan('migrate');
        //                         设置Artisan应用程序实例
        $this->app[Kernel::class]->setArtisan(null);
        //注册一个回调，在应用程序被销毁之前运行
        $this->beforeApplicationDestroyed(function () {
            ////调用手工命令和返回代码
            $this->artisan('migrate:rollback');
        });
    }
}
