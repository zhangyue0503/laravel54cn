<?php

namespace Illuminate\Notifications\Events;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BroadcastNotificationCreated implements ShouldBroadcast
{
    use Queueable, SerializesModels;

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
     * The notification data.
     *
     * 通知数据
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
     * @param  array  $data
     * @return void
     */
    public function __construct($notifiable, $notification, $data)
    {
        $this->data = $data;
        $this->notifiable = $notifiable;
        $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * 获取该事件应该播放的频道
     *
     * @return array
     */
    public function broadcastOn()
    {
        //                         获取该事件应该播放的频道
        $channels = $this->notification->broadcastOn();

        if (! empty($channels)) {
            return $channels;
        }
        //           私有频道          获取事件的广播频道名称
        return [new PrivateChannel($this->channelName())];
    }

    /**
     * Get the data that should be sent with the broadcasted event.
     *
     * 得到的数据应该发送的广播事件
     *
     * @return array
     */
    public function broadcastWith()
    {
        return array_merge($this->data, [
            'id' => $this->notification->id,
            'type' => get_class($this->notification),
        ]);
    }

    /**
     * Get the broadcast channel name for the event.
     *
     * 获取事件的广播频道名称
     *
     * @return string
     */
    protected function channelName()
    {
        if (method_exists($this->notifiable, 'receivesBroadcastNotificationsOn')) {
            return $this->notifiable->receivesBroadcastNotificationsOn($this->notification);
        }

        $class = str_replace('\\', '.', get_class($this->notifiable));

        return $class.'.'.$this->notifiable->getKey();
    }
}
