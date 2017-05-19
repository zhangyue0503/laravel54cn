<?php

namespace Illuminate\Hashing;

use Illuminate\Support\ServiceProvider;

class HashServiceProvider extends ServiceProvider
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
        $this->app->singleton('hash', function () {
            return new BcryptHasher;
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
        return ['hash'];
    }
}
