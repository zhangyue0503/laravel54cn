<?php

namespace Illuminate\Redis;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class RedisServiceProvider extends ServiceProvider
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
        $this->app->singleton('redis', function ($app) {
            //从容器中解析给定类型
            $config = $app->make('config')->get('database.redis');
			//创建一个新的Redis管理实例      从数组中获取值，并将其移除
            return new RedisManager(Arr::pull($config, 'client', 'predis'), $config);
        });
        // 与容器注册绑定
        $this->app->bind('redis.connection', function ($app) {
            return $app['redis']->connection();
        });
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
        return ['redis', 'redis.connection'];
    }
}
