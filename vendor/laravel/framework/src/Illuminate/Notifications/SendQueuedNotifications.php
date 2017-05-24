<?php

namespace Illuminate\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendQueuedNotifications implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The notifiable entities that should receive the notification.
     *
     * 应该接收通知的通知实体
     *
     * @var \Illuminate\Support\Collection
     */
    public $notifiables;

    /**
     * The notification to be sent.
     *
     * 发送的通知
     *
     * @var \Illuminate\Notifications\Notification
     */
    public $notification;

    /**
     * All of the channels to send the notification too.
     *
     * 所有发送通知的通道
     *
     * @var array
     */
    public $channels;

    /**
     * Create a new job instance.
     *
     * 创建一个新的作业实例
     *
     * @param  \Illuminate\Support\Collection  $notifiables
     * @param  \Illuminate\Notifications\Notification  $notification
     * @param  array  $channels
     * @return void
     */
    public function __construct($notifiables, $notification, array $channels = null)
    {
        $this->channels = $channels;
        $this->notifiables = $notifiables;
        $this->notification = $notification;
    }

    /**
     * Send the notifications.
     *
     * 发送通知
     *
     * @param  \Illuminate\Notifications\ChannelManager  $manager
     * @return void
     */
    public function handle(ChannelManager $manager)
    {
        //立即发送通知
        $manager->sendNow($this->notifiables, $this->notification, $this->channels);
    }

    /**
     * Get the display name for the queued job.
     *
     * 获取队列作业的显示名称
     *
     * @return string
     */
    public function displayName()
    {
        return get_class($this->notification);
    }
}
