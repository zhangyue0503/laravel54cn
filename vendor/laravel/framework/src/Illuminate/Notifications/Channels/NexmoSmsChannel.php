<?php

namespace Illuminate\Notifications\Channels;

use Nexmo\Client as NexmoClient;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\NexmoMessage;

class NexmoSmsChannel
{
    /**
     * The Nexmo client instance.
     *
     * Nexmo客户端实例
     *
     * @var \Nexmo\Client
     */
    protected $nexmo;

    /**
     * The phone number notifications should be sent from.
     *
     * 电话号码通知应该从
     *
     * @var string
     */
    protected $from;

    /**
     * Create a new Nexmo channel instance.
     *
     * 创建一个Nexmo频道实例
     *
     * @param  \Nexmo\Client  $nexmo
     * @param  string  $from
     * @return void
     */
    public function __construct(NexmoClient $nexmo, $from)
    {
        $this->from = $from;
        $this->nexmo = $nexmo;
    }

    /**
     * Send the given notification.
     *
     * 发送给定的通知
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Nexmo\Message\Message
     */
    public function send($notifiable, Notification $notification)
    {
        //                  获取给定驱动程序的通知路由信息
        if (! $to = $notifiable->routeNotificationFor('nexmo')) {
            return;
        }

        $message = $notification->toNexmo($notifiable);

        if (is_string($message)) {
            $message = new NexmoMessage($message);
        }

        return $this->nexmo->message()->send([
            'type' => $message->type,
            'from' => $message->from ?: $this->from,
            'to' => $to,
            'text' => trim($message->content),
        ]);
    }
}
