<?php

namespace Illuminate\Bus;

trait Queueable
{
    /**
     * The name of the connection the job should be sent to.
     *
     * 连接的名称应该被发送到
     *
     * @var string|null
     */
    public $connection;

    /**
     * The name of the queue the job should be sent to.
     *
     * 该作业队列的名称应该被发送到
     *
     * @var string|null
     */
    public $queue;

    /**
     * The number of seconds before the job should be made available.
     *
     * 在工作之前的几秒钟
     *
     * @var \DateTime|int|null
     */
    public $delay;

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
        $this->connection = $connection;
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
        $this->queue = $queue;

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
        $this->delay = $delay;

        return $this;
    }
}
