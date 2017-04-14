<?php

namespace Illuminate\Routing;

use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response as PsrResponse;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;

class RoutingServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerRouter(); // 注册路由器实例

        $this->registerUrlGenerator(); // 注册网址生成器服务

        $this->registerRedirector(); // 注册重定向服务

        $this->registerPsrRequest(); // 登记一个符合的psr-7请求实现

        $this->registerPsrResponse(); // 登记一个符合的psr-7响应实现

        $this->registerResponseFactory(); // 注册响应工厂实现
    }

    /**
     * Register the router instance.
     *
     * 注册路由器实例
     *
     * @return void
     */
    protected function registerRouter()
    {
        //在容器中注册共享绑定
        $this->app->singleton('router', function ($app) {
            //         创建一个新的路由实例
            return new Router($app['events'], $app);
        });
    }

    /**
     * Register the URL generator service.
     *
     * 注册网址生成器服务
     *
     * @return void
     */
    protected function registerUrlGenerator()
    {
        //在容器中注册共享绑定
        $this->app->singleton('url', function ($app) {
            $routes = $app['router']->getRoutes(); //获取基础路由集合

            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
            //
            // URL生成器需要路由器上存在的路由集合
            // 请记住，这是一个对象，所以我们在这里通过引用和所有已注册的路由将提供给生成器
            //
            $app->instance('routes', $routes); // 在容器中注册一个已存在的实例

            $url = new UrlGenerator(
                $routes, $app->rebinding( //绑定一个新的回调到抽象的绑定事件
                    'request', $this->requestRebinder() //获取重新绑定的URL请求生成器
                )
            );

            $url->setSessionResolver(function () { //为生成器设置会话解析器
                return $this->app['session'];
            });

            // If the route collection is "rebound", for example, when the routes stay
            // cached for the application, we will need to rebind the routes on the
            // URL generator instance so it has the latest version of the routes.
            //
            // 如果路由集合是“rebound”，比如，当路由保持缓存的应用，我们需要重新绑定的路线上的URL生成器实例具有路线最新版本。
            //
            $app->rebinding('routes', function ($app, $routes) { //绑定一个新的回调到抽象的绑定事件
                $app['url']->setRoutes($routes);
            });

            return $url;
        });
    }

    /**
     * Get the URL generator request rebinder.
     *
     * 获取重新绑定的URL请求生成器
     *
     * @return \Closure
     */
    protected function requestRebinder()
    {
        return function ($app, $request) {
            $app['url']->setRequest($request);
        };
    }

    /**
     * Register the Redirector service.
     *
     * 注册重定向服务
     *
     * @return void
     */
    protected function registerRedirector()
    {
        //在容器中注册共享绑定
        $this->app->singleton('redirect', function ($app) {
            $redirector = new Redirector($app['url']);

            // If the session is set on the application instance, we'll inject it into
            // the redirector instance. This allows the redirect responses to allow
            // for the quite convenient "with" methods that flash to the session.
            //
            // 如果会话设置在应用实例中，我们将它注入到重定向程序实例
            // 这允许重定向响应，以允许非常方便的“with”的方法闪存到会话。
            //
            if (isset($app['session.store'])) {
                $redirector->setSession($app['session.store']); //设置活动会话存储
            }

            return $redirector;
        });
    }

    /**
     * Register a binding for the PSR-7 request implementation.
     *
     * 登记一个符合的psr-7请求实现
     *
     * @return void
     */
    protected function registerPsrRequest()
    {
        //与容器注册绑定
        $this->app->bind(ServerRequestInterface::class, function ($app) {
            return (new DiactorosFactory)->createRequest($app->make('request')); //建立Psr\HttpMessage实例使用Zend Diactoros实现->从symfony创建一个psr-7请求实例()
        });
    }

    /**
     * Register a binding for the PSR-7 response implementation.
     *
     * 登记一个符合的psr-7响应实现
     *
     * @return void
     */
    protected function registerPsrResponse()
    {
        //与容器注册绑定
        $this->app->bind(ResponseInterface::class, function ($app) {
            return new PsrResponse();
        });
    }

    /**
     * Register the response factory implementation.
     *
     * 注册响应工厂实现
     *
     * @return void
     */
    protected function registerResponseFactory()
    {
        //在容器中注册共享绑定
        $this->app->singleton(ResponseFactoryContract::class, function ($app) {
            //        创建一个新的响应工厂实例()
            return new ResponseFactory($app[ViewFactoryContract::class], $app['redirect']);
        });
    }
}
