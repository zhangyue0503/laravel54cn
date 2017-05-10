<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Support\Collection;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

class StatusCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'migrate:status';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Show the status of each migration';

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
     * @param  \Illuminate\Database\Migrations\Migrator $migrator
     * @return \Illuminate\Database\Console\Migrations\StatusCommand
     */
    public function __construct(Migrator $migrator)
    {
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
        //设置缺省连接名称(获取命令选项的值)
        $this->migrator->setConnection($this->option('database'));

        //确定迁移存储库是否存在
        if (! $this->migrator->repositoryExists()) {
            //将字符串写入错误输出
            return $this->error('No migrations found.');
        }
        //                获取迁移存储库实例->获取一个给定包的运行迁移
        $ran = $this->migrator->getRepository()->getRan();
        //获得给定的运行迁移的状态
        if (count($migrations = $this->getStatusFor($ran)) > 0) {
            //格式输入到文本表
            $this->table(['Ran?', 'Migration'], $migrations);
        } else {
            $this->error('No migrations found');
        }
    }

    /**
     * Get the status for the given ran migrations.
     *
     * @param  array  $ran
     * @return \Illuminate\Support\Collection
     */
    protected function getStatusFor(array $ran)
    {
        //创建一个新的集合实例，如果该值不是一个准备好的(获取所有迁移文件的数组)
        return Collection::make($this->getAllMigrationFiles())
            //在每个项目上运行map
                    ->map(function ($migration) use ($ran) {
                        //                           获取迁移的名称
                        $migrationName = $this->migrator->getMigrationName($migration);

                        return in_array($migrationName, $ran)
                                ? ['<info>Y</info>', $migrationName]
                                : ['<fg=red>N</fg=red>', $migrationName];
                    });
    }

    /**
     * Get an array of all of the migration files.
     *
     * 获取所有迁移文件的数组
     *
     * @return array
     */
    protected function getAllMigrationFiles()
    {
        //                   在给定的路径中获取所有迁移文件(获取所有的迁移路径)
        return $this->migrator->getMigrationFiles($this->getMigrationPaths());
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

            ['path', null, InputOption::VALUE_OPTIONAL, 'The path of migrations files to use.'],
        ];
    }
}
