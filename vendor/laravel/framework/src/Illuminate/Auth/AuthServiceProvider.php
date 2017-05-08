<?php

namespace Illuminate\Auth;

use Illuminate\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class AuthServiceProvider extends ServiceProvider
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
        $this->registerAuthenticator();//注册认证服务

        $this->registerUserResolver();//为经过身份验证的用户注册一个解析器

        $this->registerAccessGate();//注册访问门面服务

        $this->registerRequestRebindHandler();//为经过身份验证的用户注册一个解析器
    }

    /**
     * Register the authenticator services.
	 *
	 * 注册认证服务
     *
     * @return void
     */
    protected function registerAuthenticator()
    {
        //在容器中注册共享绑定
        $this->app->singleton('auth', function ($app) {
            // Once the authentication service has actually been requested by the developer
            // we will set a variable in the application indicating such. This helps us
            // know that we need to set any queued cookies in the after event later.
            //
            // 一旦身份验证服务实际被开发人员请求，我们将在应用程序中设置一个变量来指示这些
            // 这可以帮助我们知道，我们需要在以后的事件中设置任何排队的cookie
            //
            $app['auth.loaded'] = true;
            //创建一个新的Auth管理器实例
            return new AuthManager($app);
        });
        //在容器中注册共享绑定
        $this->app->singleton('auth.driver', function ($app) {
			//     Illuminate\Auth\AuthManager->试图从本地缓存中得到守卫
            return $app['auth']->guard();
        });
    }

    /**
     * Register a resolver for the authenticated user.
     *
     * 为经过身份验证的用户注册一个解析器
     *
     * @return void
     */
    protected function registerUserResolver()
    {
        //向容器注册一个绑定
        $this->app->bind(
            AuthenticatableContract::class, function ($app) {
                //                           获取用户解析器回调
                return call_user_func($app['auth']->userResolver());
            }
        );
    }

    /**
     * Register the access gate service.
     *
     * 注册访问门面服务
     *
     * @return void
     */
    protected function registerAccessGate()
    {
        //在容器中注册共享绑定
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, function () use ($app) {
                //                           获取用户解析器回调
                return call_user_func($app['auth']->userResolver());
            });
        });
    }

    /**
     * Register a resolver for the authenticated user.
     *
     * 为经过身份验证的用户注册一个解析器
     *
     * @return void
     */
    protected function registerRequestRebindHandler()
    {
        //绑定一个新的回调到抽象的绑定事件
        $this->app->rebinding('request', function ($app, $request) {
            //设置用户解析器回调
            $request->setUserResolver(function ($guard = null) use ($app) {
                //                           获取用户解析器回调
                return call_user_func($app['auth']->userResolver(), $guard);
            });
        });
    }
}
