<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Support\Composer;
use Illuminate\Database\Migrations\MigrationCreator;

class MigrateMakeCommand extends BaseCommand
{
    /**
     * The console command signature.
     *
     * 控制台命令签名
     *
     * @var string
     */
    protected $signature = 'make:migration {name : The name of the migration.}
        {--create= : The table to be created.}
        {--table= : The table to migrate.}
        {--path= : The location where the migration file should be created.}';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new migration file';

    /**
     * The migration creator instance.
     *
     * 迁移创建器实例
     *
     * @var \Illuminate\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * The Composer instance.
     *
     * Compoer实例
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new migration install command instance.
     *
     * 创建一个新的迁移安装命令实例
     *
     * @param  \Illuminate\Database\Migrations\MigrationCreator  $creator
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        //创建一个新的控制台命令实例
        parent::__construct();

        $this->creator = $creator;
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
        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate migrations.
        //
        // 开发人员可以在这个模式操作中指定要修改的表
        // 开发人员还可以指定这个表是否需要新创建，这样我们就可以创建适当的迁移
        //
        //                            返回给定参数名的参数值
        $name = trim($this->input->getArgument('name'));
        //返回给定选项名的选项值
        $table = $this->input->getOption('table');

        $create = $this->input->getOption('create') ?: false;

        // If no table was given as an option but a create option is given then we
        // will use the "create" option as the table name. This allows the devs
        // to pass a table name into this option as a short-cut for creating.
        //
        // 如果没有将表作为一个选项给出，但是给定一个create选项，那么我们将使用“create”选项作为表名
        // 这允许devs将表名传递给这个选项，作为创建的捷径
        //
        if (! $table && is_string($create)) {
            $table = $create;

            $create = true;
        }

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
        //
        // 现在，我们准备将迁移写到磁盘上。一旦我们写迁移出来,我们将为整个框架dump-autoload确保迁移由类装入器注册
        //
        //      将迁移文件写到磁盘上
        $this->writeMigration($name, $table, $create);
        //          再生Composer的自动加载文件
        $this->composer->dumpAutoloads();
    }

    /**
     * Write the migration file to disk.
     *
     * 将迁移文件写到磁盘上
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function writeMigration($name, $table, $create)
    {
        //                              在给定的路径中创建新的迁移
        $file = pathinfo($this->creator->create(
            //               得到迁移路径(由“——路径”选项指定或默认位置)
            $name, $this->getMigrationPath(), $table, $create
        ), PATHINFO_FILENAME);
        //将字符串作为标准输出写入
        $this->line("<info>Created Migration:</info> {$file}");
    }

    /**
     * Get migration path (either specified by '--path' option or default location).
     *
     * 得到迁移路径(由“——路径”选项指定或默认位置)
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        //                                      返回给定选项名的选项值
        if (! is_null($targetPath = $this->input->getOption('path'))) {
            //得到Laravel安装的基本路径
            return $this->laravel->basePath().'/'.$targetPath;
        }
        //获取到迁移目录的路径
        return parent::getMigrationPath();
    }
}
