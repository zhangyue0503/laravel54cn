<?php

namespace Illuminate\Notifications;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Notifications\Factory as FactoryContract;
use Illuminate\Contracts\Notifications\Dispatcher as DispatcherContract;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Boot the application services.
     *
     * 启动应用程序服务
     *
     * @return void
     */
    public function boot()
    {
        //注册视图文件命名空间
        $this->loadViewsFrom(__DIR__.'/resources/views', 'notifications');
        //确定我们是否在控制台中运行
        if ($this->app->runningInConsole()) {
            //注册发布命令发布的路径
            $this->publishes([
                __DIR__.'/resources/views' => resource_path('views/vendor/notifications'),
            ], 'laravel-notifications');
        }
    }

    /**
     * Register the service provider.
     *
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        //在容器中注册共享绑定        频道管理器
        $this->app->singleton(ChannelManager::class, function ($app) {
            return new ChannelManager($app);
        });
        //将类型别名别名为不同的名称
        $this->app->alias(
            //                        调度程序
            ChannelManager::class, DispatcherContract::class
        );

        $this->app->alias(
            //                        通知工厂
            ChannelManager::class, FactoryContract::class
        );
    }
}
