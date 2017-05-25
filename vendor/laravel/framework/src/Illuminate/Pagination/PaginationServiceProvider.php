<?php

namespace Illuminate\Pagination;

use Illuminate\Support\ServiceProvider;

class PaginationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * 引导任何应用程序服务
     *
     * @return void
     */
    public function boot()
    {
        //注册视图文件命名空间
        $this->loadViewsFrom(__DIR__.'/resources/views', 'pagination');
        //        确定我们是否在控制台中运行
        if ($this->app->runningInConsole()) {
            //注册发布命令发布的路径
            $this->publishes([
                __DIR__.'/resources/views' => resource_path('views/vendor/pagination'),
            ], 'laravel-pagination');
        }
    }

    /**
     * Register the service provider.
     *
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        //      设置视图工厂解析器回调
        Paginator::viewFactoryResolver(function () {
            return $this->app['view'];
        });
        //       设置当前请求路径解析器回调
        Paginator::currentPathResolver(function () {
            return $this->app['request']->url();//从请求获取URL（无查询字符串）
        });
        //      设置当前页面解析器回调
        Paginator::currentPageResolver(function ($pageName = 'page') {
            $page = $this->app['request']->input($pageName);//从请求中检索输入项

            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
                return $page;
            }

            return 1;
        });
    }
}
