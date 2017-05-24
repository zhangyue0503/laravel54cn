<?php

namespace Illuminate\Notifications\Channels;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\Notification;

class MailChannel
{
    /**
     * The mailer implementation.
     *
     * 邮件实现
     *
     * @var \Illuminate\Contracts\Mail\Mailer
     */
    protected $mailer;

    /**
     * The Markdown resolver callback.
     *
     * markdown解析器回调
     *
     * @var \Closure
     */
    protected $markdownResolver;

    /**
     * Create a new mail channel instance.
     *
     * 创建一个新的邮件频道实例
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @return void
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Send the given notification.
     *
     * 发送给定的通知
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        //获取给定驱动程序的通知路由信息
        if (! $notifiable->routeNotificationFor('mail')) {
            return;
        }

        $message = $notification->toMail($notifiable);

        if ($message instanceof Mailable) {
            //使用给定的邮件发送消息
            return $message->send($this->mailer);
        }
        //使用视图发送一条新消息       构建通知的视图
        $this->mailer->send($this->buildView($message), $message->data(), function ($mailMessage) use ($notifiable, $notification, $message) {
            //建立邮件消息
            $this->buildMessage($mailMessage, $notifiable, $notification, $message);
        });
    }

    /**
     * Build the notification's view.
     *
     * 构建通知的视图
     *
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return void
     */
    protected function buildView($message)
    {
        if ($message->view) {
            return $message->view;
        }

        $markdown = call_user_func($this->markdownResolver);

        return [
            //                                                 获取邮件消息的数据数组
            'html' => $markdown->render($message->markdown, $message->data()),
            'text' => $markdown->renderText($message->markdown, $message->data()),
        ];
    }

    /**
     * Build the mail message.
     *
     * 建立邮件消息
     *
     * @param  \Illuminate\Mail\Message  $mailMessage
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return void
     */
    protected function buildMessage($mailMessage, $notifiable, $notification, $message)
    {
        // 地址的邮件消息
        $this->addressMessage($mailMessage, $notifiable, $message);
        //     设置消息的主题                          给定字符串转换为首字母大写
        $mailMessage->subject($message->subject ?: Str::title(
            //将字符串转换为蛇形命名
            Str::snake(class_basename($notification), ' ')
        ));
        //将附件添加到消息中
        $this->addAttachments($mailMessage, $message);

        if (! is_null($message->priority)) {
            //        设置此消息的优先级
            $mailMessage->setPriority($message->priority);
        }
    }

    /**
     * Address the mail message.
     *
     * 地址的邮件消息
     *
     * @param  \Illuminate\Mail\Message  $mailMessage
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return void
     */
    protected function addressMessage($mailMessage, $notifiable, $message)
    {
        //    将“from”和“应答”地址添加到消息中
        $this->addSender($mailMessage, $message);
        //在消息中添加收件人        获得给定消息的接收者
        $mailMessage->to($this->getRecipients($notifiable, $message));
    }

    /**
     * Add the "from" and "reply to" addresses to the message.
     *
     * 将“from”和“应答”地址添加到消息中
     *
     * @param  \Illuminate\Mail\Message  $mailMessage
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return void
     */
    protected function addSender($mailMessage, $message)
    {
        if (! empty($message->from)) {
            // 将“from”地址添加到消息中                 使用“点”符号从数组中获取一个项
            $mailMessage->from($message->from[0], Arr::get($message->from, 1));
        }

        if (! empty($message->replyTo)) {
            //在消息中添加一个应答
            $mailMessage->replyTo($message->replyTo[0], Arr::get($message->replyTo, 1));
        }
    }

    /**
     * Get the recipients of the given message.
     *
     * 获得给定消息的接收者
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return mixed
     */
    protected function getRecipients($notifiable, $message)
    {
        //                                      获取给定驱动程序的通知路由信息
        if (is_string($recipients = $notifiable->routeNotificationFor('mail'))) {
            $recipients = [$recipients];
        }
        //                       在每个项目上运行map
        return collect($recipients)->map(function ($recipient) {
            return is_string($recipient) ? $recipient : $recipient->email;
        })->all();//获取集合中的所有项目
    }

    /**
     * Add the attachments to the message.
     *
     * 将附件添加到消息中
     *
     * @param  \Illuminate\Mail\Message  $mailMessage
     * @param  \Illuminate\Notifications\Messages\MailMessage  $message
     * @return void
     */
    protected function addAttachments($mailMessage, $message)
    {
        foreach ($message->attachments as $attachment) {
            //将文件附加到消息中
            $mailMessage->attach($attachment['file'], $attachment['options']);
        }

        foreach ($message->rawAttachments as $attachment) {
            //将内存中的数据附加为附件
            $mailMessage->attachData($attachment['data'], $attachment['name'], $attachment['options']);
        }
    }

    /**
     * Set the Markdown resolver callback.
     *
     * 设置Markdown解析器回调
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function setMarkdownResolver(Closure $callback)
    {
        $this->markdownResolver = $callback;

        return $this;
    }
}
