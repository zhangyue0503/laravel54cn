<?php

namespace Illuminate\Mail;

use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Mail\Mailable as MailableContract;

class SendQueuedMailable
{
    /**
     * The mailable message instance.
     *
     * 可邮寄的消息实例
     *
     * @var Mailable
     */
    public $mailable;

    /**
     * The number of times the job may be attempted.
     *
     * 工作的次数可能会被尝试
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
     *
     * 在超时之前，工作可以运行的秒数
     *
     * @var int
     */
    public $timeout;

    /**
     * Create a new job instance.
     *
     * 创建一个新的工作实例
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return void
     */
    public function __construct(MailableContract $mailable)
    {
        $this->mailable = $mailable;
        $this->tries = property_exists($mailable, 'tries') ? $mailable->tries : null;
        $this->timeout = property_exists($mailable, 'timeout') ? $mailable->timeout : null;
    }

    /**
     * Handle the queued job.
     *
     * 处理排队的工作
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @return void
     */
    public function handle(MailerContract $mailer)
    {
        //使用给定的邮件发送消息
        $this->mailable->send($mailer);
    }

    /**
     * Get the display name for the queued job.
     *
     * 获取队列作业的显示名称
     *
     * @return string
     */
    public function displayName()
    {
        return get_class($this->mailable);
    }
}
