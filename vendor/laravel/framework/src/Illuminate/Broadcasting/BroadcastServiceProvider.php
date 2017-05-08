<?php

namespace Illuminate\Broadcasting;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * 指示是否延迟了提供者的加载
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        //在容器中注册共享绑定
        $this->app->singleton(BroadcastManager::class, function ($app) {
            return new BroadcastManager($app);
        });
        //在容器中注册共享绑定
        $this->app->singleton(BroadcasterContract::class, function ($app) {
            return $app->make(BroadcastManager::class)->connection();
        });
        //别名为不同名称的类型
        $this->app->alias(
            BroadcastManager::class, BroadcastingFactory::class
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * 获取提供者提供的服务
     *
     * @return array
     */
    public function provides()
    {
        return [
            BroadcastManager::class,
            BroadcastingFactory::class,
            BroadcasterContract::class,
        ];
    }
}
