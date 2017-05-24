<?php

namespace Illuminate\Notifications\Messages;

use Illuminate\Bus\Queueable;

class BroadcastMessage
{
    use Queueable;

    /**
     * The data for the notification.
     *
     * 通知数据
     *
     * @var array
     */
    public $data;

    /**
     * Create a new message instance.
     *
     * 创建一个新的消息实例
     *
     * @param  array  $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Set the message data.
     *
     * 设置消息数据
     *
     * @param  array  $data
     * @return $this
     */
    public function data($data)
    {
        $this->data = $data;

        return $this;
    }
}
