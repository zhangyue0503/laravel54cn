<?php

namespace Illuminate\Bus;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Contracts\Bus\QueueingDispatcher as QueueingDispatcherContract;

class BusServiceProvider extends ServiceProvider
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
		//       在容器中注册共享绑定
        $this->app->singleton(Dispatcher::class, function ($app) {
			//     创建新的命令调度实例
            return new Dispatcher($app, function ($connection = null) use ($app) {
                return $app[QueueFactoryContract::class]->connection($connection);
            });
        });
        // 别名为不同名称的类型
        $this->app->alias(
            Dispatcher::class, DispatcherContract::class
        );
        // 别名为不同名称的类型
        $this->app->alias(
            Dispatcher::class, QueueingDispatcherContract::class
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
            Dispatcher::class,
            DispatcherContract::class,
            QueueingDispatcherContract::class,
        ];
    }
}
