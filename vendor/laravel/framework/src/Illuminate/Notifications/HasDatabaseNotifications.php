<?php

namespace Illuminate\Notifications;

trait HasDatabaseNotifications
{
    /**
     * Get the entity's notifications.
     * 获取实体的通知
     */
    public function notifications()
    {
        //定义多态的一对多关系
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
                            ->orderBy('created_at', 'desc');//向查询添加一个“order by”子句
    }

    /**
     * Get the entity's read notifications.
     * 获取实体的已读通知
     */
    public function readNotifications()
    {
        return $this->notifications()// 获取实体的通知
                            ->whereNotNull('read_at');//向查询添加“where not null”子句
    }

    /**
     * Get the entity's unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->notifications()// 获取实体的通知
                            ->whereNull('read_at');// 向查询添加“where null”子句
    }
}
