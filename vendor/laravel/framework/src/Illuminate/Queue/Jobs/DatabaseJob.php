<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Container\Container;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Contracts\Queue\Job as JobContract;

class DatabaseJob extends Job implements JobContract
{
    /**
     * The database queue instance.
     *
     * 数据库队列实例
     *
     * @var \Illuminate\Queue\DatabaseQueue
     */
    protected $database;

    /**
     * The database job payload.
     *
     * 数据库工作负载
     *
     * @var \StdClass
     */
    protected $job;

    /**
     * Create a new job instance.
     *
     * 创建一个新的工作实例
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Illuminate\Queue\DatabaseQueue  $database
     * @param  \StdClass  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, DatabaseQueue $database, $job, $connectionName, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->database = $database;
        $this->container = $container;
        $this->connectionName = $connectionName;
    }

    /**
     * Release the job back into the queue.
     *
     * 将作业放回队列中
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);//将作业放回队列中

        $this->delete();//从队列中删除作业
        //               将保留的作业放回队列中
        $this->database->release($this->queue, $this->job, $delay);
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
        parent::delete();//从队列中删除作业
        //                 从队列中删除一个保留的作业
        $this->database->deleteReserved($this->queue, $this->job->id);
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
        return (int) $this->job->attempts;
    }

    /**
     * Get the job identifier.
     *
     * 得到工作标识符
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->job->id;
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
        return $this->job->payload;
    }
}
