<?php

namespace Illuminate\Events;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        //          在容器中注册共享绑定
        $this->app->singleton('events', function ($app) {
            //      创建一个新的事件调度实例->设置队列解析器实现()
            return (new Dispatcher($app))->setQueueResolver(function () use ($app) {
                //        从容器中解析给定类型
                return $app->make(QueueFactoryContract::class);
            });
        });
    }
}
