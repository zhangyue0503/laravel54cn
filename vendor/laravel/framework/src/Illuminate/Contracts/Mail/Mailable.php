<?php

namespace Illuminate\Contracts\Mail;

use Illuminate\Contracts\Queue\Factory as Queue;
//可邮寄的
interface Mailable
{
    /**
     * Send the message using the given mailer.
     *
     * 使用给定的邮件发送消息
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function send(Mailer $mailer);

    /**
     * Queue the given message.
     *
     * 对给定消息排队
     *
     * @param  Queue  $queue
     * @return mixed
     */
    public function queue(Queue $queue);

    /**
     * Deliver the queued message after the given delay.
     *
     * 在给定的延迟之后交付队列消息
     *
     * @param  \DateTime|int  $delay
     * @param  Queue  $queue
     * @return mixed
     */
    public function later($delay, Queue $queue);
}
