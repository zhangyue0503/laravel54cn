<?php

namespace Illuminate\Notifications\Channels;

use RuntimeException;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;

class BroadcastChannel
{
    /**
     * The event dispatcher.
     *
     * 事件调度器
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Create a new database channel.
     *
     * 创建一个新的数据库频道
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Send the given notification.
     *
     * 发送给定的通知
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|null
     */
    public function send($notifiable, Notification $notification)
    {
        //          获取通知的数据
        $message = $this->getData($notifiable, $notification);

        //            创建一个新的事件实例
        $event = new BroadcastNotificationCreated(
            $notifiable, $notification, is_array($message) ? $message : $message->data
        );

        if ($message instanceof BroadcastMessage) {
            $event->onConnection($message->connection)//为作业设置所需的连接
                  ->onQueue($message->queue);//设置工作所需的队列
        }

        return $this->events->dispatch($event);//将事件触发，直到返回第一个非空响应
    }

    /**
     * Get the data for the notification.
     *
     * 获取通知的数据
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return mixed
     *
     * @throws \RuntimeException
     */
    protected function getData($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toBroadcast')) {
            return $notification->toBroadcast($notifiable);
        }

        if (method_exists($notification, 'toArray')) {
            return $notification->toArray($notifiable);
        }

        throw new RuntimeException(
            'Notification is missing toArray method.'
        );
    }
}
