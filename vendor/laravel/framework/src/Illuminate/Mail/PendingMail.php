<?php

namespace Illuminate\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;

class PendingMail
{
    /**
     * The mailer instance.
     *
     * 邮件实例
     *
     * @var array
     */
    protected $mailer;

    /**
     * The "to" recipients of the message.
     *
     * 消息接收者的“to”收件人
     *
     * @var array
     */
    protected $to = [];

    /**
     * The "cc" recipients of the message.
     *
     * 消息接收者的“cc”收件人
     *
     * @var array
     */
    protected $cc = [];

    /**
     * The "bcc" recipients of the message.
     *
     * 消息接收者的“bcc”收件人
     *
     * @var array
     */
    protected $bcc = [];

    /**
     * Create a new mailable mailer instance.
     *
     * 创建一个新的邮件实例
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Set the recipients of the message.
     *
     * 设置消息的收件人
     *
     * @param  mixed  $users
     * @return $this
     */
    public function to($users)
    {
        $this->to = $users;

        return $this;
    }

    /**
     * Set the recipients of the message.
     *
     * 设置消息的收件人
     *
     * @param  mixed  $users
     * @return $this
     */
    public function cc($users)
    {
        $this->cc = $users;

        return $this;
    }

    /**
     * Set the recipients of the message.
     *
     * 设置消息的接收者
     *
     * @param  mixed  $users
     * @return $this
     */
    public function bcc($users)
    {
        $this->bcc = $users;

        return $this;
    }

    /**
     * Send a new mailable message instance.
     *
     * 发送一个新的可发送消息实例
     *
     * @param  Mailable  $mailable
     * @return mixed
     */
    public function send(Mailable $mailable)
    {
        if ($mailable instanceof ShouldQueue) {
            return $this->queue($mailable);//将给定的邮件发送到队列上
        }
        //使用视图发送一条新消息            填充邮件地址
        return $this->mailer->send($this->fill($mailable));
    }

    /**
     * Send a mailable message immediately.
     *
     * 立即发送一条可发送的消息
     *
     * @param  Mailable  $mailable
     * @return mixed
     */
    public function sendNow(Mailable $mailable)
    {
        //使用视图发送一条新消息            填充邮件地址
        return $this->mailer->send($this->fill($mailable));
    }

    /**
     * Push the given mailable onto the queue.
     *
     * 将给定的邮件发送到队列上
     *
     * @param  Mailable  $mailable
     * @return mixed
     */
    public function queue(Mailable $mailable)
    {
        //            填充邮件地址
        $mailable = $this->fill($mailable);

        if (isset($mailable->delay)) {
            //           排队等待发送(n)秒的新电子邮件消息
            return $this->mailer->later($mailable->delay, $mailable);
        }
        //                  为发送一个新的电子邮件消息
        return $this->mailer->queue($mailable);
    }

    /**
     * Deliver the queued message after the given delay.
     *
     * 在给定的延迟之后交付队列消息
     *
     * @param  \DateTime|int  $delay
     * @param  Mailable  $mailable
     * @return mixed
     */
    public function later($delay, Mailable $mailable)
    {
        //        排队等待发送(n)秒的新电子邮件消息   填充邮件地址
        return $this->mailer->later($delay, $this->fill($mailable));
    }

    /**
     * Populate the mailable with the addresses.
     *
     * 填充邮件地址
     *
     * @param  Mailable  $mailable
     * @return Mailable
     */
    protected function fill(Mailable $mailable)
    {
        return $mailable->to($this->to)//设置消息的接收者
                        ->cc($this->cc)//设置消息的接收者
                        ->bcc($this->bcc);//设置消息的接收者
    }
}
