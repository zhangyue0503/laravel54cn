<?php

namespace Illuminate\Contracts\Events;

interface Dispatcher
{
    /**
     * Register an event listener with the dispatcher.
     *
     * 用分配器注册事件监听器
     *
     * @param  string|array  $events
     * @param  mixed  $listener
     * @return void
     */
    public function listen($events, $listener);

    /**
     * Determine if a given event has listeners.
     *
     * 确定一个给定事件是否有监听器
     *
     * @param  string  $eventName
     * @return bool
     */
    public function hasListeners($eventName);

    /**
     * Register an event subscriber with the dispatcher.
     *
     * 使用分派器注册事件订阅者
     *
     * @param  object|string  $subscriber
     * @return void
     */
    public function subscribe($subscriber);

    /**
     * Dispatch an event and call the listeners.
     *
     * 发送事件并调用侦听器
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @return array|null
     */
    public function until($event, $payload = []);

    /**
     * Fire an event until the first non-null response is returned.
     *
     * 将事件触发，直到返回第一个非空响应
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false);

    /**
     * Register an event and payload to be fired later.
     *
     * 注册一个事件和有效负载，稍后将被触发
     *
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function push($event, $payload = []);

    /**
     * Flush a set of pushed events.
     *
     * 刷新一组被推事件
     *
     * @param  string  $event
     * @return void
     */
    public function flush($event);

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * 从调度程序中删除一组侦听器
     *
     * @param  string  $event
     * @return void
     */
    public function forget($event);

    /**
     * Forget all of the queued listeners.
     *
     * 忘记所有排队的侦听器
     *
     * @return void
     */
    public function forgetPushed();
}
