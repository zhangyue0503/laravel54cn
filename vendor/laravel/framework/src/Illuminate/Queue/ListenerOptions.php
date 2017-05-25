<?php

namespace Illuminate\Queue;

class ListenerOptions extends WorkerOptions
{
    /**
     * The environment the worker should run in.
     *
     * 工人应该在环境中运行
     *
     * @var string
     */
    public $environment;

    /**
     * Create a new listener options instance.
     *
     * 创建一个新的侦听器选项实例
     *
     * @param  string  $environment
     * @param  int  $delay
     * @param  int  $memory
     * @param  int  $timeout
     * @param  int  $sleep
     * @param  int  $maxTries
     * @param  bool  $force
     */
    public function __construct($environment = null, $delay = 0, $memory = 128, $timeout = 60, $sleep = 3, $maxTries = 0, $force = false)
    {
        $this->environment = $environment;
        //创建一个新的工作者选项实例
        parent::__construct($delay, $memory, $timeout, $sleep, $maxTries, $force);
    }
}
