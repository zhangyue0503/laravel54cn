<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
//刷新命令
class RefreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'migrate:refresh';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Reset and re-run all migrations';

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

        // Next we'll gather some of the options so that we can have the right options
        // to pass to the commands. This includes options such as which database to
        // use and the path to use for the migration. Then we'll run the command.
        //
        // 接下来，我们将收集一些选项，以便我们可以有正确的选项传递给命令
        // 这包括一些选项，比如使用哪个数据库，以及迁移的路径
        // 然后我们将运行命令
        //
        //              返回给定选项名的选项值
        $database = $this->input->getOption('database');

        $path = $this->input->getOption('path');

        $force = $this->input->getOption('force');

        // If the "step" option is specified it means we only want to rollback a small
        // number of migrations before migrating again. For example, the user might
        // only rollback and remigrate the latest four migrations instead of all.
        //
        // 如果指定了“step”选项，这意味着我们只希望在再次迁移之前回滚少量的迁移
        // 例如，用户可能只回滚并重新迁移最新的四个迁移，而不是全部迁移
        //
        $step = $this->input->getOption('step') ?: 0;

        if ($step > 0) {
            //运行rollback命令
            $this->runRollback($database, $path, $step, $force);
        } else {
            //运行复位命令
            $this->runReset($database, $path, $force);
        }

        // The refresh command is essentially just a brief aggregate of a few other of
        // the migration commands and just provides a convenient wrapper to execute
        // them in succession. We'll also see if we need to re-seed the database.
        //
        // refresh命令实际上只是一些其他迁移命令的简单聚合，并提供了一个方便的包装器来执行它们
        // 我们还会看到是否需要重新启动数据库
        //
        //调用另一个控制台命令
        $this->call('migrate', [
            '--database' => $database,
            '--path' => $path,
            '--force' => $force,
        ]);
        //确定开发人员是否要求进行数据库seeder
        if ($this->needsSeeding()) {
            //运行数据库seeder命令
            $this->runSeeder($database);
        }
    }

    /**
     * Run the rollback command.
     *
     * 运行rollback命令
     *
     * @param  string  $database
     * @param  string  $path
     * @param  bool  $step
     * @param  bool  $force
     * @return void
     */
    protected function runRollback($database, $path, $step, $force)
    {
        //调用另一个控制台命令
        $this->call('migrate:rollback', [
            '--database' => $database,
            '--path' => $path,
            '--step' => $step,
            '--force' => $force,
        ]);
    }

    /**
     * Run the reset command.
     *
     * 运行复位命令
     *
     * @param  string  $database
     * @param  string  $path
     * @param  bool  $force
     * @return void
     */
    protected function runReset($database, $path, $force)
    {
        //调用另一个控制台命令
        $this->call('migrate:reset', [
            '--database' => $database,
            '--path' => $path,
            '--force' => $force,
        ]);
    }

    /**
     * Determine if the developer has requested database seeding.
     *
     * 确定开发人员是否要求进行数据库seeder
     *
     * @return bool
     */
    protected function needsSeeding()
    {
        //获取命令选项的值
        return $this->option('seed') || $this->option('seeder');
    }

    /**
     * Run the database seeder command.
     *
     * 运行数据库seeder命令
     *
     * @param  string  $database
     * @return void
     */
    protected function runSeeder($database)
    {
        //调用另一个控制台命令
        $this->call('db:seed', [
            '--database' => $database,
            '--class' => $this->option('seeder') ?: 'DatabaseSeeder',//获取命令选项的值
            '--force' => $this->option('force'),
        ]);
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

            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'],

            ['seeder', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder.'],

            ['step', null, InputOption::VALUE_OPTIONAL, 'The number of migrations to be reverted & re-run.'],
        ];
    }
}
