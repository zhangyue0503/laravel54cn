<?php

namespace Illuminate\Contracts\Mail;

interface MailQueue
{
    /**
     * Queue a new e-mail message for sending.
     *
     * 为发送一个新的电子邮件消息
     *
     * @param  string|array  $view
     * @param  array   $data
     * @param  \Closure|string  $callback
     * @param  string  $queue
     * @return mixed
     */
    public function queue($view, array $data, $callback, $queue = null);

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * 队列的新邮件(n)秒后发送
     *
     * @param  int  $delay
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $view, array $data, $callback, $queue = null);
}
