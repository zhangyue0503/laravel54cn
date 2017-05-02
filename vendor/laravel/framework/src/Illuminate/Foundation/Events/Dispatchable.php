<?php

namespace Illuminate\Foundation\Events;

trait Dispatchable
{
    /**
     * Dispatch the event with the given arguments.
     *
     * 用给定的参数来分派事件
     *
     * @return void
     */
    public static function dispatch()
    {
        //调度事件并调用侦听器
        return event(new static(...func_get_args()));
    }

    /**
     * Broadcast the event with the given arguments.
     *
     * 用给定的参数对事件进行广播
     *
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public static function broadcast()
    {
        //开始广播一个事件
        return broadcast(new static(...func_get_args()));
    }
}
