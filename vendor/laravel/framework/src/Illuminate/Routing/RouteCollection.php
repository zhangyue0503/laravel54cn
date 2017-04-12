<?php

namespace Illuminate\Routing;

use Countable;
use ArrayIterator;
use IteratorAggregate;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
//路由集合
class RouteCollection implements Countable, IteratorAggregate
{
    /**
     * An array of the routes keyed by method.
     *
     * 用方法控制的路由数组
     *
     * @var array
     */
    protected $routes = [];

    /**
     * An flattened array of all of the routes.
     *
     * 所有路由的平级数组
     *
     * @var array
     */
    protected $allRoutes = [];

    /**
     * A look-up table of routes by their names.
     *
     * 路由查找表的名称
     *
     * @var array
     */
    protected $nameList = [];

    /**
     * A look-up table of routes by controller action.
     *
     * 通过控制器动作的路由查找表
     *
     * @var array
     */
    protected $actionList = [];

    /**
     * Add a Route instance to the collection.
     *
     * 将路由实例添加到集合中
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    public function add(Route $route)
    {
        $this->addToCollections($route); //将给定的路由添加到路由数组中

        $this->addLookups($route); // 如果有必要，将路由添加到任何查找表

        return $route;
    }

    /**
     * Add the given route to the arrays of routes.
     *
     * 将给定的路由添加到路由数组中
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function addToCollections($route)
    {
        //                 获取路由定义的域 获取与路由关联的URI
        $domainAndUri = $route->domain().$route->uri();
        //       获取路由响应的HTTP请求method
        foreach ($route->methods() as $method) {
            $this->routes[$method][$domainAndUri] = $route;
        }

        $this->allRoutes[$method.$domainAndUri] = $route;
    }

    /**
     * Add the route to any look-up tables if necessary.
     *
     * 如果有必要，将路由添加到任何查找表
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function addLookups($route)
    {
        // If the route has a name, we will add it to the name look-up table so that we
        // will quickly be able to find any route associate with a name and not have
        // to iterate through every route every time we need to perform a look-up.
        //
        // 如果路由有一个名称，我们将把它添加到名称查找表中，以便我们能够很快找到任何与名字关联的路径，并且不需要每次查找时都遍历每一条路由
        //
        $action = $route->getAction(); //获取路由的动作数组

        if (isset($action['as'])) {
            $this->nameList[$action['as']] = $route;
        }

        // When the route is routing to a controller we will also store the action that
        // is used by the route. This will let us reverse route to controllers while
        // processing a request and easily generate URLs to the given controllers.
        //
        // 当路由路由到控制器时，我们也将存储路由所使用的动作
        // 这将让我们反转路由控制器，同时处理一个请求，并容易生成网址给给定的控制器
        //
        if (isset($action['controller'])) {
            $this->addToActionList($action, $route); // 添加到控制器动作字典的路由
        }
    }

    /**
     * Add a route to the controller action dictionary.
     *
     * 添加到控制器动作字典的路由
     *
     * @param  array  $action
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function addToActionList($action, $route)
    {
        $this->actionList[trim($action['controller'], '\\')] = $route;
    }

    /**
     * Refresh the name look-up table.
     *
     * 刷新名称查找表
     *
     * This is done in case any names are fluently defined.
     *
     * 这样做的情况下，任何名字都流定义
     *
     * @return void
     */
    public function refreshNameLookups()
    {
        $this->nameList = [];

        foreach ($this->allRoutes as $route) {
            if ($route->getName()) { //获取路由实例的名称
                $this->nameList[$route->getName()] = $route;
            }
        }
    }

    /**
     * Find the first route matching a given request.
     *
     * 找到匹配给定请求的第一条路路由
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function match(Request $request)
    {
        $routes = $this->get($request->getMethod()); // 通过方法获取集合中的路由

        // First, we will see if we can find a matching route for this current request
        // method. If we can, great, we can just return it so that it can be called
        // by the consumer. Otherwise we will check for routes with another verb.
        //
        // 首先，我们将看看是否可以找到当前请求方法的匹配路径。如果可以，太棒了，我们可以返回它，以便它可以用户调用 。否则，我们将检查其他路由动词。
        //
        $route = $this->matchAgainstRoutes($routes, $request); // 确定数组中的路由是否与请求匹配，并返回路由

        if (! is_null($route)) {
            return $route->bind($request); //编译路由为Symfony CompiledRoute实例
        }

        // If no route was found we will now check if a matching route is specified by
        // another HTTP verb. If it is we will need to throw a MethodNotAllowed and
        // inform the user agent of which HTTP verb it should use for this route.
        //
        // 如果没有找到路由，我们将检查是否由另一个HTTP谓词指定匹配路径。如果我们需要把MethodNotAllowed告知用户代理的HTTP动词应该用这条路线。
        //
        $others = $this->checkForAlternateVerbs($request); //确定是否在另一个HTTP谓词上匹配任何路由

        if (count($others) > 0) {
            return $this->getRouteForMethods($request, $others); // 得到一个路由（如果需要的话），当其他可用的方法是响应
        }

        throw new NotFoundHttpException;
    }

    /**
     * Determine if a route in the array matches the request.
     *
     * 确定数组中的路由是否与请求匹配
     *
     * @param  array  $routes
     * @param  \Illuminate\http\Request  $request
     * @param  bool  $includingMethod
     * @return \Illuminate\Routing\Route|null
     */
    protected function matchAgainstRoutes(array $routes, $request, $includingMethod = true)
    {
        // 通过给定的真值测试返回数组中的第一个元素
        return Arr::first($routes, function ($value) use ($request, $includingMethod) {
            return $value->matches($request, $includingMethod); //确定路由是否匹配给定的请求 Illuminate\Routing\Route::matches()
        });
    }

