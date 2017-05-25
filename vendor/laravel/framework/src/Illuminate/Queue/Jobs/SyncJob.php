<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;

class SyncJob extends Job implements JobContract
{
    /**
     * The class name of the job.
     *
     * 作业的类名
     *
     * @var string
     */
    protected $job;

    /**
     * The queue message data.
     *
     * 队列消息数据
     *
     * @var string
     */
    protected $payload;

    /**
     * Create a new job instance.
	 *
	 * 创建一个新的job实例
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  string  $payload
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, $payload, $connectionName, $queue)
    {
        $this->queue = $queue;
        $this->payload = $payload;
        $this->container = $container;
        $this->connectionName = $connectionName;
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
        parent::release($delay);//将作业放回队列中
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * 获得工作尝试过的次数
     *
     * @return int
     */
    public function attempts()
    {
        return 1;
    }

    /**
     * Get the job identifier.
     *
     * 获取工作标识符
     *
     * @return string
     */
    public function getJobId()
    {
        return '';
    }

    /**
     * Get the raw body string for the job.
     *
     * 获取该作业的原始字符串
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->payload;
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
        return 'sync';
    }
}
