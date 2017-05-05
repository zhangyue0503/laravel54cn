<?php

namespace Illuminate\Auth\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends Notification
{
    /**
     * The password reset token.
     *
     * 密码重置令牌
     *
     * @var string
     */
    public $token;

    /**
     * Create a notification instance.
     *
     * 创建一个通知实例
     *
     * @param  string  $token
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's channels.
     *
     * 获取通知的通道
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * 构建通知的邮件表示
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('You are receiving this email because we received a password reset request for your account.')//在通知中添加一行文本
            ->action('Reset Password', route('password.reset', $this->token))//配置“调用操作”按钮
            ->line('If you did not request a password reset, no further action is required.');//在通知中添加一行文本
    }
}
