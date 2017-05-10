<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Migrations\Migrator;

class MigrateCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     * 控制台命令的名称和签名
     *
     *
     * @var string
     */
    protected $signature = 'migrate {--database= : The database connection to use.}
                {--force : Force the operation to run when in production.}
                {--path= : The path of migrations files to be executed.}
                {--pretend : Dump the SQL queries that would be run.}
                {--seed : Indicates if the seed task should be re-run.}
                {--step : Force the migrations to be run so they can be rolled back individually.}';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Run the database migrations';

    /**
     * The migrator instance.
     *
     * 迁移实例
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration command instance.
     *
     * 创建一个新的迁移命令实例
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     * @return void
     */
    public function __construct(Migrator $migrator)
    {
        //创建一个新的控制台命令实例
        parent::__construct();

        $this->migrator = $migrator;
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
        //在继续操作之前确认
        if (! $this->confirmToProceed()) {
            return;
        }
        //为运行准备迁移数据库
        $this->prepareDatabase();

        // Next, we will check to see if a path option has been defined. If it has
        // we will use the path relative to the root of this installation folder
        // so that migrations may be run for any path within the applications.
        //
        // 接下来，我们将检查是否定义了path选项
        // 如果有的话，我们将使用该安装文件夹的根路径，以便可以为应用程序中的任何路径运行迁移
        //
        // 在给定的路径上运行正在等待的迁移     获取所有的迁移路径
        $this->migrator->run($this->getMigrationPaths(), [
            'pretend' => $this->option('pretend'),//获取命令选项的值
            'step' => $this->option('step'),//获取命令选项的值
        ]);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        //
        // 一旦移居者运行我们将抓住注意输出并将其发送到控制台屏幕上,由于候鸟本身功能没有任何OutputInterface合同传递到类的实例
        //
        //                        获取最后一个操作的注释
        foreach ($this->migrator->getNotes() as $note) {
            $this->output->writeln($note);
        }

        // Finally, if the "seed" option has been given, we will re-run the database
        // seed task to re-populate the database, which is convenient when adding
        // a migration and a seed at the same time, as it is only this command.
        //
        // 最后,如果给出的“种子”选项,我们将重新运行数据库种子任务重新填充数据库,在添加一个迁移和种子时这是很方便的同时,也只有这个命令
        //
        if ($this->option('seed')) {//获取命令选项的值
            $this->call('db:seed', ['--force' => true]);//调用另一个控制台命令
        }
    }

    /**
     * Prepare the migration database for running.
     *
     * 为运行准备迁移数据库
     *
     * @return void
     */
    protected function prepareDatabase()
    {
        ///设置缺省连接名称(获取命令选项的值)
        $this->migrator->setConnection($this->option('database'));
        //确定迁移存储库是否存在
        if (! $this->migrator->repositoryExists()) {
            //调用另一个控制台命令
            $this->call(
                //                                      获取命令选项的值
                'migrate:install', ['--database' => $this->option('database')]
            );
        }
    }
}
