<?php

namespace Illuminate\Contracts\Broadcasting;

interface ShouldBroadcast
{
    /**
     * Get the channels the event should broadcast on.
     *
     * 获取该事件应该播放的频道
     *
     * @return array
     */
    public function broadcastOn();
}
