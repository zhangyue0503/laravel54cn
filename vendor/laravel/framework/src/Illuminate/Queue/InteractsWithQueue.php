<?php

namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Job as JobContract;
//交互队列
trait InteractsWithQueue
{
    /**
     * The underlying queue job instance.
     *
     * 底层队列作业实例
     *
     * @var \Illuminate\Contracts\Queue\Job
     */
    protected $job;

    /**
     * Get the number of times the job has been attempted.
     *
     * 获得工作尝试过的次数
     *
     * @return int
     */
    public function attempts()
    {
        //                        获得工作尝试过的次数
        return $this->job ? $this->job->attempts() : 1;
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
        if ($this->job) {
            //               从队列中删除作业
            return $this->job->delete();
        }
    }

    /**
     * Fail the job from the queue.
     *
     * 从队列中失败
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function fail($exception = null)
    {
        if ($this->job) {
            //  删除作业，调用“失败”方法，并提高失败的作业事件 获取该工作所属的连接的名称
            FailingJob::handle($this->job->getConnectionName(), $this->job, $exception);
        }
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
        if ($this->job) {
            //            将作业放回队列中
            return $this->job->release($delay);
        }
    }

    /**
     * Set the base queue job instance.
     *
     * 设置基本队列作业实例
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return $this
     */
    public function setJob(JobContract $job)
    {
        $this->job = $job;

        return $this;
    }
}
