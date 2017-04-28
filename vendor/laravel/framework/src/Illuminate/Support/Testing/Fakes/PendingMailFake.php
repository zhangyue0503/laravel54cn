<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\PendingMail;
//伪等待邮件
class PendingMailFake extends PendingMail
{
    /**
     * Create a new instance.
     *
     * 创建一个新的实例
     *
     * @param  \Illuminate\Support\Testing\Fakes\MailFake  $mailer
     * @return void
     */
    public function __construct($mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Send a new mailable message instance.
     *
     * 发送一个新的邮件消息实例
     *
     * @param  Mailable  $mailable
     * @return mixed
     */
    public function send(Mailable $mailable)
    {
        //立即发送一个邮件消息
        return $this->sendNow($mailable);
    }

    /**
     * Send a mailable message immediately.
     *
     * 立即发送一个邮件消息
     *
     * @param  Mailable  $mailable
     * @return mixed
     */
    public function sendNow(Mailable $mailable)
    {
        //使用视图发送一个新消息（填充邮件地址）
        $this->mailer->send($this->fill($mailable));
    }

    /**
     * Push the given mailable onto the queue.
     *
     * 队列发送给定的邮件
     *
     * @param  Mailable  $mailable
     * @return mixed
     */
    public function queue(Mailable $mailable)
    {
        //立即发送一个邮件消息
        return $this->sendNow($mailable);
    }
}
