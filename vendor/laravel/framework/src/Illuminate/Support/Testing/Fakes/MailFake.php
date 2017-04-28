<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Mail\Mailable;
use PHPUnit_Framework_Assert as PHPUnit;
//伪邮件
class MailFake implements Mailer
{
    /**
     * All of the mailables that have been sent.
     *
     * 所有已发送的mailables
     *
     * @var array
     */
    protected $mailables = [];

    /**
     * Assert if a mailable was sent based on a truth-test callback.
     *
     * 断言如果一个邮件是基于真实测试回调而被分派的
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return void
     */
    public function assertSent($mailable, $callback = null)
    {
        PHPUnit::assertTrue(
            //获取匹配一个真实测试回调的所有邮件->计数集合中的项目数
            $this->sent($mailable, $callback)->count() > 0,
            "The expected [{$mailable}] mailable was not sent."
        );
    }

    /**
     * Determine if a mailable was sent based on a truth-test callback.
     *
     * 确定一个邮件是否基于真实测试的回调
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotSent($mailable, $callback = null)
    {
        PHPUnit::assertTrue(
            //获取匹配一个真实测试回调的所有邮件->计数集合中的项目数
            $this->sent($mailable, $callback)->count() === 0,
            "The unexpected [{$mailable}] mailable was sent."
        );
    }

    /**
     * Get all of the mailables matching a truth-test callback.
     *
     * 获取匹配一个真实测试回调的所有邮件
     *
     * @param  string  $mailable
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function sent($mailable, $callback = null)
    {
        if (! $this->hasSent($mailable)) {//确定已发送的邮件是否已发送
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };
        //       获取一个特定类型的已发送邮件     使用给定的回调筛选数组
        return $this->mailablesOf($mailable)->filter(function ($mailable) use ($callback) {
            return $callback($mailable);
        });
    }

    /**
     * Determine if the given mailable has been sent.
     *
     * 确定已发送的邮件是否已发送
     *
     * @param  string  $mailable
     * @return bool
     */
    public function hasSent($mailable)
    {
        //获取一个特定类型的已发送邮件->计数集合中的项目数
        return $this->mailablesOf($mailable)->count() > 0;
    }

    /**
     * Get all of the mailed mailables for a given type.
     *
     * 获取一个特定类型的已发送邮件
     *
     * @param  string  $type
     * @return \Illuminate\Support\Collection
     */
    protected function mailablesOf($type)
    {
        //                                 使用给定的回调筛选数组
        return collect($this->mailables)->filter(function ($mailable) use ($type) {
            return $mailable instanceof $type;
        });
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * 开始发送一个可发送邮件类实例的过程
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function to($users)
    {
        //          伪等待邮件          设置消息的收件人
        return (new PendingMailFake($this))->to($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * 开始发送一个可发送邮件类实例的过程
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function bcc($users)
    {
        //          伪等待邮件          设置消息的收件人
        return (new PendingMailFake($this))->bcc($users);
    }

    /**
     * Send a new message when only a raw text part.
     *
     * 仅在原始文本部分发送一条新消息
     *
     * @param  string  $text
     * @param  \Closure|string  $callback
     * @return int
     */
    public function raw($text, $callback)
    {
        //
    }

    /**
     * Send a new message using a view.
     *
     * 使用视图发送一个新消息
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return void
     */
    public function send($view, array $data = [], $callback = null)
    {
        if (! $view instanceof Mailable) {
            return;
        }

        $this->mailables[] = $view;
    }

    /**
     * Queue a new e-mail message for sending.
     *
     * 队列发送一个新的电子邮件信息
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string|null  $queue
     * @return mixed
     */
    public function queue($view, array $data = [], $callback = null, $queue = null)
    {
        //使用视图发送一个新消息
        $this->send($view);
    }

    /**
     * Get the array of failed recipients.
     *
     * 获取失败收件人的数组
     *
     * @return array
     */
    public function failures()
    {
        //
    }
}
