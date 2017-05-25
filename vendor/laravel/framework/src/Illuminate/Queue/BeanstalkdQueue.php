<?php

namespace Illuminate\Queue;

use Pheanstalk\Pheanstalk;
use Pheanstalk\Job as PheanstalkJob;
use Illuminate\Queue\Jobs\BeanstalkdJob;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class BeanstalkdQueue extends Queue implements QueueContract
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
     * The name of the default tube.
     *
     * 默认管的名称
     *
     * @var string
     */
    protected $default;

    /**
     * The "time to run" for all pushed jobs.
     *
     * 所有推动工作的“运行时间”
     *
     * @var int
     */
    protected $timeToRun;

    /**
     * Create a new Beanstalkd queue instance.
     *
     * 创建一个新的Beanstalkd队列实例
     *
     * @param  \Pheanstalk\Pheanstalk  $pheanstalk
     * @param  string  $default
     * @param  int  $timeToRun
     * @return void
     */
    public function __construct(Pheanstalk $pheanstalk, $default, $timeToRun)
    {
        $this->default = $default;
        $this->timeToRun = $timeToRun;
        $this->pheanstalk = $pheanstalk;
    }

    /**
     * Get the size of the queue.
     *
     * 获取队列的大小
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        $queue = $this->getQueue($queue);//等待队列或返回默认值

        return (int) $this->pheanstalk->statsTube($queue)->total_jobs;
    }

    /**
     * Push a new job onto the queue.
     *
     * 把新工作推到队列上
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        //将原始有效负载推到队列中        从给定的作业和数据创建有效载荷字符串
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * 将原始有效负载推到队列中
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        //                                等待队列或返回默认值
        return $this->pheanstalk->useTube($this->getQueue($queue))->put(
            $payload, Pheanstalk::DEFAULT_PRIORITY, Pheanstalk::DEFAULT_DELAY, $this->timeToRun
        );
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * 在延迟之后将新作业推到队列上
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        //                                           等待队列或返回默认值
        $pheanstalk = $this->pheanstalk->useTube($this->getQueue($queue));

        return $pheanstalk->put(
            $this->createPayload($job, $data),//从给定的作业和数据创建有效载荷字符串
            Pheanstalk::DEFAULT_PRIORITY,
            $this->secondsUntil($delay),//在给定的DateTime之前获得秒数
            $this->timeToRun
        );
    }

    /**
     * Pop the next job off of the queue.
     *
     * 从队列中取出下一个作业
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        //等待队列或返回默认值
        $queue = $this->getQueue($queue);

        $job = $this->pheanstalk->watchOnly($queue)->reserve(0);

        if ($job instanceof PheanstalkJob) {
            return new BeanstalkdJob(
                $this->container, $this->pheanstalk, $job, $this->connectionName, $queue
            );
        }
    }

    /**
     * Delete a message from the Beanstalk queue.
     *
     * 从Beanstalk队列中删除一条消息
     *
     * @param  string  $queue
     * @param  string  $id
     * @return void
     */
    public function deleteMessage($queue, $id)
    {
        $queue = $this->getQueue($queue);//等待队列或返回默认值

        $this->pheanstalk->useTube($queue)->delete(new PheanstalkJob($id, ''));
    }

    /**
     * Get the queue or return the default.
     *
     * 等待队列或返回默认值
     *
     * @param  string|null  $queue
     * @return string
     */
    public function getQueue($queue)
    {
        return $queue ?: $this->default;
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
}
