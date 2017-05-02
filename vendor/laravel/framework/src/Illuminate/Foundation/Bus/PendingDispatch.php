<?php

namespace Illuminate\Foundation\Bus;
//等待调度
class PendingDispatch
{
    /**
     * Create a new pending job dispatch.
     *
     * 创建一个新的待处理的作业
     *
     * @param  mixed  $job
     * @return void
     */
    public function __construct($job)
    {
        $this->job = $job;
    }

    /**
     * Set the desired connection for the job.
     *
     * 为作业设置所需的连接
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function onConnection($connection)
    {
        $this->job->onConnection($connection);

        return $this;
    }

    /**
     * Set the desired queue for the job.
     *
     * 设置工作所需的队列
     *
     * @param  string|null  $queue
     * @return $this
     */
    public function onQueue($queue)
    {
        $this->job->onQueue($queue);//设置工作所需的队列

        return $this;
    }

    /**
     * Set the desired delay for the job.
     *
     * 为工作设定期望的延迟
     *
     * @param  \DateTime|int|null  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->job->delay($delay);//为工作设定期望的延迟

        return $this;
    }

    /**
     * Handle the object's destruction.
     *
     * 处理对象的破坏
     *
     * @return void
     */
    public function __destruct()
    {
        //把工作分派给适当的处理者
        dispatch($this->job);
    }
}
