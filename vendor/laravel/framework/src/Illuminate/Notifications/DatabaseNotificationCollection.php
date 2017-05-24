<?php

namespace Illuminate\Notifications;

use Illuminate\Database\Eloquent\Collection;

class DatabaseNotificationCollection extends Collection
{
    /**
     * Mark all notification as read.
     *
     * 将所有通知标记为读
     *
     * @return void
     */
    public function markAsRead()
    {
        //在每个项目上执行回调
        $this->each(function ($notification) {
            //将通知标记为读
            $notification->markAsRead();
        });
    }
}