    /**
     * Determine if any routes match on another HTTP verb.
     *
     * 确定是否在另一个HTTP谓词上匹配任何路由
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function checkForAlternateVerbs($request)
    {
        $methods = array_diff(Router::$verbs, [$request->getMethod()]);

        // Here we will spin through all verbs except for the current request verb and
        // check to see if any routes respond to them. If they do, we will return a
        // proper error response with the correct headers on the response string.
        //
        // 在这里，我们将通过所有动词，除了当前请求动词，并检查是否有任何路由响应他们
        // 如果他们这样做，我们将返回正确的错误响应与正确的头上的响应字符串
        //
        $others = [];

        foreach ($methods as $method) {
            //           确定数组中的路由是否与请求匹配     通过方法获取集合中的路由
            if (! is_null($this->matchAgainstRoutes($this->get($method), $request, false))) {
                $others[] = $method;
            }
        }

        return $others;
    }

    /**
     * Get a route (if necessary) that responds when other available methods are present.
     *
     * 得到一个路由（如果需要的话），当其他可用的方法是响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $methods
     * @return \Illuminate\Routing\Route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function getRouteForMethods($request, array $methods)
    {
        //     如果请求的方法是OPTIONS
        if ($request->method() == 'OPTIONS') {
            //路由实例(OPTIONS, 获取请求的当前路径信息, Colsure{响应实例})->将路由绑定到给定的执行请求
            return (new Route('OPTIONS', $request->path(), function () use ($methods) {
                return new Response('', 200, ['Allow' => implode(',', $methods)]);
            }))->bind($request);
        }

        $this->methodNotAllowed($methods); //抛出HTTP异常:not allowed
    }

    /**
     * Throw a method not allowed HTTP exception.
     *
     * 抛出HTTP异常:not allowed
     *
     * @param  array  $others
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function methodNotAllowed(array $others)
    {
        throw new MethodNotAllowedHttpException($others);
    }

    /**
     * Get routes from the collection by method.
     *
     * 通过方法获取集合中的路由
     *
     * @param  string|null  $method
     * @return array
     */
    public function get($method = null)
    {
        // 返回 如果$method是空，返回所有平级路由数组，否则，从方法命名的路由数组中返回指定的值
        return is_null($method) ? $this->getRoutes() : Arr::get($this->routes, $method, []);
    }

    /**
     * Determine if the route collection contains a given named route.
     *
     * 确定路由集合是否包含给定的命名路由
     *
     * @param  string  $name
     * @return bool
     */
    public function hasNamedRoute($name)
    {
        return ! is_null($this->getByName($name)); //按名称获取路由实例
    }

    /**
     * Get a route instance by its name.
     *
     * 按名称获取路由实例
     *
     * @param  string  $name
     * @return \Illuminate\Routing\Route|null
     */
    public function getByName($name)
    {
        return isset($this->nameList[$name]) ? $this->nameList[$name] : null;
    }

    /**
     * Get a route instance by its controller action.
     *
     * 通过控制器动作获取路由实例
     *
     * @param  string  $action
     * @return \Illuminate\Routing\Route|null
     */
    public function getByAction($action)
    {
        return isset($this->actionList[$action]) ? $this->actionList[$action] : null;
    }

    /**
     * Get all of the routes in the collection.
     *
     * 获取集合中的所有路由
     *
     * @return array
     */
    public function getRoutes()
    {
        return array_values($this->allRoutes); //返回数组的所有值（非键名）：
    }

    /**
     * Get all of the routes keyed by their HTTP verb / method.
     *
     * 获取所有路由的HTTP动词/方法
     *
     * @return array
     */
    public function getRoutesByMethod()
    {
        return $this->routes;
    }

    /**
     * Get an iterator for the items.
     *
     * 获取项目的迭代器
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getRoutes());
    }

    /**
     * Count the number of items in the collection.
     *
     * 计数集合中的项目数
     *
     * @return int
     */
    public function count()
    {
        return count($this->getRoutes());
    }
}
