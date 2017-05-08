<?php

namespace Illuminate\Notifications;

use Illuminate\Queue\SerializesModels;

class Notification
{
    use SerializesModels;

    /**
     * The unique identifier for the notification.
     *
     * @var string
     */
    public $id;

    /**
     * Get the channels the event should broadcast on.
     *
     * 获取该事件应该播放的频道
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
