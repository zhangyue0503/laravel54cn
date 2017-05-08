<?php

namespace Illuminate\Auth;

use Closure;
use InvalidArgumentException;
use Illuminate\Contracts\Auth\Factory as FactoryContract;

class AuthManager implements FactoryContract
{
    use CreatesUserProviders;

    /**
     * The application instance.
     *
     * 应用程序实例
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The registered custom driver creators.
     *
     * 注册自定义驱动程序的创建者
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * The array of created "drivers".
     *
     * 创建"drivers"的数组
     *
     * @var array
     */
    protected $guards = [];

    /**
     * The user resolver shared by various services.
     *
     * 由各种服务共享的用户解析器
     *
     * Determines the default user for Gate, Request, and the Authenticatable contract.
     *
     * 确定门户、请求和身份验证契约的默认用户
     *
     * @var \Closure
     */
    protected $userResolver;

    /**
     * Create a new Auth manager instance.
     *
     * 创建一个新的Auth管理器实例
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;

        $this->userResolver = function ($guard = null) {
            //      试图从本地缓存中得到守卫->获取当前经过身份验证的用户
            return $this->guard($guard)->user();
        };
    }

    /**
     * Attempt to get the guard from the local cache.
	 *
	 * 试图从本地缓存中得到守卫
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     */
    public function guard($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();//获取默认身份验证驱动程序名称

        return isset($this->guards[$name])
                    ? $this->guards[$name]
                    : $this->guards[$name] = $this->resolve($name);//解决给定的守卫
    }

    /**
     * Resolve the given guard.
	 *
	 * 解决给定的守卫
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        //              获得警卫配置
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            //             调用自定义驱动程序创建者
            return $this->callCustomCreator($name, $config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($name, $config);
        }

        throw new InvalidArgumentException("Auth guard driver [{$name}] is not defined.");
    }

    /**
     * Call a custom driver creator.
     *
     * 调用自定义驱动程序创建者
     *
     * @param  string  $name
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator($name, array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $name, $config);
    }

    /**
     * Create a session based authentication guard.
     *
     * 创建基于会话的身份验证保护
     *
     * @param  string  $name
     * @param  array  $config
     * @return \Illuminate\Auth\SessionGuard
     */
    public function createSessionDriver($name, $config)
    {
        //               为驱动程序创建用户提供程序实现
        $provider = $this->createUserProvider($config['provider']);
        //             创建一个新的身份验证保护
        $guard = new SessionGuard($name, $provider, $this->app['session.store']);

        // When using the remember me functionality of the authentication services we
        // will need to be set the encryption instance of the guard, which allows
        // secure, encrypted cookie values to get generated for those cookies.
        //
        // 当使用身份验证服务的记住我功能时，我们将需要设置保护的加密实例，它允许安全的、加密的cookie值为这些cookie生成
        //
        if (method_exists($guard, 'setCookieJar')) {
            //设置保护器使用的cookie创建器实例
            $guard->setCookieJar($this->app['cookie']);
        }

        if (method_exists($guard, 'setDispatcher')) {
            //设置事件调度程序实例
            $guard->setDispatcher($this->app['events']);
        }

        if (method_exists($guard, 'setRequest')) {
            //设置当前的请求实例(在给定的目标和方法上刷新实例)
            $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
        }

        return $guard;
    }

    /**
     * Create a token based authentication guard.
     *
     * 创建基于标记的身份验证保护
     *
     * @param  string  $name
     * @param  array  $config
     * @return \Illuminate\Auth\TokenGuard
     */
    public function createTokenDriver($name, $config)
    {
        // The token guard implements a basic API token based guard implementation
        // that takes an API token field from the request and matches it to the
        // user in the database or another persistence layer where users are.
        //
        // 令牌保护器实现了一个基本的API令牌基于保护的实现，它从请求中获取API令牌字段，并将其与数据库中的用户或用户所在的另一个持久层进行匹配
        //
        //           创建一个新的身份验证保护
        $guard = new TokenGuard(
            //为驱动程序创建用户提供程序实现
            $this->createUserProvider($config['provider']),
            $this->app['request']
        );
        //在给定的目标和方法上刷新实例
        $this->app->refresh('request', $guard, 'setRequest');

        return $guard;
    }

    /**
     * Get the guard configuration.
     *
     * 获得警卫配置
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["auth.guards.{$name}"];
    }

    /**
     * Get the default authentication driver name.
	 *
	 * 获取默认身份验证驱动程序名称
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['auth.defaults.guard'];
    }

    /**
     * Set the default guard driver the factory should serve.
     *
     * 设置工厂应该服务的默认保护驱动程序
     *
     * @param  string  $name
     * @return void
     */
    public function shouldUse($name)
    {
        //获取默认身份验证驱动程序名称
        $name = $name ?: $this->getDefaultDriver();

        $this->setDefaultDriver($name);//设置默认的身份验证驱动名称

        $this->userResolver = function ($name = null) {
            //试图从本地缓存中得到守卫->获取当前经过身份验证的用户
            return $this->guard($name)->user();
        };
    }

    /**
     * Set the default authentication driver name.
     *
     * 设置默认的身份验证驱动名称
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['auth.defaults.guard'] = $name;
    }

    /**
     * Register a new callback based request guard.
     *
     * 注册一个基于回调的请求保护
     *
     * @param  string  $driver
     * @param  callable  $callback
     * @return $this
     */
    public function viaRequest($driver, callable $callback)
    {
        //        注册一个自定义驱动程序创建者的闭包
        return $this->extend($driver, function () use ($callback) {
            $guard = new RequestGuard($callback, $this->app['request']);//创建一个新的身份验证保护
            //在给定的目标和方法上刷新实例
            $this->app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }

    /**
     * Get the user resolver callback.
     *
     * 获取用户解析器回调
     *
     * @return \Closure
     */
    public function userResolver()
    {
        return $this->userResolver;
    }

    /**
     * Set the callback to be used to resolve users.
     *
     * 设置用于解析用户的回调
     *
     * @param  \Closure  $userResolver
     * @return $this
     */
    public function resolveUsersUsing(Closure $userResolver)
    {
        $this->userResolver = $userResolver;

        return $this;
    }

    /**
     * Register a custom driver creator Closure.
     *
     * 注册一个自定义驱动程序创建者的闭包
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Register a custom provider creator Closure.
     *
     * 注册一个定制的提供者回调
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return $this
     */
    public function provider($name, Closure $callback)
    {
        $this->customProviderCreators[$name] = $callback;

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * 动态调用默认驱动程序实例
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        //试图从本地缓存中得到守卫
        return $this->guard()->{$method}(...$parameters);
    }
}
