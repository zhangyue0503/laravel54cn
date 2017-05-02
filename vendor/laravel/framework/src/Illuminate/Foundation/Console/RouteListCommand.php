<?php

namespace Illuminate\Foundation\Console;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class RouteListCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'route:list';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'List all registered routes';

    /**
     * The router instance.
     *
     * 路由器实例
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * An array of all the registered routes.
     *
     * 所有注册路径的数组
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routes;

    /**
     * The table headers for the command.
     *
     * 命令的表头
     *
     * @var array
     */
    protected $headers = ['Domain', 'Method', 'URI', 'Name', 'Action', 'Middleware'];

    /**
     * Create a new route command instance.
     *
     * 创建一个新的路由命令实例
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        parent::__construct();//创建一个新的控制台命令实例

        $this->router = $router;
        $this->routes = $router->getRoutes();//获取基础路由集合
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
        if (count($this->routes) == 0) {
            return $this->error("Your application doesn't have any routes.");//将字符串写入错误输出
        }
        //显示控制台的路由信息(将路由编译成可显示格式)
        $this->displayRoutes($this->getRoutes());
    }

    /**
     * Compile the routes into a displayable format.
     *
     * 将路由编译成可显示格式
     *
     * @return array
     */
    protected function getRoutes()
    {
        //                              在每个项目上运行map
        $routes = collect($this->routes)->map(function ($route) {
            return $this->getRouteInformation($route);//获取给定路由的路由信息
        })->all();//获取集合中的所有项目

        if ($sort = $this->option('sort')) {//获取命令选项的值
            $routes = $this->sortRoutes($sort, $routes);//按照给定的元素对路由进行排序
        }

        if ($this->option('reverse')) {//获取命令选项的值
            $routes = array_reverse($routes);
        }

        return array_filter($routes);
    }

    /**
     * Get the route information for a given route.
     *
     * 获取给定路由的路由信息
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    protected function getRouteInformation(Route $route)
    {
        // 通过URI和/或名称过滤路由
        return $this->filterRoute([
            'host'   => $route->domain(),//获取路由定义的域
            'method' => implode('|', $route->methods()),//获取路由响应的HTTP请求method
            'uri'    => $route->uri(),//获取与路由关联的URI
            'name'   => $route->getName(),//获取路由实例的名称
            'action' => $route->getActionName(),//获取路由的action名称
            'middleware' => $this->getMiddleware($route),//在过滤器之前
        ]);
    }

    /**
     * Sort the routes by a given element.
     *
     * 按照给定的元素对路由进行排序
     *
     * @param  string  $sort
     * @param  array  $routes
     * @return array
     */
    protected function sortRoutes($sort, $routes)
    {
        //使用给定的回调或“点”符号对数组进行排序
        return Arr::sort($routes, function ($route) use ($sort) {
            return $route[$sort];
        });
    }

    /**
     * Display the route information on the console.
     *
     * 显示控制台的路由信息
     *
     * @param  array  $routes
     * @return void
     */
    protected function displayRoutes(array $routes)
    {
        //格式输入到文本表
        $this->table($this->headers, $routes);
    }

    /**
     * Get before filters.
     *
     * 在过滤器之前
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     */
    protected function getMiddleware($route)
    {
        //              获取所有的中间件，包括从控制器中定义的->在每个项目上运行map
        return collect($route->gatherMiddleware())->map(function ($middleware) {
            return $middleware instanceof Closure ? 'Closure' : $middleware;
        })->implode(',');//一个给定的键连接的值作为一个字符串
    }

    /**
     * Filter the route by URI and / or name.
     *
     * 通过URI和/或名称过滤路由
     *
     * @param  array  $route
     * @return array|null
     */
    protected function filterRoute(array $route)
    {
        // 获取命令选项的值             确定一个给定的字符串包含另一个字符串        获取命令选项的值
        if (($this->option('name') && ! Str::contains($route['name'], $this->option('name'))) ||
             $this->option('path') && ! Str::contains($route['uri'], $this->option('path')) ||
             $this->option('method') && ! Str::contains($route['method'], $this->option('method'))) {
            return;
        }

        return $route;
    }

    /**
     * Get the console command options.
     *
     * 获得控制台命令选项
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['method', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by method.'],

            ['name', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by name.'],

            ['path', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by path.'],

            ['reverse', 'r', InputOption::VALUE_NONE, 'Reverse the ordering of the routes.'],

            ['sort', null, InputOption::VALUE_OPTIONAL, 'The column (host, method, uri, name, action, middleware) to sort by.', 'uri'],
        ];
    }
}
