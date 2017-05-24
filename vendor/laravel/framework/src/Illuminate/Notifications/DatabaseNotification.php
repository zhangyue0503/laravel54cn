<?php

namespace Illuminate\Notifications;

use Illuminate\Database\Eloquent\Model;

class DatabaseNotification extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * 表示如果id是自动递增的
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The table associated with the model.
     *
     * 与模型相关联的表
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * The guarded attributes on the model.
     *
     * 模型中有保护的属性
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * 应该将这些属性转换为原生类型
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Get the notifiable entity that the notification belongs to.
     *
     * 获取通知所属的通知实体
     */
    public function notifiable()
    {
        //           定义一个多态，逆一对一或多关系
        return $this->morphTo();
    }

    /**
     * Mark the notification as read.
     *
     * 将通知标记为读
     *
     * @return void
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            //用属性数组填充模型。从批量赋值        为模型获取一个新的时间戳    将模型保存到数据库中
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    /**
     * Determine if a notification has been read.
     *
     * 确定是否已读取通知
     *
     * @return bool
     */
    public function read()
    {
        return $this->read_at !== null;
    }

    /**
     * Determine if a notification has not been read.
     *
     * 确定是否有通知未被读取
     *
     * @return bool
     */
    public function unread()
    {
        return $this->read_at === null;
    }

    /**
     * Create a new database notification collection instance.
     *
     * 创建一个新的数据库通知收集实例
     *
     * @param  array  $models
     * @return \Illuminate\Notifications\DatabaseNotificationCollection
     */
    public function newCollection(array $models = [])
    {
        return new DatabaseNotificationCollection($models);
    }
}
