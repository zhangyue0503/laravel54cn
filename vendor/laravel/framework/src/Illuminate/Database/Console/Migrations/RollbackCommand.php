<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

class RollbackCommand extends BaseCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'migrate:rollback';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Rollback the last database migration';

    /**
     * The migrator instance.
     *
     * 迁移实例
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
        //设置缺省连接名称(获取命令选项的值)
        $this->migrator->setConnection($this->option('database'));
        //回滚最后一次迁移操作
        $this->migrator->rollback(
            //获取所有的迁移路径
            $this->getMigrationPaths(), [
                'pretend' => $this->option('pretend'),
                'step' => (int) $this->option('step'),
            ]
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

            ['step', null, InputOption::VALUE_OPTIONAL, 'The number of migrations to be reverted.'],
        ];
    }
}
