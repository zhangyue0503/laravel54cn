<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Routing\UrlGenerator;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The controller namespace for the application.
     *
     * @var string|null
     */
    protected $namespace;

    /**
     * Bootstrap any application services.
	 *
	 * 引导一些应用程序服务
     *
     * @return void
     */
    public function boot()
    {
        $this->setRootControllerNamespace(); // 为应用程序设置根控制器命名空间

        if ($this->app->routesAreCached()) { // 确定应用程序路由是否被缓存
            $this->loadCachedRoutes(); // 为应用程序加载缓存的路由
        } else {
            $this->loadRoutes();

            $this->app->booted(function () {
                $this->app['router']->getRoutes()->refreshNameLookups();
            });
        }
    }

    /**
     * Set the root controller namespace for the application.
	 *
	 * 为应用程序设置根控制器命名空间
     *
     * @return void
     */
    protected function setRootControllerNamespace()
    {
        if (! is_null($this->namespace)) {
            $this->app[UrlGenerator::class]->setRootControllerNamespace($this->namespace);
        }
    }

    /**
     * Load the cached routes for the application.
	 *
	 * 为应用程序加载缓存的路由
     *
     * @return void
     */
    protected function loadCachedRoutes()
    {
        $this->app->booted(function () {
            require $this->app->getCachedRoutesPath();
        });
    }

    /**
     * Load the application routes.
	 *
	 * 加载应用程序路由
     *
     * @return void
     */
    protected function loadRoutes()
    {
        if (method_exists($this, 'map')) {
			// 调用给定的闭包/类@方法并注入它的依赖项(this->map())
            $this->app->call([$this, 'map']);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Pass dynamic methods onto the router instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(
            [$this->app->make(Router::class), $method], $parameters
        );
    }
}
