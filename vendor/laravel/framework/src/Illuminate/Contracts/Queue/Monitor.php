<?php

namespace Illuminate\Contracts\Queue;

interface Monitor
{
    /**
     * Register a callback to be executed on every iteration through the queue loop.
     *
     * 注册一个回调，在每个迭代中通过队列循环执行
     *
     * @param  mixed  $callback
     * @return void
     */
    public function looping($callback);

    /**
     * Register a callback to be executed when a job fails after the maximum amount of retries.
     *
     * 注册一个回调，当一个作业在最大重试次数失败后被执行
     *
     * @param  mixed  $callback
     * @return void
     */
    public function failing($callback);

    /**
     * Register a callback to be executed when a daemon queue is stopping.
     *
     * 注册一个回调，以便在守护进程停止时执行
     *
     * @param  mixed  $callback
     * @return void
     */
    public function stopping($callback);
}
