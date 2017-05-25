<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Queue\InteractsWithTime;

abstract class Job
{
    use InteractsWithTime;

    /**
     * The job handler instance.
     *
     * 工作处理实例
     *
     * @var mixed
     */
    protected $instance;

    /**
     * The IoC container instance.
     *
     * IoC容器实例
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Indicates if the job has been deleted.
     *
     * 表明作业是否已被删除
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * Indicates if the job has been released.
     *
     * 表明工作是否已被释放
     *
     * @var bool
     */
    protected $released = false;

    /**
     * Indicates if the job has failed.
     *
     * 表明工作是否失败
     *
     * @var bool
     */
    protected $failed = false;

    /**
     * The name of the connection the job belongs to.
     *
     * 该作业所属的连接是
     */
    protected $connectionName;

    /**
     * The name of the queue the job belongs to.
     *
     * 该作业所属的队列的名称
     *
     * @var string
     */
    protected $queue;

    /**
     * Fire the job.
	 *
	 * 处理一个消息
     *
     * @return void
     */
    public function fire()
    {
        $payload = $this->payload();//得到解码后的工作

        list($class, $method) = JobName::parse($payload['job']); //将给定的作业名称解析成类/方法数组
        //返回给定对象                 解析给定的类
        with($this->instance = $this->resolve($class))->{$method}($this, $payload['data']);
    }

    /**
     * Delete the job from the queue.
     *
     * 从队列中删除作业
     *
     * @return void
     */
    public function delete()
    {
        $this->deleted = true;
    }

    /**
     * Determine if the job has been deleted.
     *
     * 确定该作业是否已被删除
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Release the job back into the queue.
     *
     * 将作业放回队列中
     *
     * @param  int   $delay
     * @return void
     */
    public function release($delay = 0)
    {
        $this->released = true;
    }

    /**
     * Determine if the job was released back into the queue.
     *
     * 确定作业是否被释放到队列中
     *
     * @return bool
     */
    public function isReleased()
    {
        return $this->released;
    }

    /**
     * Determine if the job has been deleted or released.
     *
     * 确定该作业是否已被删除或释放
     *
     * @return bool
     */
    public function isDeletedOrReleased()
    {
        //    确定该作业是否已被删除       确定作业是否被释放到队列中
        return $this->isDeleted() || $this->isReleased();
    }

    /**
     * Determine if the job has been marked as a failure.
     *
     * 确定这个工作是否被标记为失败
     *
     * @return bool
     */
    public function hasFailed()
    {
        return $this->failed;
    }

    /**
     * Mark the job as "failed".
     *
     * 把工作标记为“失败”
     *
     * @return void
     */
    public function markAsFailed()
    {
        $this->failed = true;
    }

    /**
     * Process an exception that caused the job to fail.
     *
     * 处理一个导致作业失败的异常
     *
     * @param  \Exception  $e
     * @return void
     */
    public function failed($e)
    {
        $this->markAsFailed();//把工作标记为“失败”

        $payload = $this->payload();//得到解码后的工作
        //                         将给定的作业名称解析成类/方法数组
        list($class, $method) = JobName::parse($payload['job']);
        //                                      解析给定的类
        if (method_exists($this->instance = $this->resolve($class), 'failed')) {
            $this->instance->failed($payload['data'], $e);
        }
    }

    /**
     * Resolve the given class.
     *
     * 解析给定的类
     *
     * @param  string  $class
     * @return mixed
     */
    protected function resolve($class)
    {
        //                 从容器中解析给定类型
        return $this->container->make($class);
    }

    /**
     * Get the decoded body of the job.
     *
     * 得到解码后的工作
     *
     * @return array
     */
    public function payload()
    {
        //                    获取该作业的原始字符串
        return json_decode($this->getRawBody(), true);
    }

    /**
     * The number of times to attempt a job.
     *
     * 尝试一份工作的次数
     *
     * @return int|null
     */
    public function maxTries()
    {
        //使用“点”符号从数组中获取一个项  得到解码后的工作
        return array_get($this->payload(), 'maxTries');
    }

    /**
     * The number of seconds the job can run.
     *
     * 工作可以运行的秒数
     *
     * @return int|null
     */
    public function timeout()
    {
        //使用“点”符号从数组中获取一个项  得到解码后的工作
        return array_get($this->payload(), 'timeout');
    }

    /**
     * Get the name of the queued job class.
     *
     * 获取队列作业类的名称
     *
     * @return string
     */
    public function getName()
    {
        //          得到解码后的工作
        return $this->payload()['job'];
    }

    /**
     * Get the resolved name of the queued job class.
     *
     * 获取队列作业类的解析名称
     *
     * Resolves the name of "wrapped" jobs such as class-based handlers.
     *
     * 解析“包装”作业的名称，例如基于类的处理程序
     *
     * @return string
     */
    public function resolveName()
    {
        //      获取队列作业类的解析名称  获取队列作业类的名称     得到解码后的工作
        return JobName::resolve($this->getName(), $this->payload());
    }

    /**
     * Get the name of the connection the job belongs to.
     *
     * 获取该工作所属的连接的名称
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Get the name of the queue the job belongs to.
     *
     * 获取该作业所属的队列的名称
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Get the service container instance.
     *
     * 获取服务容器实例
     *
     * @return \Illuminate\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}
