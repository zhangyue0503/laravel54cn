<?php

namespace Illuminate\Queue\Jobs;

use Pheanstalk\Pheanstalk;
use Illuminate\Container\Container;
use Pheanstalk\Job as PheanstalkJob;
use Illuminate\Contracts\Queue\Job as JobContract;

class BeanstalkdJob extends Job implements JobContract
{
    /**
     * The Pheanstalk instance.
     *
     * Pheanstalk实例
     *
     * @var \Pheanstalk\Pheanstalk
     */
    protected $pheanstalk;

    /**
     * The Pheanstalk job instance.
     *
     * Pheanstalk工作实例
     *
     * @var \Pheanstalk\Job
     */
    protected $job;

    /**
     * Create a new job instance.
     *
     * 创建一个新的工作实例
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Pheanstalk\Pheanstalk  $pheanstalk
     * @param  \Pheanstalk\Job  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, Pheanstalk $pheanstalk, PheanstalkJob $job, $connectionName, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->container = $container;
        $this->pheanstalk = $pheanstalk;
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

        $priority = Pheanstalk::DEFAULT_PRIORITY;

        $this->pheanstalk->release($this->job, $priority, $delay);
    }

    /**
     * Bury the job in the queue.
     *
     * 把工作埋在队列里
     *
     * @return void
     */
    public function bury()
    {
        parent::release();//将作业放回队列中

        $this->pheanstalk->bury($this->job);
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

        $this->pheanstalk->delete($this->job);
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
        $stats = $this->pheanstalk->statsJob($this->job);

        return (int) $stats->reserves;
    }

    /**
     * Get the job identifier.
     *
     * 得到那份工作标识符
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->job->getId();
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
        return $this->job->getData();
    }

    /**
     * Get the underlying Pheanstalk instance.
     *
     * 获取底层的Pheanstalk实例
     *
     * @return \Pheanstalk\Pheanstalk
     */
    public function getPheanstalk()
    {
        return $this->pheanstalk;
    }

    /**
     * Get the underlying Pheanstalk job.
     *
     * 获取底层的Pheanstalk作业
     *
     * @return \Pheanstalk\Job
     */
    public function getPheanstalkJob()
    {
        return $this->job;
    }
}
