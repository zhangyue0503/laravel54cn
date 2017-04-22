<?php

namespace Illuminate\Foundation\Bus;

use Illuminate\Contracts\Bus\Dispatcher;

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
        return app(Dispatcher::class)->dispatch($job);
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @param  mixed  $job
     * @return mixed
     */
    public function dispatchNow($job)
    {
        return app(Dispatcher::class)->dispatchNow($job);
    }
}
