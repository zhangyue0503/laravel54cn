<?php

namespace Illuminate\Support;
//聚焦服务提供者
class AggregateServiceProvider extends ServiceProvider
{
    /**
     * The provider class names.
     *
     * 提供者类名
     *
     * @var array
     */
    protected $providers = [];

    /**
     * An array of the service provider instances.
     *
     * 服务提供者实例数组
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Register the service provider.
     *
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->instances = [];

        foreach ($this->providers as $provider) {
            //                                 应用程序注册服务提供者
            $this->instances[] = $this->app->register($provider);
        }
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
        $provides = [];

        foreach ($this->providers as $provider) {
            //                     从类名解析服务提供者实例
            $instance = $this->app->resolveProvider($provider);
            //                                  获取提供者提供的服务
            $provides = array_merge($provides, $instance->provides());
        }

        return $provides;
    }
}
