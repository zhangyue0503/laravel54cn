<?php

namespace Illuminate\Notifications\Events;

class NotificationSending
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
     * Create a new event instance.
     *
     * 创建新的事件实例
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  string  $channel
     * @return void
     */
    public function __construct($notifiable, $notification, $channel)
    {
        $this->channel = $channel;
        $this->notifiable = $notifiable;
        $this->notification = $notification;
    }
}
