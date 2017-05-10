<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

class ResetCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback all database migrations';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration rollback command instance.
     *
     * 创建一个新的迁移回滚命令实例
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
        //            设置缺省连接名称             获取命令选项的值
        $this->migrator->setConnection($this->option('database'));

        // First, we'll make sure that the migration table actually exists before we
        // start trying to rollback and re-run all of the migrations. If it's not
        // present we'll just bail out with an info message for the developers.
        //
        // 首先，在开始尝试回滚和重新运行所有迁移之前，我们将确保迁移表确实存在
        // 如果它不存在，我们将为开发人员提供一个信息消息
        //
        //确定迁移存储库是否存在
        if (! $this->migrator->repositoryExists()) {
            //编写一个字符串作为注释输出
            return $this->comment('Migration table not found.');
        }
        //将当前应用的所有迁移回滚
        $this->migrator->reset(
            //获取所有的迁移路径
            $this->getMigrationPaths(), $this->option('pretend')
        );

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        //
        // 一旦迁移运行我们将抓住注意输出并将其发送到控制台屏幕上,由于迁移本身功能没有任何OutputInterface合同传递到类的实例
        //
        //              获取最后一个操作的注释
        foreach ($this->migrator->getNotes() as $note) {
            $this->output->writeln($note);
        }
    }

    /**
     * Get the console command options.
     *
     * 获得控制台命令选项
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],

            ['path', null, InputOption::VALUE_OPTIONAL, 'The path of migrations files to be executed.'],

            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
        ];
    }
}
