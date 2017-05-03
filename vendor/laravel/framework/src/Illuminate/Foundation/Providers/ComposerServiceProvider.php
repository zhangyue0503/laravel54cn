<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Support\Composer;
use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
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
        $this->app->singleton('composer', function ($app) {
            //创建新的Composer管理器实例(,获取Laravel安装的基础路径)
            return new Composer($app['files'], $app->basePath());
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
        return ['composer'];
    }
}
