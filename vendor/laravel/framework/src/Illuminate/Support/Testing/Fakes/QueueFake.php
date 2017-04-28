<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Contracts\Queue\Queue;
use PHPUnit_Framework_Assert as PHPUnit;
//伪队列
class QueueFake implements Queue
{
    /**
     * All of the jobs that have been pushed.
     *
     * 所有被推的工作
     *
     * @var array
     */
    protected $jobs = [];

    /**
     * Assert if a job was pushed based on a truth-test callback.
     *
     * 断言如果一个任务是基于真实测试回调而被推的
     *
     * @param  string  $job
     * @param  callable|null  $callback
     * @return void
     */
    public function assertPushed($job, $callback = null)
    {
        PHPUnit::assertTrue(
            //获取所有匹配一个真实测试回调的任务->计数集合中的项目数
            $this->pushed($job, $callback)->count() > 0,
            "The expected [{$job}] job was not pushed."
        );
    }

    /**
     * Assert if a job was pushed based on a truth-test callback.
     *
     * 断言如果一个任务是基于真实测试回调而被推的
     *
     * @param  string  $queue
     * @param  string  $job
     * @param  callable|null  $callback
     * @return void
     */
    public function assertPushedOn($queue, $job, $callback = null)
    {
        return $this->assertPushed($job, function ($job, $pushedQueue) use ($callback, $queue) {
            if ($pushedQueue !== $queue) {
                return false;
            }

            return $callback ? $callback(...func_get_args()) : true;
        });
    }

    /**
     * Determine if a job was pushed based on a truth-test callback.
     *
     * 确定一个推送的任务是否基于一个真实测试的回调
     *
     * @param  string  $job
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotPushed($job, $callback = null)
    {
        PHPUnit::assertTrue(
            //获取所有匹配一个真实测试回调的任务->计数集合中的项目数
            $this->pushed($job, $callback)->count() === 0,
            "The unexpected [{$job}] job was pushed."
        );
    }

    /**
     * Get all of the jobs matching a truth-test callback.
     *
     * 获取所有匹配一个真实测试回调的任务
     *
     * @param  string  $job
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function pushed($job, $callback = null)
    {
        if (! $this->hasPushed($job)) {//确定给定类是否存在任何存储作业
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };
        //                               在每个项目上运行过滤器
        return collect($this->jobs[$job])->filter(function ($data) use ($callback) {
            return $callback($data['job'], $data['queue']);
        })->pluck('job');//获取给定键的值
    }

    /**
     * Determine if there are any stored jobs for a given class.
     *
     * 确定给定类是否存在任何存储作业
     *
     * @param  string  $job
     * @return bool
     */
    public function hasPushed($job)
    {
        return isset($this->jobs[$job]) && ! empty($this->jobs[$job]);
    }

    /**
     * Resolve a queue connection instance.
     *
     * 解析队列连接实例
     *
     * @param  mixed  $value
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connection($value = null)
    {
        return $this;
    }

    /**
     * Get the size of the queue.
     *
     * 获取队列大小
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return 0;
    }

    /**
     * Push a new job onto the queue.
     *
     * 向队列推一个新的任务
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        $this->jobs[get_class($job)][] = [
            'job' => $job,
            'queue' => $queue,
        ];
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
        //
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
        //向队列推一个新的任务
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto the queue.
     *
     * 把新工作推到队列上
     *
     * @param  string  $queue
     * @param  string  $job
     * @param  mixed   $data
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
        //向队列推一个新的任务
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * 在延迟之后将新作业推到队列上
     *
     * @param  string  $queue
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
        //向队列推一个新的任务
        return $this->push($job, $data, $queue);
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
        //
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * 将一系列作业推到队列中
     *
     * @param  array $jobs
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ($this->jobs as $job) {
            $this->push($job);//向队列推一个新的任务
        }
    }

    /**
     * Get the connection name for the queue.
     *
     * 获取队列的连接名
     *
     * @return string
     */
    public function getConnectionName()
    {
        //
    }

    /**
     * Set the connection name for the queue.
     *
     * 设置队列的连接名
     *
     * @param  string $name
     * @return $this
     */
    public function setConnectionName($name)
    {
        return $this;
    }
}
