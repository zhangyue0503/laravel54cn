<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;

class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'migrate:install';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create the migration repository';

    /**
     * The repository instance.
     *
     * 存储库实例
     *
     * @var \Illuminate\Database\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * Create a new migration install command instance.
     *
     * 创建一个新的迁移安装命令实例
     *
     * @param  \Illuminate\Database\Migrations\MigrationRepositoryInterface  $repository
     * @return void
     */
    public function __construct(MigrationRepositoryInterface $repository)
    {
        //创建一个新的控制台命令实例
        parent::__construct();

        $this->repository = $repository;
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
        //设置信息源以收集数据(返回给定选项名的选项值)
        $this->repository->setSource($this->input->getOption('database'));
        //创建迁移存储库数据存储
        $this->repository->createRepository();
        //将字符串写入信息输出
        $this->info('Migration table created successfully.');
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
        ];
    }
}
