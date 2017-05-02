<?php

namespace Illuminate\Foundation\Console;

use Closure;
use ReflectionFunction;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClosureCommand extends Command
{
    /**
     * The command callback.
     *
     * 命令回调
     *
     * @var \Closure
     */
    protected $callback;

    /**
     * Create a new command instance.
     *
     * 创建一个新的命令实例
     *
     * @param  string  $signature
     * @param  Closure  $callback
     * @return void
     */
    public function __construct($signature, Closure $callback)
    {
        $this->callback = $callback;
        $this->signature = $signature;

        parent::__construct();//创建一个新的控制台命令实例
    }

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //                   返回所有给定的参数与默认值合并    返回与默认值合并的所有选项
        $inputs = array_merge($input->getArguments(), $input->getOptions());

        $parameters = [];

        foreach ((new ReflectionFunction($this->callback))->getParameters() as $parameter) {
            if (isset($inputs[$parameter->name])) {
                $parameters[$parameter->name] = $inputs[$parameter->name];
            }
        }
        //调用给定的闭包/类@方法并注入它的依赖项
        return $this->laravel->call(
            $this->callback->bindTo($this, $this), $parameters
        );
    }

    /**
     * Set the description for the command.
     *
     * 设置该命令的描述
     *
     * @param  string  $description
     * @return $this
     */
    public function describe($description)
    {
        //设置命令的描述
        $this->setDescription($description);

        return $this;
    }
}
