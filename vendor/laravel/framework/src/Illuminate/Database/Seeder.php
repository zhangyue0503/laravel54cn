<?php

namespace Illuminate\Database;

use InvalidArgumentException;
use Illuminate\Console\Command;
use Illuminate\Container\Container;

abstract class Seeder
{
    /**
     * The container instance.
     *
     * 容器实例
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The console command instance.
     *
     *  命令行工具实例
     *
     * @var \Illuminate\Console\Command
     */
    protected $command;

    /**
     * Seed the given connection from the given path.
     *
     * 从给定路径中选择给定的连接
     *
     * @param  string  $class
     * @return void
     */
    public function call($class)
    {
        if (isset($this->command)) {
            //          得到输出实现      写信息到输出增加了最后一个换行符
            $this->command->getOutput()->writeln("<info>Seeding:</info> $class");
        }
        //解析给定seeder类的实例->运行数据库seeds
        $this->resolve($class)->__invoke();
    }

    /**
     * Resolve an instance of the given seeder class.
     *
     * 解析给定seeder类的实例
     *
     * @param  string  $class
     * @return \Illuminate\Database\Seeder
     */
    protected function resolve($class)
    {
        if (isset($this->container)) {
            $instance = $this->container->make($class); // 从容器中解析给定类型

            $instance->setContainer($this->container); // 设置IoC容器实例
        } else {
            $instance = new $class;
        }

        if (isset($this->command)) {
            $instance->setCommand($this->command); // 设置控制台命令实例
        }

        return $instance;
    }

    /**
     * Set the IoC container instance.
     *
     * 设置IoC容器实例
     *
     * @param  \Illuminate\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the console command instance.
     *
     * 设置控制台命令实例
     *
     * @param  \Illuminate\Console\Command  $command
     * @return $this
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Run the database seeds.
     *
     * 运行数据库seeds
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __invoke()
    {
        if (! method_exists($this, 'run')) {
            throw new InvalidArgumentException('Method [run] missing from '.get_class($this));
        }

        return isset($this->container)
                    ? $this->container->call([$this, 'run']) //调用给定的闭包/类@方法并注入它的依赖项
                    : $this->run(); //继承seeder类的子类的run()方法
    }
}
