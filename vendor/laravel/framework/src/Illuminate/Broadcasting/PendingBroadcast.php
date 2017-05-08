<?php

namespace Illuminate\Broadcasting;

use Illuminate\Contracts\Events\Dispatcher;

class PendingBroadcast
{
    /**
     * The event dispatcher implementation.
     *
     * 事件调度程序实现
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The event instance.
     *
     * 事件实例
     *
     * @var mixed
     */
    protected $event;

    /**
     * Create a new pending broadcast instance.
     *
     * 创建一个新的等待广播实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  mixed  $event
     * @return void
     */
    public function __construct(Dispatcher $events, $event)
    {
        $this->event = $event;
        $this->events = $events;
    }

    /**
     * Broadcast the event to everyone except the current user.
     *
     * 将事件传播给所有人，除了当前的用户
     *
     * @return $this
     */
    public function toOthers()
    {
        if (method_exists($this->event, 'dontBroadcastToCurrentUser')) {
            $this->event->dontBroadcastToCurrentUser();//将当前用户排除在接收广播之外
        }

        return $this;
    }

    /**
     * Handle the object's destruction.
     *
     * 处理对象的破坏
     *
     * @return void
     */
    public function __destruct()
    {
        $this->events->dispatch($this->event);
    }
}
