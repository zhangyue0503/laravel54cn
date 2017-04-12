<?php

namespace Illuminate\Foundation\Http;

use Exception;
use Throwable;
use Illuminate\Routing\Router;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Facade;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Kernel implements KernelContract
{
    /**
     * The application implementation.
     *
     * 应用实现
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The router instance.
     *
     * 路由器实例
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The bootstrap classes for the application.
     *
     * 应用程序的引导类
     *
     * @var array
     */
    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
    ];

    /**
     * The application's middleware stack.
     *
     * 应用程序中间件堆栈
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * The application's route middleware groups.
     *
     * 应用程序的路由中间件组
     *
     * @var array
     */
    protected $middlewareGroups = [];

    /**
     * The application's route middleware.
     *
     * 应用程序的路由中间件
     *
     * @var array
     */
    protected $routeMiddleware = [];

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
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class, //开启session
        \Illuminate\View\Middleware\ShareErrorsFromSession::class, //共享session错误
        \Illuminate\Auth\Middleware\Authenticate::class, //认证
        \Illuminate\Session\Middleware\AuthenticateSession::class, //session认证
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];

    /**
     * Create a new HTTP kernel instance.
     *
     * 创建一个新的HTTP内核实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;

        $router->middlewarePriority = $this->middlewarePriority;

        foreach ($this->middlewareGroups as $key => $middleware) {
            $router->middlewareGroup($key, $middleware);     // 注册一组中间件
        }

        foreach ($this->routeMiddleware as $key => $middleware) {
            $router->aliasMiddleware($key, $middleware); //为中间件注册短名称
        }
    }

    /**
     * Handle an incoming HTTP request.
     *
     * 处理传入的HTTP请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handle($request)
    {
        try {
            $request->enableHttpMethodParameterOverride(); //可实现对_method请求参数来确定预期的HTTP方法的支持

            $response = $this->sendRequestThroughRouter($request); // 通过中间件/路由器发送给定的请求
        } catch (Exception $e) {
            $this->reportException($e); //向异常处理程序报告异常

            $response = $this->renderException($request, $e); //将异常渲染到响应
        } catch (Throwable $e) {
            $this->reportException($e = new FatalThrowableError($e)); //向异常处理程序报告异常

            $response = $this->renderException($request, $e); //将异常渲染到响应
        }
        // 调度事件并调用监听器，监听Requesthandled，Illuminate\Events\Dispatcher::dispatch
        event(new Events\RequestHandled($request, $response));

        // 返回响应
        return $response;
    }

    /**
     * Send the given request through the middleware / router.
     *
     * 通过中间件/路由器发送给定的请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function sendRequestThroughRouter($request)
    {
        $this->app->instance('request', $request); // 在容器中注册request实例

        Facade::clearResolvedInstance('request'); // 清除已经解析的facade实例

        $this->bootstrap(); // 引导HTTP请求的应用程序

        return (new Pipeline($this->app)) //创建管道对象
                    ->send($request) // 设置通过管道发送的对象
                    ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware) // 设置管道数组,确定应用程序的中间件是否被禁用，禁用了传[]，未禁用传中间件
                    ->then($this->dispatchToRouter()); // 使用最终目标回调来运行管道，dispatchToRouter()获取路由调度器回调
    }

    /**
     * Bootstrap the application for HTTP requests.
     *
     * 引导HTTP请求的应用程序
     *
     * @return void
     */
    public function bootstrap()
    {
        if (! $this->app->hasBeenBootstrapped()) { // 确定应用程序是否已经引导 Illuminate\Foundation\Application::hasBeenBootstrapped()
            $this->app->bootstrapWith($this->bootstrappers()); //运行给定的引导类数组 Illuminate\Foundation\Application::bootstrapWith()
        }
    }

    /**
     * Get the route dispatcher callback.
     *
     * 获取路由调度器回调
     *
     * @return \Closure
     */
    protected function dispatchToRouter()
    {
        return function ($request) {
            $this->app->instance('request', $request); // 在容器中注册request实例

            return $this->router->dispatch($request); // 将请求发送到应用程序 Illuminate\Routing\Router::dispatch()
        };
    }

    /**
     * Call the terminate method on any terminable middleware.
     *
     * 调用terminate方法对任何有期限的中间件
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function terminate($request, $response)
    {
        $this->terminateMiddleware($request, $response); // 调用Terminate方法对任何有期限的中间件

        $this->app->terminate(); //终止应用程序 Illuminate\Foundation\Application::terminate()
    }

    /**
     * Call the terminate method on any terminable middleware.
     *
     * 调用Terminate方法对任何有期限的中间件
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    protected function terminateMiddleware($request, $response)
    {
        //                    确定应用程序的中间件是否被禁用
        $middlewares = $this->app->shouldSkipMiddleware() ? [] : array_merge(
            $this->gatherRouteMiddleware($request), //为给定的请求收集路由中间件
            $this->middleware
        );
        //循环中间件数组
        foreach ($middlewares as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            list($name, $parameters) = $this->parseMiddleware($middleware); // 解析中间件字符串以获取名称和参数

            $instance = $this->app->make($name);  // 从容器中解析给定类型，获得实例

            if (method_exists($instance, 'terminate')) { //如果实例包含terminate方法
                $instance->terminate($request, $response); //调用terminate方法
            }
        }
    }

    /**
     * Gather the route middleware for the given request.
     *
     * 为给定的请求收集路由中间件
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function gatherRouteMiddleware($request)
    {
        if ($route = $request->route()) {    //获取路由处理请求
            return $this->router->gatherRouteMiddleware($route); //用给定的解析类名收集给定路由的中间件
        }

        return [];
    }

    /**
     * Parse a middleware string to get the name and parameters.
     *
     * 解析中间件字符串以获取名称和参数
     *
     * @param  string  $middleware
     * @return array
     */
    protected function parseMiddleware($middleware)
    {
        list($name, $parameters) = array_pad(explode(':', $middleware, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    /**
     * Determine if the kernel has a given middleware.
     *
     * 确定内核是否有给定的中间件
     *
     * @param  string  $middleware
     * @return bool
     */
    public function hasMiddleware($middleware)
    {
        return in_array($middleware, $this->middleware);
    }

    /**
     * Add a new middleware to beginning of the stack if it does not already exist.
     *
     * 如果堆栈不存在，就向堆栈的开始添加一个新的中间件
     *
     * @param  string  $middleware
     * @return $this
     */
    public function prependMiddleware($middleware)
    {
        if (array_search($middleware, $this->middleware) === false) {
            array_unshift($this->middleware, $middleware);
        }

        return $this;
    }

    /**
     * Add a new middleware to end of the stack if it does not already exist.
     *
     * 如果堆栈不存在，则向堆栈末尾添加新的中间件
     *
     * @param  string  $middleware
     * @return $this
     */
    public function pushMiddleware($middleware)
    {
        if (array_search($middleware, $this->middleware) === false) {
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    /**
     * Get the bootstrap classes for the application.
     *
     * 获取应用程序的引导类
     *
     * @return array
     */
    protected function bootstrappers()
    {
        return $this->bootstrappers;
    }

    /**
     * Report the exception to the exception handler.
     *
     * 向异常处理程序报告异常
     *
     * @param  \Exception  $e
     * @return void
     */
    protected function reportException(Exception $e)
    {
        $this->app[ExceptionHandler::class]->report($e);
    }

    /**
     * Render the exception to a response.
     *
     * 将异常渲染到响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderException($request, Exception $e)
    {
        return $this->app[ExceptionHandler::class]->render($request, $e);
    }

    /**
     * Get the Laravel application instance.
     *
     * 获取Laravel应用程序实例
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getApplication()
    {
        return $this->app;
    }
}
