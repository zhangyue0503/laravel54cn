<?php

namespace Illuminate\Queue\Events;

class JobProcessing
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
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    public function __construct($connectionName, $job)
    {
        $this->job = $job;
        $this->connectionName = $connectionName;
    }
}
