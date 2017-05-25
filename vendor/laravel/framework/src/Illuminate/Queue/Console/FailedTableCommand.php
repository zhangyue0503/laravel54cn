<?php

namespace Illuminate\Queue\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;

class FailedTableCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'queue:failed-table';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a migration for the failed queue jobs database table';

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
     * Create a new failed queue jobs table command instance.
     *
     * 创建一个新的失败队列作业表命令实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer    $composer
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
        $table = $this->laravel['config']['queue.failed.table'];
        //用失败的作业表桩文件替换生成的迁移
        $this->replaceMigration(
            //    为表创建一个基本迁移文件                 将值转换为大驼峰
            $this->createBaseMigration($table), $table, Str::studly($table)
        );
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
     * @param  string  $table
     * @return string
     */
    protected function createBaseMigration($table = 'failed_jobs')
    {
        //                                       在给定的路径中创建新的迁移
        return $this->laravel['migration.creator']->create(
            //                                      获取数据库目录的路径
            'create_'.$table.'_table', $this->laravel->databasePath().'/migrations'
        );
    }

    /**
     * Replace the generated migration with the failed job table stub.
     *
     * 用失败的作业表桩文件替换生成的迁移
     *
     * @param  string  $path
     * @param  string  $table
     * @param  string  $tableClassName
     * @return void
     */
    protected function replaceMigration($path, $table, $tableClassName)
    {
        $stub = str_replace(
            ['{{table}}', '{{tableClassName}}'],
            [$table, $tableClassName],
        //            获取文件的内容
            $this->files->get(__DIR__.'/stubs/failed_jobs.stub')
        );
        //          写入文件的内容
        $this->files->put($path, $stub);
    }
}
