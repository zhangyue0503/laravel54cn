<?php

namespace Illuminate\Contracts\Pipeline;

use Closure;

interface Pipeline
{
    /**
     * Set the traveler object being sent on the pipeline.
     *
     * 设置正在发送的旅行者对象
     *
     * @param  mixed  $traveler
     * @return $this
     */
    public function send($traveler);

    /**
     * Set the stops of the pipeline.
     *
     * 设置管线的停止
     *
     * @param  dynamic|array  $stops
     * @return $this
     */
    public function through($stops);

    /**
     * Set the method to call on the stops.
     *
     * 设置调用停止的方法
     *
     * @param  string  $method
     * @return $this
     */
    public function via($method);

    /**
     * Run the pipeline with a final destination callback.
     *
     * 使用最终目的地回调运行管道
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(Closure $destination);
}
