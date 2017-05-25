<?php

namespace Illuminate\Queue\Failed;

class NullFailedJobProvider implements FailedJobProviderInterface
{
    /**
     * Log a failed job into storage.
     *
     *  将一个失败的作业记录到存储中
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  \Exception  $exception
     * @return int|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        //
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * 列出所有失败的工作
     *
     * @return array
     */
    public function all()
    {
        return [];
    }

    /**
     * Get a single failed job.
     *
     * 获取一个失败的工作
     *
     * @param  mixed  $id
     * @return array
     */
    public function find($id)
    {
        //
    }

    /**
     * Delete a single failed job from storage.
     *
     * 从存储中删除一个失败的作业
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        return true;
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * 从存储中清除所有失败的作业
     *
     * @return void
     */
    public function flush()
    {
        //
    }
}
