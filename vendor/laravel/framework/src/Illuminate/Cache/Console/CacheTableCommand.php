<?php

namespace Illuminate\Cache\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;

class CacheTableCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名称
     *
     * @var string
     */
    protected $name = 'cache:table';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a migration for the cache database table';

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
     * Create a new cache table command instance.
     *
     * 创建一个新的缓存表命令实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        //创建一个新的控制台命令实例
        parent::__construct();

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
        //为表创建一个基本迁移文件
        $fullPath = $this->createBaseMigration();
        //写入文件的内容                           获取文件的内容
        $this->files->put($fullPath, $this->files->get(__DIR__.'/stubs/cache.stub'));
        //将字符串写入信息输出
        $this->info('Migration created successfully!');
        //再生Composer的自动加载文件
        $this->composer->dumpAutoloads();
    }

    /**
     * Create a base migration file for the table.
     *
     * 为表创建一个基本迁移文件
     *
     * @return string
     */
    protected function createBaseMigration()
    {
        $name = 'create_cache_table';
        //                 获取数据库目录的路径
        $path = $this->laravel->databasePath().'/migrations';
        //                                       在给定的路径中创建新的迁移
        return $this->laravel['migration.creator']->create($name, $path);
    }
}
