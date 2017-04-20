<?php

namespace Illuminate\Redis;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class RedisServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
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
            $config = $app->make('config')->get('database.redis');
			//创建一个新的Redis管理实例
            return new RedisManager(Arr::pull($config, 'client', 'predis'), $config);
        });

        $this->app->bind('redis.connection', function ($app) {
            return $app['redis']->connection();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['redis', 'redis.connection'];
    }
}
