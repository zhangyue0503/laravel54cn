<?php

namespace Illuminate\Contracts\Routing;

interface Registrar
{
    /**
     * Register a new GET route with the router.
     *
     * 用路由器注册一个新的GET路由
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return void
     */
    public function get($uri, $action);

    /**
     * Register a new POST route with the router.
     *
     * 用路由器注册一个新的POST路由
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return void
     */
    public function post($uri, $action);

    /**
     * Register a new PUT route with the router.
     *
     * 用路由器注册一个新的PUT路由
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return void
     */
    public function put($uri, $action);

    /**
     * Register a new DELETE route with the router.
     *
     * 用路由器注册一个新的DELETE路由
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return void
     */
    public function delete($uri, $action);

    /**
     * Register a new PATCH route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return void
     */
    public function patch($uri, $action);

    /**
     * Register a new OPTIONS route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return void
     */
    public function options($uri, $action);

    /**
     * Register a new route with the given verbs.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string  $action
     * @return void
     */
    public function match($methods, $uri, $action);

    /**
     * Route a resource to a controller.
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array   $options
     * @return void
     */
    public function resource($name, $controller, array $options = []);

    /**
     * Create a route group with shared attributes.
     *
     * @param  array  $attributes
     * @param  \Closure|string  $routes
     * @return void
     */
    public function group(array $attributes, $routes);

    /**
     * Substitute the route bindings onto the route.
     *
     * 将路由绑定替换到路由
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    public function substituteBindings($route);

    /**
     * Substitute the implicit Eloquent model bindings for the route.
     *
     * 为路由替换隐式的Eloquent模型绑定
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    public function substituteImplicitBindings($route);
}
