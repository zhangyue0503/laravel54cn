<?php

namespace Illuminate\Cache;

use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
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
        $this->app->singleton('cache', function ($app) {
            //创建一个新的缓存管理器实例
            return new CacheManager($app);
        });
        //在容器中注册共享绑定
        $this->app->singleton('cache.store', function ($app) {
            return $app['cache']->driver();
        });
        //在容器中注册共享绑定
        $this->app->singleton('memcached.connector', function () {
            return new MemcachedConnector;//创建一个新的Memcached连接
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
        return [
            'cache', 'cache.store', 'memcached.connector',
        ];
    }
}
