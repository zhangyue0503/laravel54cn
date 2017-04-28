<?php

namespace Illuminate\Support\Testing\Fakes;

use PHPUnit_Framework_Assert as PHPUnit;
use Illuminate\Contracts\Events\Dispatcher;
//伪事件
class EventFake implements Dispatcher
{
    /**
     * All of the events that have been dispatched keyed by type.
     *
     * 所有被按类型键入的事件
     *
     * @var array
     */
    protected $events = [];

    /**
     * Assert if an event was dispatched based on a truth-test callback.
     *
     * 断言如果一个事件是基于真实测试回调而被分派的
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return void
     */
    public function assertDispatched($event, $callback = null)
    {
        PHPUnit::assertTrue(
            //获取所有匹配真实测试回调的事件->计数集合中的项目数
            $this->dispatched($event, $callback)->count() > 0,
            "The expected [{$event}] event was not dispatched."
        );
    }

    /**
     * Determine if an event was dispatched based on a truth-test callback.
     *
     * 确定一个事件是否基于真实测试的回调
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotDispatched($event, $callback = null)
    {
        PHPUnit::assertTrue(
            //获取所有匹配真实测试回调的事件->计数集合中的项目数
            $this->dispatched($event, $callback)->count() === 0,
            "The unexpected [{$event}] event was dispatched."
        );
    }

    /**
     * Get all of the events matching a truth-test callback.
     *
     * 获取所有匹配真实测试回调的事件
     *
     * @param  string  $event
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function dispatched($event, $callback = null)
    {
        if (! $this->hasDispatched($event)) {//确定给定类是否有存储的命令
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };
        //                                          在每个项目上运行过滤器
        return collect($this->events[$event])->filter(function ($arguments) use ($callback) {
            return $callback(...$arguments);
        });
    }

    /**
     * Determine if the given event has been dispatched.
     *
     * 确定给定事件是否已被发送
     *
     * @param  string  $event
     * @return bool
     */
    public function hasDispatched($event)
    {
        return isset($this->events[$event]) && ! empty($this->events[$event]);
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * 用分配器注册一个事件侦听器
     *
     * @param  string|array  $events
     * @param  mixed  $listener
     * @return void
     */
    public function listen($events, $listener)
    {
        //
    }

    /**
     * Determine if a given event has listeners.
     *
     * 确定给定事件是否有侦听器
     *
     * @param  string  $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        //
    }

    /**
     * Register an event and payload to be dispatched later.
     *
     * 注册事件和有效载荷稍后发送
     *
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function push($event, $payload = [])
    {
        //
    }

    /**
     * Register an event subscriber with the dispatcher.
     *
     * 使用分配器注册事件订阅服务器
     *
     * @param  object|string  $subscriber
     * @return void
     */
    public function subscribe($subscriber)
    {
        //
    }

    /**
     * Flush a set of pushed events.
     *
     * 刷新一组被推的事件
     *
     * @param  string  $event
     * @return void
     */
    public function flush($event)
    {
        //
    }

    /**
     * Fire an event and call the listeners.
     *
     * 触发事件并调用监听器
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = false)
    {
        return $this->dispatch($event, $payload, $halt);//触发事件并调用监听器
    }

    /**
     * Fire an event and call the listeners.
     *
     * 触发事件并调用监听器
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        $name = is_object($event) ? get_class($event) : (string) $event;

        $this->events[$name][] = func_get_args();
    }

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * 从调度程序中移除一组侦听器
     *
     * @param  string  $event
     * @return void
     */
    public function forget($event)
    {
        //
    }

    /**
     * Forget all of the queued listeners.
     *
     * 忘记所有排队的监听器
     *
     * @return void
     */
    public function forgetPushed()
    {
        //
    }

    /**
     * Dispatch an event and call the listeners.
     *
     * 调度事件并调用侦听器
     *
     * @param  string|object $event
     * @param  mixed $payload
     * @return void
     */
    public function until($event, $payload = [])
    {
        //
    }
}
