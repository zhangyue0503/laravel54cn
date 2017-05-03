<?php

namespace Illuminate\Contracts\Foundation;

use Illuminate\Contracts\Container\Container;

interface Application extends Container
{
    /**
     * Get the version number of the application.
     *
     * 获得应用程序的版本号
     *
     * @return string
     */
    public function version();

    /**
     * Get the base path of the Laravel installation.
     *
     * 得到Laravel安装的基本路径
     *
     * @return string
     */
    public function basePath();

    /**
     * Get or check the current application environment.
     *
     * 获取或检查当前的应用程序环境
     *
     * @return string
     */
    public function environment();

    /**
     * Determine if the application is currently down for maintenance.
     *
     * 确定应用程序当前是否用于维护
     *
     * @return bool
     */
    public function isDownForMaintenance();

    /**
     * Register all of the configured providers.
	 *
	 * 注册所有配置的提供者
     *
     * @return void
     */
    public function registerConfiguredProviders();

    /**
     * Register a service provider with the application.
	 *
	 * 应用程序注册服务提供者
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @param  array  $options
     * @param  bool   $force
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $options = [], $force = false);

    /**
     * Register a deferred provider and service.
     *
     * @param  string  $provider
     * @param  string  $service
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null);

    /**
     * Boot the application's service providers.
	 *
	 * 启动应用程序的服务提供者
     *
     * @return void
     */
    public function boot();

    /**
     * Register a new boot listener.
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booting($callback);

    /**
     * Register a new "booted" listener.
     *
     * 注册一个新的“引导”监听
     *
     * @param  mixed  $callback
     * @return void
     */
    public function booted($callback);

    /**
     * Get the path to the cached services.php file.
     *
     * 获取缓存的services.php文件路径
     *
     * @return string
     */
    public function getCachedServicesPath();
}
