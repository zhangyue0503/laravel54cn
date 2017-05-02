<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\RouteCollection;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;

class RouteCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'route:cache';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a route cache file for faster route registration';

    /**
     * The filesystem instance.
     *
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new route command instance.
     *
     * 创建一个新的路由命令实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();//创建一个新的控制台命令实例

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        //调用另一个控制台命令
        $this->call('route:clear');
        //启动应用程序的新副本并获取路由
        $routes = $this->getFreshApplicationRoutes();

        if (count($routes) == 0) {
            //将字符串写入错误输出
            return $this->error("Your application doesn't have any routes.");
        }

        foreach ($routes as $route) {
            $route->prepareForSerialization();//为序列化准备路由实例
        }

        $this->files->put(//写入文件的内容
            //             获取路由的缓存文件的路径        构建路由缓存文件
            $this->laravel->getCachedRoutesPath(), $this->buildRouteCacheFile($routes)
        );
        //将字符串写入信息输出
        $this->info('Routes cached successfully!');
    }

    /**
     * Boot a fresh copy of the application and get the routes.
     *
     * 启动应用程序的新副本并获取路由
     *
     * @return \Illuminate\Routing\RouteCollection
     */
    protected function getFreshApplicationRoutes()
    {
        //                     获取引导目录的路径
        $app = require $this->laravel->bootstrapPath().'/app.php';
        //从容器中解析给定类型                       引导应用程序命令
        $app->make(ConsoleKernelContract::class)->bootstrap();
        //                      获取基础路由集合
        return $app['router']->getRoutes();
    }

    /**
     * Build the route cache file.
     *
     * 构建路由缓存文件
     *
     * @param  \Illuminate\Routing\RouteCollection  $routes
     * @return string
     */
    protected function buildRouteCacheFile(RouteCollection $routes)
    {
        //                获取文件的内容
        $stub = $this->files->get(__DIR__.'/stubs/routes.stub');

        return str_replace('{{routes}}', base64_encode(serialize($routes)), $stub);
    }
}
