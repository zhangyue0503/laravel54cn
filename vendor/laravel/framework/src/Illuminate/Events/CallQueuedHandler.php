<?php

namespace Illuminate\Events;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Container\Container;

class CallQueuedHandler
{
    /**
     * The container instance.
     *
     * 容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Create a new job instance.
     *
     * 创建一个新的工作实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Handle the queued job.
     *
     * 处理排队工作
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  array  $data
     * @return void
     */
    public function call(Job $job, array $data)
    {
        $handler = $this->setJobInstanceIfNecessary( //如有必要，设置给定类的作业实例
            //               从容器中解析给定类型
            $job, $this->container->make($data['class'])
        );

        call_user_func_array(
            [$handler, $data['method']], unserialize($data['data'])
        );

        if (! $job->isDeletedOrReleased()) { //确定作业是否已被删除或发布
            $job->delete(); //从队列中删除作业
        }
    }

    /**
     * Set the job instance of the given class if necessary.
     *
     * 如有必要，设置给定类的作业实例
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  mixed  $instance
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(Job $job, $instance)
    {
        //               交互队列                  返回类所使用的所有特性、子类和它们的特征
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
     * The event instance and the exception will be passed.
     *
     * 事件实例和异常将被传递
     *
     * @param  array  $data
     * @param  \Exception  $e
     * @return void
     */
    public function failed(array $data, $e)
    {
        $handler = $this->container->make($data['class']); // 从容器中解析给定类型

        $parameters = array_merge(unserialize($data['data']), [$e]);

        if (method_exists($handler, 'failed')) {
            call_user_func_array([$handler, 'failed'], $parameters);
        }
    }
}
