<?php

namespace Illuminate\Routing;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Routing\BindingRegistrar;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Illuminate\Contracts\Routing\Registrar as RegistrarContract;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Router implements RegistrarContract, BindingRegistrar
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The event dispatcher instance.
     *
     * 事件调度实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The IoC container instance.
     *
     * IoC容器实例
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The route collection instance.
     *
     * 路由集合实例
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routes;

    /**
     * The currently dispatched route instance.
     *
     * 当前发送的路由实例
     *
     * @var \Illuminate\Routing\Route
     */
    protected $current;

    /**
     * The request currently being dispatched.
     *
     * 目前正在发送的请求
     *
     * @var \Illuminate\Http\Request
     */
    protected $currentRequest;

    /**
     * All of the short-hand keys for middlewares.
     *
     * 所有的短名称的中间件键值
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * All of the middleware groups.
     *
     * 所有的中间件组
     *
     * @var array
     */
    protected $middlewareGroups = [];

    /**
     * The priority-sorted list of middleware.
     *
     * 中间件的优先级排序列表
     *
     * Forces the listed middleware to always be in the given order.
     *
     * 强制列出的中间件总是在给定的顺序
     *
     * @var array
     */
    public $middlewarePriority = [];

    /**
     * The registered route value binders.
     *
     * 注册路由值绑定器
     *
     * @var array
     */
    protected $binders = [];

    /**
     * The globally available parameter patterns.
     *
     * 全局可用参数模式
     *
     * @var array
     */
    protected $patterns = [];

    /**
     * The route group attribute stack.
     *
     * 路由组属性堆栈
     *
     * @var array
     */
    protected $groupStack = [];

    /**
     * All of the verbs supported by the router.
     *
     * 路由器支持的所有动词
     *
     * @var array
     */
    public static $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    /**
     * Create a new Router instance.
     *
     * 创建一个新的路由实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function __construct(Dispatcher $events, Container $container = null)
    {
        $this->events = $events; //事件调度实例
        $this->routes = new RouteCollection; // 路由集合实例
        $this->container = $container ?: new Container; //IoC容器实例
    }

    /**
     * Register a new GET route with the router.
     *
     * 用路由器注册新的GET路由
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function get($uri, $action = null)
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Register a new POST route with the router.
     *
     * 用路由器注册新的POST路由
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function post($uri, $action = null)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a new PUT route with the router.
     *
     * 用路由器注册新的PUT路由
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function put($uri, $action = null)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register a new PATCH route with the router.
     *
     * 用路由器注册新的PATCH路由
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function patch($uri, $action = null)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Register a new DELETE route with the router.
     *
     * 用路由器注册新的DELETE路由
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function delete($uri, $action = null)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Register a new OPTIONS route with the router.
     *
     * 用路由器注册新的OPTIONS路由
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function options($uri, $action = null)
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    /**
     * Register a new route responding to all verbs.
     *
     * 注册一个响应所有动词的新路由
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function any($uri, $action = null)
    {
        $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'];

        return $this->addRoute($verbs, $uri, $action);
    }

    /**
     * Register a new route with the given verbs.
     *
     * 用给定的动词注册新路由
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function match($methods, $uri, $action = null)
    {
        return $this->addRoute(array_map('strtoupper', (array) $methods), $uri, $action);
    }

    /**
     * Register an array of resource controllers.
     *
     * 注册资源控制器数组
     *
     * @param  array  $resources
     * @return void
     */
    public function resources(array $resources)
    {
        foreach ($resources as $name => $controller) {
            $this->resource($name, $controller); //将资源路由到控制器
        }
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
        //                                          确定给定的抽象类型是否已绑定
        if ($this->container && $this->container->bound(ResourceRegistrar::class)) {
            $registrar = $this->container->make(ResourceRegistrar::class); // 从容器中解析给定类型
        } else {
            $registrar = new ResourceRegistrar($this); // 注册一个ResourceRegistrar类
        }

        $registrar->register($name, $controller, $options);  // 将资源路由到控制器
    }

    /**
     * Create a route group with shared attributes.
     *
     * 创建具有共享属性的路由组
     *
     * @param  array  $attributes
     * @param  \Closure|string  $routes
     * @return void
     */
    public function group(array $attributes, $routes)
    {
        $this->updateGroupStack($attributes); //使用给定属性更新组堆栈

        // Once we have updated the group stack, we'll load the provided routes and
        // merge in the group's attributes when the routes are created. After we
        // have created the routes, we will pop the attributes off the stack.
        //
        // 一旦更新了组堆栈，我们将在路由创建时加载所提供的路由并合并组属性
        // 在创建了路由之后，我们将从堆栈中弹出属性
        //
        $this->loadRoutes($routes); // 加载所提供的路由

        array_pop($this->groupStack);
    }

    /**
     * Update the group stack with the given attributes.
     *
     * 使用给定属性更新组堆栈
     *
     * @param  array  $attributes
     * @return void
     */
    protected function updateGroupStack(array $attributes)
    {
        if (! empty($this->groupStack)) {
            $attributes = RouteGroup::merge($attributes, end($this->groupStack)); //将路由组合并到新数组中
        }

        $this->groupStack[] = $attributes;
    }

    /**
     * Merge the given array with the last group stack.
     *
     * 将给定数组与最后一组堆栈合并
     *
     * @param  array  $new
     * @return array
     */
    public function mergeWithLastGroup($new)
    {
        return RouteGroup::merge($new, end($this->groupStack)); //将路由组合并到新数组中
    }

    /**
     * Load the provided routes.
     *
     * 加载所提供的路由
     *
     * @param  \Closure|string  $routes
     * @return void
     */
    protected function loadRoutes($routes)
    {
        if ($routes instanceof Closure) {
            $routes($this);
        } else {
            $router = $this;

            require $routes;
        }
    }

    /**
     * Get the prefix from the last group on the stack.
     *
     * 从堆栈上的最后一组获取前缀
     *
     * @return string
     */
    public function getLastGroupPrefix()
    {
        //           路由组属性堆栈
        if (! empty($this->groupStack)) {
            $last = end($this->groupStack);

            return isset($last['prefix']) ? $last['prefix'] : '';
        }

        return '';
    }

    /**
     * Add a route to the underlying route collection.
     *
     * 将路由添加到基础路由集合
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    protected function addRoute($methods, $uri, $action)
    {
        //       将路由实例添加到集合中           创建一个新的路由实例
        return $this->routes->add($this->createRoute($methods, $uri, $action));
    }

    /**
     * Create a new route instance.
     *
     * 创建一个新的路由实例
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  mixed  $action
     * @return \Illuminate\Routing\Route
     */
    protected function createRoute($methods, $uri, $action)
    {
        // If the route is routing to a controller we will parse the route action into
        // an acceptable array format before registering it and creating this route
        // instance itself. We need to build the Closure that will call this out.
        //
        // 如果路由路由到控制器，我们将分析路由行动到一个可接受的数组格式，然后再注册它，并创建这个路由实例本身
        // 我们需要建立闭包来调用这个
        //
        if ($this->actionReferencesController($action)) { //确定该动作是否路由到控制器
            $action = $this->convertToControllerAction($action); //$action=向动作数组添加基于控制器的路由操作
        }
        //创建一个新的路由对象
        $route = $this->newRoute(
            //          获取给定URI的最后前缀
            $methods, $this->prefix($uri), $action
        );

        // If we have groups that need to be merged, we will merge them now after this
        // route has already been created and is ready to go. After we're done with
        // the merge we will be ready to return the route back out to the caller.
        //
        // 如果我们有需要合并的组，那么在这个路由已经创建并准备就绪后，我们将合并它们。在完成合并之后，我们将准备将路由返回给调用方
        //
        if ($this->hasGroupStack()) {  //确定路由器当前是否有组堆栈
            $this->mergeGroupAttributesIntoRoute($route); // 将组堆栈与控制器动作合并
        }

        $this->addWhereClausesToRoute($route); // 在初始注册的基础上添加必要的WHERE子句

        return $route; //返回路由对象
    }

    /**
     * Determine if the action is routing to a controller.
     *
     * 确定该动作是否路由到控制器
     *
     * @param  array  $action
     * @return bool
     */
    protected function actionReferencesController($action)
    {
        if (! $action instanceof Closure) {
            return is_string($action) || (isset($action['uses']) && is_string($action['uses']));
        }

        return false;
    }

    /**
     * Add a controller based route action to the action array.
     *
     * 向动作数组添加基于控制器的路由操作
     *
     * @param  array|string  $action
     * @return array
     */
    protected function convertToControllerAction($action)
    {
        if (is_string($action)) {
            $action = ['uses' => $action];
        }

        // Here we'll merge any group "uses" statement if necessary so that the action
        // has the proper clause for this property. Then we can simply set the name
        // of the controller on the action and return the action array for usage.
        //
        // 在这里，我们将合并任何组“uses”语句，如果必要的话，该行动有适当的从句属性。
        // 然后，我们可以简单地在动作上设置控制器的名称，并返回使用的动作数组。
        //
        if (! empty($this->groupStack)) {
            $action['uses'] = $this->prependGroupNamespace($action['uses']); //在最后一组的命名空间上使用从句
        }

        // Here we will set this controller name on the action array just so we always
        // have a copy of it for reference if we need it. This can be used while we
        // search for a controller name or do some other type of fetch operation.
        //
        // 在这里我们将在动作数组中设置这个控制器名，所以我们总是有一个副本作为参考，如果我们需要它的话
        // 这可以在搜索控制器名或做其他类型的取操作时使用
        //
        $action['controller'] = $action['uses'];

        return $action;
    }

    /**
     * Prepend the last group namespace onto the use clause.
     *
     * 在最后一组命名空间上使用从句
     *
     * @param  string  $class
     * @return string
     */
    protected function prependGroupNamespace($class)
    {
        $group = end($this->groupStack);

        return isset($group['namespace']) && strpos($class, '\\') !== 0
                ? $group['namespace'].'\\'.$class : $class;
    }

    /**
     * Create a new Route object.
     *
     * 创建一个新的路由对象
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  mixed  $action
     * @return \Illuminate\Routing\Route
     */
    protected function newRoute($methods, $uri, $action)
    {
        return (new Route($methods, $uri, $action))
                    ->setRouter($this) //在路由上设置路由器实例
                    ->setContainer($this->container); //在路由上设置容器实例
    }

    /**
     * Prefix the given URI with the last prefix.
     *
     * 获取给定URI的最后前缀
     *
     * @param  string  $uri
     * @return string
     */
    protected function prefix($uri)
    {
        //                   从堆栈上的最后一组获取前缀
        return trim(trim($this->getLastGroupPrefix(), '/').'/'.trim($uri, '/'), '/') ?: '/';
    }

    /**
     * Add the necessary where clauses to the route based on its initial registration.
     *
     * 在初始注册的基础上添加必要的WHERE子句
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    protected function addWhereClausesToRoute($route)
    {
        //设置路由上要求的正则表达式
        $route->where(array_merge(
            //                            获取路由的动作数组              获取路由的动作数组
            $this->patterns, isset($route->getAction()['where']) ? $route->getAction()['where'] : []
        ));

        return $route;
    }

    /**
     * Merge the group stack with the controller action.
     *
     * 将组堆栈与控制器动作合并
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    protected function mergeGroupAttributesIntoRoute($route)
    {
        //设置路由的动作数组   将给定数组与最后一组堆栈合并      获取路由的动作数组
        $route->setAction($this->mergeWithLastGroup($route->getAction()));
    }

    /**
     * Dispatch the request to the application.
     *
     * 将请求发送到应用程序
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        return $this->dispatchToRoute($request); // 将请求发送到路由并返回响应
    }

    /**
     * Dispatch the request to a route and return the response.
     *
     * 将请求发送到路由并返回响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function dispatchToRoute(Request $request)
    {
        // First we will find a route that matches this request. We will also set the
        // route resolver on the request so middlewares assigned to the route will
        // receive access to this route instance for checking of the parameters.
        //
        // 首先，我们会找到一个符合这个要求的路由。我们也将要求设置路由解析，分配给路由中间件对参数检测接收访问此路径实例。
        //
        $route = $this->findRoute($request); // 查找给定请求相匹配的路由
        // 设置路由回调解析器
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });
        // 触发事件并调用监听器
        $this->events->dispatch(new Events\RouteMatched($route, $request));
        // 在堆栈“onion”实例中运行给定的路由
        $response = $this->runRouteWithinStack($route, $request);
        // 返回 从给定值创建响应实例
        return $this->prepareResponse($request, $response);
    }

    /**
     * Find the route matching a given request.
     *
     * 查找给定请求相匹配的路由
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route
     */
    protected function findRoute($request)
    {
        $this->current = $route = $this->routes->match($request); //找到匹配给定请求的第一条路路由

        $this->container->instance(Route::class, $route); // IoC容器实例设置为Route

        return $route;
    }

    /**
     * Run the given route within a Stack "onion" instance.
     *
     * 在堆栈“onion”实例中运行给定的路由
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function runRouteWithinStack(Route $route, Request $request)
    {
        $shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
                                $this->container->make('middleware.disable') === true;
        // 用给定的解析类名收集给定路由的中间件
        $middleware = $shouldSkipMiddleware ? [] : $this->gatherRouteMiddleware($route);

        return (new Pipeline($this->container)) //管道命令
                        ->send($request) //设置通过管道发送的对象$request
                        ->through($middleware) //设置管道数组$中间件
                        ->then(function ($request) use ($route) { //使用最终目标回调来运行管道
                            return $this->prepareResponse( //从给定值创建响应实例
                                $request, $route->run() //运行路由操作并返回响应
                            );
                        });
    }

    /**
     * Gather the middleware for the given route with resolved class names.
     *
     * 用给定的解析类名收集给定路由的中间件
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    public function gatherRouteMiddleware(Route $route)
    {
        //           创建集合对象      (获取所有的中间件，包括从控制器中定义的)->在每个项目上运行map(匿名方法{retrun 将中间件名称解析为保存传递参数的类名})->获取集合中的项目的扁平数组
        $middleware = collect($route->gatherMiddleware())->map(function ($name) {
            return (array) MiddlewareNameResolver::resolve($name, $this->middleware, $this->middlewareGroups);
        })->flatten();

        return $this->sortMiddleware($middleware); // 返回 按优先级排序给定的中间件
    }

    /**
     * Sort the given middleware by priority.
     *
     * 按优先级排序给定的中间件
     *
     * @param  \Illuminate\Support\Collection  $middlewares
     * @return array
     */
    protected function sortMiddleware(Collection $middlewares)
    {
        //     创建一个新的排序中间件容器->all()
        return (new SortedMiddleware($this->middlewarePriority, $middlewares))->all();
    }

    /**
     * Create a response instance from the given value.
     *
     * 从给定值创建响应实例
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  mixed  $response
     * @return \Illuminate\Http\Response
     */
    public function prepareResponse($request, $response)
    {
        if ($response instanceof PsrResponseInterface) { // $response 是否属于PsrResponseInterface
            $response = (new HttpFoundationFactory)->createResponse($response); //创建HttpFoundationFactory响应
        } elseif (! $response instanceof SymfonyResponse) { // $response 是否属于 SymfonyResopnse
            $response = new Response($response); //创建Symfony响应
        }

        return $response->prepare($request); // 返回 Symfony\Component\HttpFoundation\Response::perpare() 在发送给客户端之前准备响应
    }

    /**
     * Substitute the route bindings onto the route.
     *
     * 将路由绑定替换到路由
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return \Illuminate\Routing\Route
     */
    public function substituteBindings($route)
    {
        foreach ($route->parameters() as $key => $value) {     //获取路由参数的键/值列表
            if (isset($this->binders[$key])) {
                //     为给定值设置参数         调用给定键的绑定回调
                $route->setParameter($key, $this->performBinding($key, $value, $route));
            }
        }

        return $route;
    }

    /**
     * Substitute the implicit Eloquent model bindings for the route.
     *
     * 替换隐含的Eloquent模式绑定的路线
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    public function substituteImplicitBindings($route)
    {
        //解决给定路由的隐式路由绑定
        ImplicitRouteBinding::resolveForRoute($this->container, $route);
    }

    /**
     * Call the binding callback for the given key.
     *
     * 调用给定键的绑定回调
     *
     * @param  string  $key
     * @param  string  $value
     * @param  \Illuminate\Routing\Route  $route
     * @return mixed
     */
    protected function performBinding($key, $value, $route)
    {
        return call_user_func($this->binders[$key], $value, $route);
    }

    /**
     * Register a route matched event listener.
     *
     * 注册路由匹配事件侦听器
     *
     * @param  string|callable  $callback
     * @return void
     */
    public function matched($callback)
    {
        //     用分配器注册事件监听器
        $this->events->listen(Events\RouteMatched::class, $callback);
    }

    /**
     * Get all of the defined middleware short-hand names.
     *
     * 获取所有定义的中间件短名称
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Register a short-hand name for a middleware.
     *
     * 为中间件注册短名称
     *
     * @param  string  $name
     * @param  string  $class
     * @return $this
     */
    public function aliasMiddleware($name, $class)
    {
        $this->middleware[$name] = $class;

        return $this;
    }

    /**
     * Check if a middlewareGroup with the given name exists.
     *
     * 检查是否一个给定名字的中间件组是否存在
     *
     * @param  string  $name
     * @return bool
     */
    public function hasMiddlewareGroup($name)
    {
        return array_key_exists($name, $this->middlewareGroups);
    }

    /**
     * Get all of the defined middleware groups.
     *
     * 获取所有定义的中间件组
     *
     * @return array
     */
    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }

    /**
     * Register a group of middleware.
     *
     * 注册一组中间件
     *
     * @param  string  $name
     * @param  array  $middleware
     * @return $this
     */
    public function middlewareGroup($name, array $middleware)
    {
        $this->middlewareGroups[$name] = $middleware;

        return $this;
    }

    /**
     * Add a middleware to the beginning of a middleware group.
     *
     * 在中间件组的开始添加中间件
     *
     * If the middleware is already in the group, it will not be added again.
     *
     * 如果中间件已经在组中，则不会再次添加
     *
     * @param  string  $group
     * @param  string  $middleware
     * @return $this
     */
    public function prependMiddlewareToGroup($group, $middleware)
    {
        if (isset($this->middlewareGroups[$group]) && ! in_array($middleware, $this->middlewareGroups[$group])) {
            array_unshift($this->middlewareGroups[$group], $middleware);
        }

        return $this;
    }

    /**
     * Add a middleware to the end of a middleware group.
     *
     * 在中间件组的末端添加中间件
     *
     * If the middleware is already in the group, it will not be added again.
     *
     * 如果中间件已经在组中，则不会再次添加
     *
     * @param  string  $group
     * @param  string  $middleware
     * @return $this
     */
    public function pushMiddlewareToGroup($group, $middleware)
    {
        if (! array_key_exists($group, $this->middlewareGroups)) {
            $this->middlewareGroups[$group] = [];
        }

        if (! in_array($middleware, $this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group][] = $middleware;
        }

        return $this;
    }

    /**
     * Add a new route parameter binder.
     *
     * 添加新路由参数绑定器
     *
     * @param  string  $key
     * @param  string|callable  $binder
     * @return void
     */
    public function bind($key, $binder)
    {
        //                                                为给定回调创建路由模型绑定
        $this->binders[str_replace('-', '_', $key)] = RouteBinding::forCallback(
            $this->container, $binder
        );
    }

    /**
     * Register a model binder for a wildcard.
     *
     * 为通配符注册模型绑定器
     *
     * @param  string  $key
     * @param  string  $class
     * @param  \Closure|null  $callback
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function model($key, $class, Closure $callback = null)
    {
        //添加新路由参数绑定器               为模型创建路由模型绑定
        $this->bind($key, RouteBinding::forModel($this->container, $class, $callback));
    }

    /**
     * Get the binding callback for a given binding.
     *
     * 获取给定绑定的绑定回调
     *
     * @param  string  $key
     * @return \Closure|null
     */
    public function getBindingCallback($key)
    {
        if (isset($this->binders[$key = str_replace('-', '_', $key)])) {
            return $this->binders[$key];
        }
    }

    /**
     * Get the global "where" patterns.
     *
     * 获取全局的“where”模式
     *
     * @return array
     */
    public function getPatterns()
    {
        return $this->patterns;
    }

    /**
     * Set a global where pattern on all routes.
     *
     * 为所有路由设置全局的where模式
     *
     * @param  string  $key
     * @param  string  $pattern
     * @return void
     */
    public function pattern($key, $pattern)
    {
        $this->patterns[$key] = $pattern;
    }

    /**
     * Set a group of global where patterns on all routes.
     *
     * 为所有路由设置全局的组where模式
     *
     * @param  array  $patterns
     * @return void
     */
    public function patterns($patterns)
    {
        foreach ($patterns as $key => $pattern) {
            $this->pattern($key, $pattern); // 为所有路由设置全局的where模式
        }
    }

    /**
     * Determine if the router currently has a group stack.
     *
     * 确定路由器当前是否有组堆栈
     *
     * @return bool
     */
    public function hasGroupStack()
    {
        return ! empty($this->groupStack);
    }

    /**
     * Get the current group stack for the router.
     *
     * 获取路由器的当前组堆栈
     *
     * @return array
     */
    public function getGroupStack()
    {
        return $this->groupStack;
    }

    /**
     * Get a route parameter for the current route.
     *
     * 获取当前路由的路由参数
     *
     * @param  string  $key
     * @param  string  $default
     * @return mixed
     */
    public function input($key, $default = null)
    {
        //       获取当前发送的路由实例  从路由中得到给定的参数
        return $this->current()->parameter($key, $default);
    }

    /**
     * Get the request currently being dispatched.
     *
     * 获取当前正在发送的请求
     *
     * @return \Illuminate\Http\Request
     */
    public function getCurrentRequest()
    {
        return $this->currentRequest;
    }

    /**
     * Get the currently dispatched route instance.
     *
     * 获取当前发送的路由实例
     *
     * @return \Illuminate\Routing\Route
     */
    public function getCurrentRoute()
    {
        return $this->current(); //获取当前发送的路由实例
    }

    /**
     * Get the currently dispatched route instance.
     *
     * 获取当前发送的路由实例
     *
     * @return \Illuminate\Routing\Route
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * Check if a route with the given name exists.
     *
     * 检查是否存在给定名称的路由
     *
     * @param  string  $name
     * @return bool
     */
    public function has($name)
    {
        return $this->routes->hasNamedRoute($name); //确定路由集合是否包含给定的命名路由
    }

    /**
     * Get the current route name.
     *
     * 获取当前路由名
     *
     * @return string|null
     */
    public function currentRouteName()
    {
        //       获取当前发送的路由实例?获取当前发送的路由实例->获取路由实例的名称:null
        return $this->current() ? $this->current()->getName() : null;
    }

    /**
     * Alias for the "currentRouteNamed" method.
     *
     * 别名为“currentRouteNamed”的方法
     *
     * @return bool
     */
    public function is()
    {
        foreach (func_get_args() as $pattern) {
            //                      获取当前路由名
            if (Str::is($pattern, $this->currentRouteName())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current route matches a given name.
     *
     * 确定当前路由是否与给定名称匹配
     *
     * @param  string  $name
     * @return bool
     */
    public function currentRouteNamed($name)
    {
        //       获取当前发送的路由实例?获取当前发送的路由实例->获取路由实例的名称==$name:false
        return $this->current() ? $this->current()->getName() == $name : false;
    }

    /**
     * Get the current route action.
     *
     * 获取当前的路由动作
     *
     * @return string|null
     */
    public function currentRouteAction()
    {
        //     获取当前发送的路由实例
        if (! $this->current()) {
            return;
        }
        //            获取当前发送的路由实例->获取路由的动作数组
        $action = $this->current()->getAction();

        return isset($action['controller']) ? $action['controller'] : null;
    }

    /**
     * Alias for the "currentRouteUses" method.
     *
     * 别名为“currentRouteUses”的方法
     *
     * @return bool
     */
    public function uses()
    {
        foreach (func_get_args() as $pattern) {
            //                      获取当前路由动作
            if (Str::is($pattern, $this->currentRouteAction())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current route action matches a given action.
     *
     * 确定当前路由动作是否与给定动作匹配
     *
     * @param  string  $action
     * @return bool
     */
    public function currentRouteUses($action)
    {
        //          获取当前的路由动作
        return $this->currentRouteAction() == $action;
    }

    /**
     * Register the typical authentication routes for an application.
     *
     * 为应用程序注册典型的身份验证路径
     *
     * @return void
     */
    public function auth()
    {
        // Authentication Routes...  认证路由...
        $this->get('login', 'Auth\LoginController@showLoginForm')->name('login');
        $this->post('login', 'Auth\LoginController@login');
        $this->post('logout', 'Auth\LoginController@logout')->name('logout');

        // Registration Routes... 注册路由...
        $this->get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
        $this->post('register', 'Auth\RegisterController@register');

        // Password Reset Routes... 密码重置路由...
        $this->get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
        $this->post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
        $this->get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
        $this->post('password/reset', 'Auth\ResetPasswordController@reset');
    }

    /**
     * Set the unmapped global resource parameters to singular.
     *
     * 设置映射单数的的全局资源参数
     *
     * @param  bool  $singular
     * @return void
     */
    public function singularResourceParameters($singular = true)
    {
        ResourceRegistrar::singularParameters($singular); // 设置或取消映射单数的全局参数
    }

    /**
     * Set the global resource parameter mapping.
     *
     * 设置全局资源参数映射
     *
     * @param  array  $parameters
     * @return void
     */
    public function resourceParameters(array $parameters = [])
    {
        ResourceRegistrar::setParameters($parameters); //设置全局参数映射
    }

    /**
     * Get or set the verbs used in the resource URIs.
     *
     * 获取或设置用于资源的URI的动词
     *
     * @param  array  $verbs
     * @return array|null
     */
    public function resourceVerbs(array $verbs = [])
    {
        return ResourceRegistrar::verbs($verbs); //获取或设置用于资源的URI动作动词
    }

    /**
     * Get the underlying route collection.
     *
     * 获取基础路由集合
     *
     * @return \Illuminate\Routing\RouteCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Set the route collection instance.
     *
     * 设置路由集合实例
     *
     * @param  \Illuminate\Routing\RouteCollection  $routes
     * @return void
     */
    public function setRoutes(RouteCollection $routes)
    {
        foreach ($routes as $route) {
            //   在路由上设置路由器实例    在路由上设置容器实例
            $route->setRouter($this)->setContainer($this->container);
        }

        $this->routes = $routes;
        // 在容器中注册一个已存在的实例
        $this->container->instance('routes', $this->routes);
    }

    /**
     * Dynamically handle calls into the router instance.
     *
     * 动态处理调用到路由器实例
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {  // 检查宏是否已注册
            return $this->macroCall($method, $parameters);  //动态调用类的调用
        }

        return (new RouteRegistrar($this))->attribute($method, $parameters[0]); //为给定属性设置值
    }
}
