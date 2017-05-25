<?php

namespace Illuminate\Queue\Events;

class JobExceptionOccurred
{
    /**
     * The connection name.
     *
     * 连接名
     *
     * @var string
     */
    public $connectionName;

    /**
     * The job instance.
     *
     * 工作实例
     *
     * @var \Illuminate\Contracts\Queue\Job
     */
    public $job;

    /**
     * The exception instance.
     *
     * 异常实例
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $exception
     * @return void
     */
    public function __construct($connectionName, $job, $exception)
    {
        $this->job = $job;
        $this->exception = $exception;
        $this->connectionName = $connectionName;
    }
}
