<?php

namespace Illuminate\Events;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CallQueuedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The listener class name.
     *
     * 监听器类名
     *
     * @var string
     */
    public $class;

    /**
     * The listener method.
     *
     * 监听方法
     *
     * @var string
     */
    public $method;

    /**
     * The data to be passed to the listener.
     *
     * 将数据传递给监听器
     *
     * @var array
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * 创建一个新的工作实例
     *
     * @param  string  $class
     * @param  string  $method
     * @param  array  $data
     * @return void
     */
    public function __construct($class, $method, $data)
    {
        $this->data = $data;
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * Handle the queued job.
     *
     * 处理排队工作
     *
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function handle(Container $container)
    {
        $this->prepareData(); // 如果需要，获取序列化的数据

        $handler = $this->setJobInstanceIfNecessary( //如有必要，设置给定类的作业实例
            //               从容器中解析给定类型
            $this->job, $container->make($this->class)
        );

        call_user_func_array(
            [$handler, $this->method], $this->data
        );
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
     * @param  \Exception  $e
     * @return void
     */
    public function failed($e)
    {
        $this->prepareData();// 如果需要，获取序列化的数据

        $handler = Container::getInstance()->make($this->class);  // 从容器中解析给定类型

        $parameters = array_merge($this->data, [$e]);

        if (method_exists($handler, 'failed')) {
            call_user_func_array([$handler, 'failed'], $parameters);
        }
    }

    /**
     * Unserialize the data if needed.
     *
     * 如果需要，获取序列化的数据
     *
     * @return void
     */
    protected function prepareData()
    {
        if (is_string($this->data)) {
            $this->data = unserialize($this->data);
        }
    }

    /**
     * Get the display name for the queued job.
     *
     * 获取排队作业的显示名称
     *
     * @return string
     */
    public function displayName()
    {
        return $this->class;
    }
}
