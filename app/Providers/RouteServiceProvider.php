<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
	 *
	 * 定义路由模型绑定、模式筛选器等
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot(); //引导一些应用程序服务
    }

    /**
     * Define the routes for the application.
	 *
	 * 定义应用程序的路由
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes(); // 定义应用程序的“api”路由

        $this->mapWebRoutes(); // 定义应用程序的“web”路由

        //
    }

    /**
     * Define the "web" routes for the application.
	 *
	 * 定义应用程序的“web”路由
     *
     * These routes all receive session state, CSRF protection, etc.
	 *
	 * 这些路由所有接收会话状态，CSRF保护，等。
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
	 *
	 * 定义应用程序的“api”路由
     *
     * These routes are typically stateless.
	 *
	 * 这些路由通常是无状态的
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }
}
