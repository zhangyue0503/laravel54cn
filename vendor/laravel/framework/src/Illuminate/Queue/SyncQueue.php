<?php

namespace Illuminate\Queue;

use Exception;
use Throwable;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Symfony\Component\Debug\Exception\FatalThrowableError;
//同步队列
class SyncQueue extends Queue implements QueueContract
{
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
        return 0;
    }

    /**
     * Push a new job onto the queue.
	 *
	 * 推送一条新的消息到队列
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function push($job, $data = '', $queue = null)
    {
		//                 解决同步job实例(通过给定的消息和数据创建一个载荷字符串)
        $queueJob = $this->resolveJob($this->createPayload($job, $data, $queue), $queue);

        try {
            $this->raiseBeforeJobEvent($queueJob);//提高之前的队列作业事件

            $queueJob->fire();//处理一个消息

            $this->raiseAfterJobEvent($queueJob);//提高后队列作业事件
        } catch (Exception $e) {
            $this->handleException($queueJob, $e);//处理作业时发生的异常
        } catch (Throwable $e) {
            $this->handleException($queueJob, new FatalThrowableError($e));
        }

        return 0;
    }

    /**
     * Resolve a Sync job instance.
	 *
	 * 解决同步job实例
	 * * 处理一个同步类型消息实例
     *
     * @param  string  $payload
     * @param  string  $queue
     * @return \Illuminate\Queue\Jobs\SyncJob
     */
    protected function resolveJob($payload, $queue)
    {
        return new SyncJob($this->container, $payload, $this->connectionName, $queue);
    }

    /**
     * Raise the before queue job event.
     *
     * 提高之前的队列作业事件
     *
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseBeforeJobEvent(Job $job)
    {
        //确定给定的抽象类型是否已绑定
        if ($this->container->bound('events')) {
            //                    触发事件并调用监听器
            $this->container['events']->fire(new Events\JobProcessing($this->connectionName, $job));
        }
    }

    /**
     * Raise the after queue job event.
     *
     * 提高后队列作业事件
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseAfterJobEvent(Job $job)
    {
        //确定给定的抽象类型是否已绑定
        if ($this->container->bound('events')) {
            //                    触发事件并调用监听器
            $this->container['events']->fire(new Events\JobProcessed($this->connectionName, $job));
        }
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * 提高异常发生队列作业事件
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function raiseExceptionOccurredJobEvent(Job $job, $e)
    {
        //确定给定的抽象类型是否已绑定
        if ($this->container->bound('events')) {
            //                    触发事件并调用监听器
            $this->container['events']->fire(new Events\JobExceptionOccurred($this->connectionName, $job, $e));
        }
    }

    /**
     * Handle an exception that occurred while processing a job.
     *
     * 处理作业时发生的异常
     *
     * @param  \Illuminate\Queue\Jobs\Job  $queueJob
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    protected function handleException($queueJob, $e)
    {
        $this->raiseExceptionOccurredJobEvent($queueJob, $e);//提高异常发生队列作业事件
        //删除作业，调用“失败”方法，并提高失败的作业事件
        FailingJob::handle($this->connectionName, $queueJob, $e);

        throw $e;
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
        //推送一条新的消息到队列
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
}
