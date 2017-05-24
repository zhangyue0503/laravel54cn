<?php

namespace Illuminate\Notifications;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as ModelCollection;

class NotificationSender
{
    /**
     * The notification manager instance.
     *
     * 通知管理器实例
     *
     * @var \Illuminate\Notifications\ChannelManager
     */
    protected $manager;

    /**
     * The Bus dispatcher instance.
     *
     * 公交调度程序实例
     *
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $bus;

    /**
     * The event dispatcher.
     *
     * 事件调度器
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Create a new notification sender instance.
     *
     * 创建一个新的通知发送方实例
     *
     * @param  \Illuminate\Notifications\ChannelManager  $manager
     * @param  \Illuminate\Contracts\Bus\Dispatcher  $bus
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct($manager, $bus, $events)
    {
        $this->bus = $bus;
        $this->events = $events;
        $this->manager = $manager;
    }

    /**
     * Send the given notification to the given notifiable entities.
     *
     * 将给定的通知发送给指定的通知实体
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification)
    {
        //                  如果需要，将notifiables格式化为集合/数组
        $notifiables = $this->formatNotifiables($notifiables);

        if ($notification instanceof ShouldQueue) {
            //          对给定的通知实例排队
            return $this->queueNotification($notifiables, $notification);
        }
        //       立即发送通知
        return $this->sendNow($notifiables, $notification);
    }

    /**
     * Send the given notification immediately.
     *
     * 立即发送通知
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @param  array  $channels
     * @return void
     */
    public function sendNow($notifiables, $notification, array $channels = null)
    {
        //              如果需要，将notifiables格式化为集合/数组
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            //               生成一个版本4(随机)UUID
            $notificationId = Uuid::uuid4()->toString();

            if (empty($viaChannels = $channels ?: $notification->via($notifiable))) {
                continue;
            }

            foreach ($viaChannels as $channel) {
                //通过通道将给定的通知发送给指定的通知
                $this->sendToNotifiable($notifiable, $notificationId, clone $original, $channel);
            }
        }
    }

    /**
     * Send the given notification to the given notifiable via a channel.
     *
     * 通过通道将给定的通知发送给指定的通知
     *
     * @param  mixed  $notifiable
     * @param  string  $id
     * @param  mixed  $notification
     * @param  string  $channel
     * @return void
     */
    protected function sendToNotifiable($notifiable, $id, $notification, $channel)
    {
        if (! $notification->id) {
            $notification->id = $id;
        }
        //            确定是否可以发送通知
        if (! $this->shouldSendNotification($notifiable, $notification, $channel)) {
            return;
        }
        //                    获取驱动实例
        $response = $this->manager->driver($channel)->send($notifiable, $notification);
        //将事件触发，直到返回第一个非空响应
        $this->events->dispatch(
            new Events\NotificationSent($notifiable, $notification, $channel, $response)
        );
    }

    /**
     * Determines if the notification can be sent.
     *
     * 确定是否可以发送通知
     *
     * @param  mixed  $notifiable
     * @param  mixed  $notification
     * @param  string  $channel
     * @return bool
     */
    protected function shouldSendNotification($notifiable, $notification, $channel)
    {
        //                发送事件并调用侦听器
        return $this->events->until(
            new Events\NotificationSending($notifiable, $notification, $channel)
        ) !== false;
    }

    /**
     * Queue the given notification instances.
     *
     * 对给定的通知实例排队
     *
     * @param  mixed  $notifiables
     * @param  array[\Illuminate\Notifications\Channels\Notification]  $notification
     * @return void
     */
    protected function queueNotification($notifiables, $notification)
    {
        //                     如果需要，将notifiables格式化为集合/数组
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            //              生成一个版本4(随机)UUID
            $notificationId = Uuid::uuid4()->toString();

            foreach ($original->via($notifiable) as $channel) {
                $notification = clone $original;

                $notification->id = $notificationId;
                //向适当的处理程序分派一个命令
                $this->bus->dispatch(
                    //                                 如果需要，将notifiables格式化为集合/数组
                    (new SendQueuedNotifications($this->formatNotifiables($notifiable), $notification, [$channel]))
                            ->onConnection($notification->connection)//为作业设置所需的连接
                            ->onQueue($notification->queue)//为作业设置所需的队列
                            ->delay($notification->delay)//为工作设定期望的延迟
                );
            }
        }
    }

    /**
     * Format the notifiables into a Collection / array if necessary.
     *
     * 如果需要，将notifiables格式化为集合/数组
     *
     * @param  mixed  $notifiables
     * @return ModelCollection|array
     */
    protected function formatNotifiables($notifiables)
    {
        if (! $notifiables instanceof Collection && ! is_array($notifiables)) {
            return $notifiables instanceof Model
                            ? new ModelCollection([$notifiables]) : [$notifiables];
        }

        return $notifiables;
    }
}
