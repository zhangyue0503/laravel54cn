<?php

namespace Illuminate\Notifications\Messages;

class DatabaseMessage
{
    /**
     * The data that should be stored with the notification.
     *
     * 应该在通知中存储的数据
     *
     * @var array
     */
    public $data = [];

    /**
     * Create a new database message.
     *
     * 创建一个新的数据库消息
     *
     * @param  array  $data
     * @return void
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
