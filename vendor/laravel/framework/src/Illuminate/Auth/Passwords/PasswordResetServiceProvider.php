<?php

namespace Illuminate\Auth\Passwords;

use Illuminate\Support\ServiceProvider;

class PasswordResetServiceProvider extends ServiceProvider
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
        $this->registerPasswordBroker();//注册密码代理实例
    }

    /**
     * Register the password broker instance.
     *
     * 注册密码代理实例
     *
     * @return void
     */
    protected function registerPasswordBroker()
    {
        //在容器中注册共享绑定
        $this->app->singleton('auth.password', function ($app) {
            return new PasswordBrokerManager($app);
        });
        //向容器注册一个绑定
        $this->app->bind('auth.password.broker', function ($app) {
            //从容器中解析给定类型->尝试从本地缓存中获取代理
            return $app->make('auth.password')->broker();
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
        return ['auth.password', 'auth.password.broker'];
    }
}
