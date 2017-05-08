<?php

namespace Illuminate\Broadcasting;

use Illuminate\Support\Facades\Broadcast;
//配合套接字
trait InteractsWithSockets
{
    /**
     * The socket ID for the user that raised the event.
     *
     * 用于唤起事件的用户的套接字ID
     *
     * @var string|null
     */
    public $socket;

    /**
     * Exclude the current user from receiving the broadcast.
     *
     * 将当前用户排除在接收广播之外
     *
     * @return $this
     */
    public function dontBroadcastToCurrentUser()
    {
        //获取给定请求的套接字ID
        $this->socket = Broadcast::socket();

        return $this;
    }

    /**
     * Broadcast the event to everyone.
     *
     * 将事件传播给每个人
     *
     * @return $this
     */
    public function broadcastToEveryone()
    {
        $this->socket = null;

        return $this;
    }
}
