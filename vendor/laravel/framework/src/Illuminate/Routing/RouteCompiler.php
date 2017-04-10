<?php

namespace Illuminate\Routing;

use Symfony\Component\Routing\Route as SymfonyRoute;

//     路由  编译
class RouteCompiler
{
    /**
     * The route instance.
     *
     * 路由实例
     *
     * @var \Illuminate\Routing\Route
     */
    protected $route;

    /**
     * Create a new Route compiler instance.
     *
     * 创建一个新的Route compiler实例
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    public function __construct($route)
    {
        $this->route = $route;
    }

    /**
     * Compile the route.
     *
     * 编译路由
     *
     * @return \Symfony\Component\Routing\CompiledRoute
     */
    public function compile()
    {
        $optionals = $this->getOptionalParameters(); // 获取路由的可选参数
        //                                              与路由关联的URI
        $uri = preg_replace('/\{(\w+?)\?\}/', '{$1}', $this->route->uri());

        return (
            new SymfonyRoute($uri, $optionals, $this->route->wheres, [], $this->route->domain() ?: '')
        )->compile(); //返回 编译的SymfonyRoute
    }

    /**
     * Get the optional parameters for the route.
     *
     * 获取路由的可选参数
     *
     * @return array
     */
    protected function getOptionalParameters()
    {
        preg_match_all('/\{(\w+?)\?\}/', $this->route->uri(), $matches);

        return isset($matches[1]) ? array_fill_keys($matches[1], null) : [];
    }
}
