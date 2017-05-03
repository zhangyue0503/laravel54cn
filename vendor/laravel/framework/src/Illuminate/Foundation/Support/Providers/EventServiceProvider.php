<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * 应用程序的事件处理程序映射
     *
     * @var array
     */
    protected $listen = [];

    /**
     * The subscriber classes to register.
     *
     * 注册用户类
     *
     * @var array
     */
    protected $subscribe = [];

    /**
     * Register the application's event listeners.
     *
     * 注册应用程序的事件侦听器
     *
     * @return void
     */
    public function boot()
    {
        //获取事件和处理程序
        foreach ($this->listens() as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);//用分配器注册事件监听器
            }
        }

        foreach ($this->subscribe as $subscriber) {
            Event::subscribe($subscriber);//使用分配器注册事件订阅服务器
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        //
    }

    /**
     * Get the events and handlers.
     *
     * 获取事件和处理程序
     *
     * @return array
     */
    public function listens()
    {
        return $this->listen;
    }
}
