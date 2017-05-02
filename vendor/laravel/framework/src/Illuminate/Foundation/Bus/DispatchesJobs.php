<?php

namespace Illuminate\Foundation\Bus;

use Illuminate\Contracts\Bus\Dispatcher;
//分派工作
trait DispatchesJobs
{
    /**
     * Dispatch a job to its appropriate handler.
	 *
	 * 把工作分派给适当的处理者
	 * * 分发消息到适当的处理模块
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected function dispatch($job)
    {
        //获取可用容器实例(分配对象)->把工作分派给适当的处理者
        return app(Dispatcher::class)->dispatch($job);
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * 在当前流程中向其适当的处理程序分派一个命令
     *
     * @param  mixed  $job
     * @return mixed
     */
    public function dispatchNow($job)
    {
        //获取可用容器实例(分配对象)->在当前进程中向其适当的处理程序发送命令
        return app(Dispatcher::class)->dispatchNow($job);
    }
}
