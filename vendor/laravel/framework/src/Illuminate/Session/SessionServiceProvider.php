<?php

namespace Illuminate\Session;

use Illuminate\Support\ServiceProvider;
use Illuminate\Session\Middleware\StartSession;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //注册会话管理器实例
        $this->registerSessionManager();
        //注册会话驱动程序实例
        $this->registerSessionDriver();
        //在容器中注册共享绑定
        $this->app->singleton(StartSession::class);
    }

    /**
     * Register the session manager instance.
	 *
	 * 注册会话管理器实例
     *
     * @return void
     */
    protected function registerSessionManager()
    {
		//在容器中注册共享绑定
        $this->app->singleton('session', function ($app) {
            return new SessionManager($app);
        });
    }

    /**
     * Register the session driver instance.
     *
     * 注册会话驱动程序实例
     *
     * @return void
     */
    protected function registerSessionDriver()
    {
        //在容器中注册共享绑定
        $this->app->singleton('session.store', function ($app) {
            // First, we will create the session manager which is responsible for the
            // creation of the various session drivers when they are needed by the
            // application instance, and will resolve them on a lazy load basis.
            //
            // 首先，我们将创建一个会话管理器，它负责在应用程序实例需要时创建各种会话驱动程序，并在惰性负载基础上解决它们
            //
            //       从容器中解析给定类型
            return $app->make('session')->driver();
        });
    }
}
