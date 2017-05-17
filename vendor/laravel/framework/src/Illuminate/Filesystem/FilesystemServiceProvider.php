<?php

namespace Illuminate\Filesystem;

use Illuminate\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
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
        $this->registerNativeFilesystem();//注册本地文件系统实现

        $this->registerFlysystem();//注册基于驱动程序的文件系统
    }

    /**
     * Register the native filesystem implementation.
     *
     * 注册本地文件系统实现
     *
     * @return void
     */
    protected function registerNativeFilesystem()
    {
        //在容器中注册共享绑定
        $this->app->singleton('files', function () {
            return new Filesystem;
        });
    }

    /**
     * Register the driver based filesystem.
     *
     * 注册基于驱动程序的文件系统
     *
     * @return void
     */
    protected function registerFlysystem()
    {
        //注册文件系统管理器
        $this->registerManager();
        //在容器中注册共享绑定
        $this->app->singleton('filesystem.disk', function () {
            //                                       获取默认的文件驱动程序
            return $this->app['filesystem']->disk($this->getDefaultDriver());
        });

        $this->app->singleton('filesystem.cloud', function () {
            //                                      获取默认的基于云的文件驱动程序
            return $this->app['filesystem']->disk($this->getCloudDriver());
        });
    }

    /**
     * Register the filesystem manager.
     *
     * 注册文件系统管理器
     *
     * @return void
     */
    protected function registerManager()
    {
        //在容器中注册共享绑定
        $this->app->singleton('filesystem', function () {
            return new FilesystemManager($this->app);
        });
    }

    /**
     * Get the default file driver.
     *
     * 获取默认的文件驱动程序
     *
     * @return string
     */
    protected function getDefaultDriver()
    {
        return $this->app['config']['filesystems.default'];
    }

    /**
     * Get the default cloud based file driver.
     *
     * 获取默认的基于云的文件驱动程序
     *
     * @return string
     */
    protected function getCloudDriver()
    {
        return $this->app['config']['filesystems.cloud'];
    }
}
