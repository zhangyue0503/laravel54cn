<?php

namespace Illuminate\Contracts\Cookie;

interface QueueingFactory extends Factory
{
    /**
     * Queue a cookie to send with the next response.
     *
     * 用下一个响应发送一个cookie来发送
     *
     * @param  array  $parameters
     * @return void
     */
    public function queue(...$parameters);

    /**
     * Remove a cookie from the queue.
     *
     * 从队列中删除cookie
     *
     * @param  string  $name
     */
    public function unqueue($name);

    /**
     * Get the cookies which have been queued for the next request.
     *
     * 获取为下一个请求排队的cookie
     *
     * @return array
     */
    public function getQueuedCookies();
}
