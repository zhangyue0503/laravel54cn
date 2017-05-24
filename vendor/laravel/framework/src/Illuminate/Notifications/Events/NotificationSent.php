<?php

namespace Illuminate\Notifications\Events;

class NotificationSent
{
    /**
     * The notifiable entity who received the notification.
     *
     * 收到通知的通知实体
     *
     * @var mixed
     */
    public $notifiable;

    /**
     * The notification instance.
     *
     * 通知实例
     *
     * @var \Illuminate\Notifications\Notification
     */
    public $notification;

    /**
     * The channel name.
     *
     * 频道名称
     *
     * @var string
     */
    public $channel;

    /**
     * The channel's response.
     *
     * 频道的响应
     *
     * @var mixed
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * 创建一个事件实例
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  string  $channel
     * @param  mixed  $response
     * @return void
     */
    public function __construct($notifiable, $notification, $channel, $response = null)
    {
        $this->channel = $channel;
        $this->response = $response;
        $this->notifiable = $notifiable;
        $this->notification = $notification;
    }
}
