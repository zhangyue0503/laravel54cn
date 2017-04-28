<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Contracts\Bus\Dispatcher;
use PHPUnit_Framework_Assert as PHPUnit;
//伪总线
class BusFake implements Dispatcher
{
    /**
     * The commands that have been dispatched.
     *
     * 已发送的命令
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Assert if a job was dispatched based on a truth-test callback.
     *
     * 断言是否根据事实测试回调发送作业
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return void
     */
    public function assertDispatched($command, $callback = null)
    {
        PHPUnit::assertTrue(
            //获取与真实测试回调匹配的所有作业->计数集合中的项目数
            $this->dispatched($command, $callback)->count() > 0,
            "The expected [{$command}] job was not dispatched."
        );
    }

    /**
     * Determine if a job was dispatched based on a truth-test callback.
     *
     * 确定是否基于真测试回调调度作业
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotDispatched($command, $callback = null)
    {
        PHPUnit::assertTrue(
        //获取与真实测试回调匹配的所有作业->计数集合中的项目数
            $this->dispatched($command, $callback)->count() === 0,
            "The unexpected [{$command}] job was dispatched."
        );
    }

    /**
     * Get all of the jobs matching a truth-test callback.
     *
     * 获取与真实测试回调匹配的所有作业
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function dispatched($command, $callback = null)
    {
        if (! $this->hasDispatched($command)) { //确定给定类是否有存储的命令
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        //                                          在每个项目上运行过滤器
        return collect($this->commands[$command])->filter(function ($command) use ($callback) {
            return $callback($command);
        });
    }

    /**
     * Determine if there are any stored commands for a given class.
     *
     * 确定给定类是否有存储的命令
     *
     * @param  string  $command
     * @return bool
     */
    public function hasDispatched($command)
    {
        return isset($this->commands[$command]) && ! empty($this->commands[$command]);
    }

    /**
     * Dispatch a command to its appropriate handler.
     *
     * 向适当的处理程序发送命令
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command)
    {
        return $this->dispatchNow($command);//在当前进程中向其适当的处理程序发送命令
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * 在当前进程中向其适当的处理程序发送命令
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchNow($command, $handler = null)
    {
        $this->commands[get_class($command)][] = $command;
    }

    /**
     * Set the pipes commands should be piped through before dispatching.
     *
     * 设置管道命令在调度前应管道通过
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes)
    {
        //
    }
}
