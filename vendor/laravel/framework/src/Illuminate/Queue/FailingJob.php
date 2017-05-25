<?php

namespace Illuminate\Queue;

use Illuminate\Container\Container;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Contracts\Events\Dispatcher;

class FailingJob
{
    /**
     * Delete the job, call the "failed" method, and raise the failed job event.
     *
     * 删除作业，调用“失败”方法，并提高失败的作业事件
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Queue\Jobs\Job  $job
     * @param  \Exception $e
     * @return void
     */
    public static function handle($connectionName, $job, $e = null)
    {
        $job->markAsFailed();//把工作标记为“失败”

        if ($job->isDeleted()) {//确定该作业是否已被删除
            return;
        }

        try {
            // If the job has failed, we will delete it, call the "failed" method and then call
            // an event indicating the job has failed so it can be logged if needed. This is
            // to allow every developer to better keep monitor of their failed queue jobs.
            //
            // 如果作业失败了，我们将删除它，调用“失败”方法，然后调用一个表明作业失败的事件，以便在需要时可以记录它
            // 这是为了让每个开发人员更好地监视他们失败的队列作业
            //
            // 从队列中删除作业
            $job->delete();
            //处理一个导致作业失败的异常
            $job->failed($e);
        } finally {
            //获取事件调度程序实例  触发事件并调用监听器
            static::events()->fire(new JobFailed(
                $connectionName, $job, $e ?: new ManuallyFailedException
            ));
        }
    }

    /**
     * Get the event dispatcher instance.
     *
     * 获取事件调度程序实例
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    protected static function events()
    {
        //设置容器的全局可用实例        从容器中解析给定类型
        return Container::getInstance()->make(Dispatcher::class);
    }
}
