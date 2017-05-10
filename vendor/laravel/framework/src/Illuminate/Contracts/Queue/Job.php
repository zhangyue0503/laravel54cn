<?php

namespace Illuminate\Contracts\Queue;

interface Job
{
    /**
     * Fire the job.
     *
     * 触发工作
     *
     * @return void
     */
    public function fire();

    /**
     * Release the job back into the queue.
     *
     * 将作业放回队列中
     *
     * @param  int   $delay
     * @return void
     */
    public function release($delay = 0);

    /**
     * Delete the job from the queue.
     *
     * 从队列中删除作业
     *
     * @return void
     */
    public function delete();

    /**
     * Determine if the job has been deleted.
     *
     * 确定该作业是否已被删除
     *
     * @return bool
     */
    public function isDeleted();

    /**
     * Determine if the job has been deleted or released.
     *
     * 确定作业是否已被删除或发布
     *
     * @return bool
     */
    public function isDeletedOrReleased();

    /**
     * Get the number of times the job has been attempted.
     *
     * 获得工作尝试过的次数
     *
     * @return int
     */
    public function attempts();

    /**
     * Process an exception that caused the job to fail.
     *
     * 处理一个导致工作失败的异常
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function failed($e);

    /**
     * The number of times to attempt a job.
     *
     * 尝试一份工作的次数
     *
     * @return int|null
     */
    public function maxTries();

    /**
     * The number of seconds the job can run.
     *
     * 工作可以运行的秒数
     *
     * @return int|null
     */
    public function timeout();

    /**
     * Get the name of the queued job class.
     *
     * 获取队列作业类的名称
     *
     * @return string
     */
    public function getName();

    /**
     * Get the resolved name of the queued job class.
     *
     * 获取队列作业类的解析名称
     *
     * Resolves the name of "wrapped" jobs such as class-based handlers.
     *
     * 解决“包装”的名字基于类处理程序等工作
     *
     * @return string
     */
    public function resolveName();

    /**
     * Get the name of the connection the job belongs to.
     *
     * 获取该工作所属的连接的名称
     *
     * @return string
     */
    public function getConnectionName();

    /**
     * Get the name of the queue the job belongs to.
     *
     * 获取该作业所属的队列的名称
     *
     * @return string
     */
    public function getQueue();

     /**
      * Get the raw body string for the job.
      *
      * 获取该作业的原始字符串
      *
      * @return string
      */
     public function getRawBody();
}
