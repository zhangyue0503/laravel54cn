<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Support\Arr;
use Illuminate\Queue\RedisQueue;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;

class RedisJob extends Job implements JobContract
{
    /**
     * The Redis queue instance.
     *
     * Redis队列实例
     *
     * @var \Illuminate\Queue\RedisQueue
     */
    protected $redis;

    /**
     * The Redis raw job payload.
     *
     * Redis的原始工作负载
     *
     * @var string
     */
    protected $job;

    /**
     * The JSON decoded version of "$job".
     *
     * JSON解码版本的“$job”
     *
     * @var array
     */
    protected $decoded;

    /**
     * The Redis job payload inside the reserved queue.
     *
     * 在保留队列内的Redis作业有效负载
     *
     * @var string
     */
    protected $reserved;

    /**
     * Create a new job instance.
     *
     * 创建一个新的工作实例
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Illuminate\Queue\RedisQueue  $redis
     * @param  string  $job
     * @param  string  $reserved
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, RedisQueue $redis, $job, $reserved, $connectionName, $queue)
    {
        // The $job variable is the original job JSON as it existed in the ready queue while
        // the $reserved variable is the raw JSON in the reserved queue. The exact format
        // of the reserved job is requird in order for us to properly delete its value.
        //
        // $job变量是原始的作业JSON，因为它存在于就绪队列中，而$预留变量是保留队列中的原始JSON
        // 保留作业的确切格式是为了让我们正确地删除它的值
        //
        $this->job = $job;
        $this->redis = $redis;
        $this->queue = $queue;
        $this->reserved = $reserved;
        $this->container = $container;
        $this->connectionName = $connectionName;
        //                  得到解码后的工作
        $this->decoded = $this->payload();
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
        return $this->job;
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
        //从队列中删除一个保留的作业
        $this->redis->deleteReserved($this->queue, $this);
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
        //从保留队列中删除保留的作业并释放它
        $this->redis->deleteAndRelease($this->queue, $this, $delay);
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
        //使用“点”符号从数组中获取一个项
        return Arr::get($this->decoded, 'attempts') + 1;
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
        //使用“点”符号从数组中获取一个项
        return Arr::get($this->decoded, 'id');
    }

    /**
     * Get the underlying Redis factory implementation.
     *
     * 获得底层的Redis工厂实现
     *
     * @return \Illuminate\Contracts\Redis\Factory
     */
    public function getRedisQueue()
    {
        return $this->redis;
    }

    /**
     * Get the underlying reserved Redis job.
     *
     * 获得基本的保留的Redis工作
     *
     * @return string
     */
    public function getReservedJob()
    {
        return $this->reserved;
    }
}
