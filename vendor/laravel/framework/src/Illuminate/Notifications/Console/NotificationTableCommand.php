<?php

namespace Illuminate\Notifications\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;

class NotificationTableCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'notifications:table';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a migration for the notifications table';

    /**
     * The filesystem instance.
     *
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new notifications table command instance.
     *
     * 创建一个新的通知表命令实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer    $composer
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();//创建一个新的控制台命令实例

        $this->files = $files;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        $fullPath = $this->createBaseMigration(); //为通知创建一个基本迁移文件
        //写入文件的内容                       获取文件的内容
        $this->files->put($fullPath, $this->files->get(__DIR__.'/stubs/notifications.stub'));
        //将字符串写入信息输出
        $this->info('Migration created successfully!');
        //再生Composer的自动加载文件
        $this->composer->dumpAutoloads();
    }

    /**
     * Create a base migration file for the notifications.
     *
     * 为通知创建一个基本迁移文件
     *
     * @return string
     */
    protected function createBaseMigration()
    {
        $name = 'create_notifications_table';
        //               获取数据库目录的路径
        $path = $this->laravel->databasePath().'/migrations';

        return $this->laravel['migration.creator']->create($name, $path);
    }
}
