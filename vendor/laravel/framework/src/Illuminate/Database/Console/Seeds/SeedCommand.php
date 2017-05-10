<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class SeedCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'db:seed';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Seed the database with records';

    /**
     * The connection resolver instance.
     *
     * 连接解析器实例
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * Create a new database seed command instance.
     *
     * 创建一个新的数据库种子命令实例
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public function __construct(Resolver $resolver)
    {
        //创建一个新的控制台命令实例
        parent::__construct();

        $this->resolver = $resolver;
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
        //设置默认的连接名称(获取要使用的数据库连接的名称)
        $this->resolver->setDefaultConnection($this->getDatabase());
        //运行给定的可调用的大意的
        Model::unguarded(function () {
            //在容器中获取seeder实例->运行数据库seeds
            $this->getSeeder()->__invoke();
        });
    }

    /**
     * Get a seeder instance from the container.
     *
     * 在容器中获取seeder实例
     *
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeeder()
    {
        //从容器中解析给定类型(返回给定选项名的选项值)
        $class = $this->laravel->make($this->input->getOption('class'));

        return $class->setContainer($this->laravel)->setCommand($this);
    }

    /**
     * Get the name of the database connection to use.
     *
     * 获取要使用的数据库连接的名称
     *
     * @return string
     */
    protected function getDatabase()
    {
        //返回给定选项名的选项值
        $database = $this->input->getOption('database');

        return $database ?: $this->laravel['config']['database.default'];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'DatabaseSeeder'],

            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }
}
