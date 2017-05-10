<?php

namespace Illuminate\Cookie;

use Illuminate\Support\ServiceProvider;

class CookieServiceProvider extends ServiceProvider
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
        //在容器中注册共享绑定
        $this->app->singleton('cookie', function ($app) {
            //从容器中解析给定类型
            $config = $app->make('config')->get('session');
            //设置jar的默认路径和域
            return (new CookieJar)->setDefaultPathAndDomain($config['path'], $config['domain'], $config['secure']);
        });
    }
}
