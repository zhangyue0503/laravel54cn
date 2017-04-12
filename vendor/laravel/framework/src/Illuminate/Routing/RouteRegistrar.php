<?php

namespace Illuminate\Routing;

use Closure;
use BadMethodCallException;
use InvalidArgumentException;

class RouteRegistrar
{
    /**
     * The router instance.
     *
     * 路由实例
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The attributes to pass on to the router.
     *
     * 传递给路由器的属性
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The methods to dynamically pass through to the router.
     *
     * 动态传递给路由器的方法
     *
     * @var array
     */
    protected $passthru = [
        'get', 'post', 'put', 'patch', 'delete', 'options', 'any',
    ];

    /**
     * The attributes that can be set through this class.
     *
     * 可以通过这个类设置的属性
     *
     * @var array
     */
    protected $allowedAttributes = [
        'as', 'domain', 'middleware', 'name', 'namespace', 'prefix',
    ];

    /**
     * The attributes that are aliased.
     *
     * 别名属性
     *
     * @var array
     */
    protected $aliases = [
        'name' => 'as',
    ];

    /**
     * Create a new route registrar instance.
     *
     * 创建一个新的路由注册实例
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Set the value for a given attribute.
     *
     * 为给定属性设置值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function attribute($key, $value)
    {
        if (! in_array($key, $this->allowedAttributes)) {
            throw new InvalidArgumentException("Attribute [{$key}] does not exist.");
        }

        $this->attributes[array_get($this->aliases, $key, $key)] = $value;

        return $this;
    }

    /**
     * Route a resource to a controller.
     *
     * 将资源路由到控制器
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return void
     */
    public function resource($name, $controller, array $options = [])
    {
        $this->router->resource($name, $controller, $this->attributes + $options); //将资源路由到控制器 Illuminate\Routing\Router::resource()
    }

    /**
     * Create a route group with shared attributes.
     *
     * 创建具有共享属性的路由组
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function group($callback)
    {
        $this->router->group($this->attributes, $callback);//创建具有共享属性的路由组 Illuminate\Routing\Router::group()
    }

    /**
     * Register a new route with the given verbs.
     *
     * 用给定的动词注册新路径
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function match($methods, $uri, $action = null)
    {
        //                   用给定的动词注册新路由(,,将动作编译成包含属性的数组)
        return $this->router->match($methods, $uri, $this->compileAction($action));
    }

    /**
     * Register a new route with the router.
     *
     * 用路由器注册新路由
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    protected function registerRoute($method, $uri, $action = null)
    {
        if (! is_array($action)) {
            $action = array_merge($this->attributes, $action ? ['uses' => $action] : []);
        }
        //                                      将动作编译成包含属性的数组
        return $this->router->{$method}($uri, $this->compileAction($action));
    }

    /**
     * Compile the action into an array including the attributes.
     *
     * 将动作编译成包含属性的数组
     *
     * @param  \Closure|array|string|null  $action
     * @return array
     */
    protected function compileAction($action)
    {
        if (is_null($action)) {
            return $this->attributes;
        }

        if (is_string($action) || $action instanceof Closure) {
            $action = ['uses' => $action];
        }

        return array_merge($this->attributes, $action);
    }

    /**
     * Dynamically handle calls into the route registrar.
     *
     * 动态调用路由注册程序
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Illuminate\Routing\Route|$this
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, $this->passthru)) {
            return $this->registerRoute($method, ...$parameters);  //用路由器注册新路由
        }

        if (in_array($method, $this->allowedAttributes)) {
            return $this->attribute($method, $parameters[0]); //为给定属性设置值
        }

        throw new BadMethodCallException("Method [{$method}] does not exist.");
    }
}
