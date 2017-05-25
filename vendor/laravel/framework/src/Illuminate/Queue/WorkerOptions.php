<?php

namespace Illuminate\Queue;

class WorkerOptions
{
    /**
     * The number of seconds before a released job will be available.
     *
     * 发布作业前的秒数
     *
     * @var int
     */
    public $delay;

    /**
     * The maximum amount of RAM the worker may consume.
     *
     * 工人可能消耗的最大RAM数
     *
     * @var int
     */
    public $memory;

    /**
     * The maximum number of seconds a child worker may run.
     *
     * 一个工人可能运行的最大秒数
     *
     * @var int
     */
    public $timeout;

    /**
     * The number of seconds to wait in between polling the queue.
     *
     * 在轮询队列之间等待的秒数
     *
     * @var int
     */
    public $sleep;

    /**
     * The maximum amount of times a job may be attempted.
     *
     * 一份工作的最大次数可能会被尝试
     *
     * @var int
     */
    public $maxTries;

    /**
     * Indicates if the worker should run in maintenance mode.
     *
     * 指示工人是否应该在维护模式下运行
     *
     * @var bool
     */
    public $force;

    /**
     * Create a new worker options instance.
     *
     * 创建一个新的工作者选项实例
     *
     * @param  int  $delay
     * @param  int  $memory
     * @param  int  $timeout
     * @param  int  $sleep
     * @param  int  $maxTries
     * @param  bool  $force
     */
    public function __construct($delay = 0, $memory = 128, $timeout = 60, $sleep = 3, $maxTries = 0, $force = false)
    {
        $this->delay = $delay;
        $this->sleep = $sleep;
        $this->force = $force;
        $this->memory = $memory;
        $this->timeout = $timeout;
        $this->maxTries = $maxTries;
    }
}
