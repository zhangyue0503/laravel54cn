<?php

namespace Illuminate\Queue;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Contracts\Redis\Factory as Redis;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class RedisQueue extends Queue implements QueueContract
{
    /**
     * The Redis factory implementation.
     *
     * Redis工厂实施
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * The connection name.
     *
     * 连接名
     *
     * @var string
     */
    protected $connection;

    /**
     * The name of the default queue.
     *
     * 默认队列的名称
     *
     * @var string
     */
    protected $default;

    /**
     * The expiration time of a job.
     *
     * 工作的截止时间
     *
     * @var int|null
     */
    protected $retryAfter = 60;

    /**
     * Create a new Redis queue instance.
     *
     * 创建一个新的Redis队列实例
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @param  string  $default
     * @param  string  $connection
     * @param  int  $retryAfter
     * @return void
     */
    public function __construct(Redis $redis, $default = 'default', $connection = null, $retryAfter = 60)
    {
        $this->redis = $redis;
        $this->default = $default;
        $this->connection = $connection;
        $this->retryAfter = $retryAfter;
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
        $queue = $this->getQueue($queue);//获取队列或返回默认值
        //获取队列的连接
        return $this->getConnection()->eval(
            //让Lua的脚本计算出队列的大小
            LuaScripts::size(), 3, $queue, $queue.':delayed', $queue.':reserved'
        );
    }

    /**
     * Push a new job onto the queue.
     *
     * 把新工作推到队列上
     *
     * @param  object|string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        //   将原始有效负载推到队列中   从给定的作业和数据创建有效载荷字符串
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
        //获取队列的连接                         获取队列或返回默认值
        $this->getConnection()->rpush($this->getQueue($queue), $payload);
        //使用“点”符号从数组中获取一个项
        return Arr::get(json_decode($payload, true), 'id');
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * 在延迟之后将新作业推到队列上
     *
     * @param  \DateTime|int  $delay
     * @param  object|string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        //在延迟之后将原始作业推到队列上             从给定的作业和数据创建有效载荷字符串
        return $this->laterRaw($delay, $this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw job onto the queue after a delay.
     *
     * 在延迟之后将原始作业推到队列上
     *
     * @param  \DateTime|int  $delay
     * @param  string  $payload
     * @param  string  $queue
     * @return mixed
     */
    protected function laterRaw($delay, $payload, $queue = null)
    {
        //获取队列的连接
        $this->getConnection()->zadd(
            //获取队列或返回默认值                         获得“可用的”UNIX时间戳
            $this->getQueue($queue).':delayed', $this->availableAt($delay), $payload
        );
        //使用“点”符号从数组中获取一个项
        return Arr::get(json_decode($payload, true), 'id');
    }

    /**
     * Create a payload string from the given job and data.
     *
     * 从给定的作业和数据中创建一个有效负载字符串
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return string
     */
    protected function createPayloadArray($job, $data = '', $queue = null)
    {
        //                        从给定的作业和数据创建有效载荷数组
        return array_merge(parent::createPayloadArray($job, $data, $queue), [
            'id' => $this->getRandomId(),//得到一个随机的ID字符串
            'attempts' => 0,
        ]);
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
        //将任何延迟或过期的作业迁移到主队列         获取队列或返回默认值
        $this->migrate($prefixed = $this->getQueue($queue));
        //                            从队列中检索下一个作业
        list($job, $reserved) = $this->retrieveNextJob($prefixed);

        if ($reserved) {
            return new RedisJob(
                $this->container, $this, $job,
                $reserved, $this->connectionName, $queue ?: $this->default
            );
        }
    }

    /**
     * Migrate any delayed or expired jobs onto the primary queue.
     *
     * 将任何延迟或过期的作业迁移到主队列
     *
     * @param  string  $queue
     * @return void
     */
    protected function migrate($queue)
    {
        //迁移准备到常规队列的延迟作业
        $this->migrateExpiredJobs($queue.':delayed', $queue);

        if (! is_null($this->retryAfter)) {
            $this->migrateExpiredJobs($queue.':reserved', $queue);
        }
    }

    /**
     * Migrate the delayed jobs that are ready to the regular queue.
     *
     * 迁移准备到常规队列的延迟作业
     *
     * @param  string  $from
     * @param  string  $to
     * @return array
     */
    public function migrateExpiredJobs($from, $to)
    {
        //获取队列的连接
        return $this->getConnection()->eval(
            //让Lua的脚本将过期的工作转移到队列上                    将当前系统时间作为UNIX时间戳
            LuaScripts::migrateExpiredJobs(), 2, $from, $to, $this->currentTime()
        );
    }

    /**
     * Retrieve the next job from the queue.
     *
     * 从队列中检索下一个作业
     *
     * @param  string  $queue
     * @return array
     */
    protected function retrieveNextJob($queue)
    {
        //获取队列的连接
        return $this->getConnection()->eval(
            //获得Lua的脚本，从队列中弹出下一份工作
            LuaScripts::pop(), 2, $queue, $queue.':reserved',
            $this->availableAt($this->retryAfter)//获得“可用的”UNIX时间戳
        );
    }

    /**
     * Delete a reserved job from the queue.
     *
     * 从队列中删除一个保留的作业
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\RedisJob  $job
     * @return void
     */
    public function deleteReserved($queue, $job)
    {
        //获取队列的连接                     获取队列或返回默认值             获得基本的保留的Redis工作
        $this->getConnection()->zrem($this->getQueue($queue).':reserved', $job->getReservedJob());
    }

    /**
     * Delete a reserved job from the reserved queue and release it.
     *
     * 从保留队列中删除保留的作业并释放它
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\RedisJob  $job
     * @param  int  $delay
     * @return void
     */
    public function deleteAndRelease($queue, $job, $delay)
    {
        $queue = $this->getQueue($queue);//获取队列或返回默认值
        //获取队列的连接
        $this->getConnection()->eval(
            //获得Lua的保留作业
            LuaScripts::release(), 2, $queue.':delayed', $queue.':reserved',
        //      获得基本的保留的Redis工作          获得“可用的”UNIX时间戳
            $job->getReservedJob(), $this->availableAt($delay)
        );
    }

    /**
     * Get a random ID string.
     *
     * 得到一个随机的ID字符串
     *
     * @return string
     */
    protected function getRandomId()
    {
        return Str::random(32);//生成一个更真实的“随机”alpha数字字符串
    }

    /**
     * Get the queue or return the default.
     *
     * 获取队列或返回默认值
     *
     * @param  string|null  $queue
     * @return string
     */
    protected function getQueue($queue)
    {
        return 'queues:'.($queue ?: $this->default);
    }

    /**
     * Get the connection for the queue.
     *
     * 获取队列的连接
     *
     * @return \Predis\ClientInterface
     */
    protected function getConnection()
    {
        return $this->redis->connection($this->connection);//通过名称获得一个Redis连接
    }

    /**
     * Get the underlying Redis instance.
     *
     * 获取底层的Redis实例
     *
     * @return \Illuminate\Contracts\Redis\Factory
     */
    public function getRedis()
    {
        return $this->redis;
    }
}
