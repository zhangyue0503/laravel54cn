<?php

namespace Illuminate\Notifications\Events;

class NotificationFailed
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
     * The data needed to process this failure.
     *
     * 处理此故障所需的数据
     *
     * @var array
     */
    public $data = [];

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  string  $channel
     * @param  array  $data
     * @return void
     */
    public function __construct($notifiable, $notification, $channel, $data = [])
    {
        $this->data = $data;
        $this->channel = $channel;
        $this->notifiable = $notifiable;
        $this->notification = $notification;
    }
}
