<?php

namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Bus\Dispatcher;

class CallQueuedHandler
{
    /**
     * The bus dispatcher implementation.
     *
     * 公交调度程序的实现
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $dispatcher;

    /**
     * Create a new handler instance.
     *
     * 创建一个新的处理程序实例
     *
     * @param  \Illuminate\Contracts\Bus\Dispatcher  $dispatcher
     * @return void
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handle the queued job.
	 *
	 * 处理队列工作
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  array  $data
     * @return void
     */
    public function call(Job $job, array $data)
    {
        //在必要时设置给定类的作业实例
        $command = $this->setJobInstanceIfNecessary(
            $job, unserialize($data['command'])
        );

        $this->dispatcher->dispatchNow(//在当前进程中将命令发送给适当的处理模块
            $command, $handler = $this->resolveHandler($job, $command)//为给定的命令解析处理程序
        );
        //确定作业是否已被删除或发布
        if (! $job->isDeletedOrReleased()) {
            $job->delete();//从队列中删除作业
        }
    }

    /**
     * Resolve the handler for the given command.
     *
     * 为给定的命令解析处理程序
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  mixed  $command
     * @return mixed
     */
    protected function resolveHandler($job, $command)
    {
        //                        获取一个命令的处理程序
        $handler = $this->dispatcher->getCommandHandler($command) ?: null;

        if ($handler) {
            //在必要时设置给定类的作业实例
            $this->setJobInstanceIfNecessary($job, $handler);
        }

        return $handler;
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * 在必要时设置给定类的作业实例
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  mixed  $instance
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(Job $job, $instance)
    {
        //               交互队列               返回类所使用的所有特性、子类和它们的特征
        if (in_array(InteractsWithQueue::class, class_uses_recursive(get_class($instance)))) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Call the failed method on the job instance.
     *
     * 在作业实例上调用失败的方法
     *
     * The exception that caused the failure will be passed.
     *
     * 导致失败的异常将被通过
     *
     * @param  array  $data
     * @param  \Exception  $e
     * @return void
     */
    public function failed(array $data, $e)
    {
        $command = unserialize($data['command']);

        if (method_exists($command, 'failed')) {
            $command->failed($e);
        }
    }
}
