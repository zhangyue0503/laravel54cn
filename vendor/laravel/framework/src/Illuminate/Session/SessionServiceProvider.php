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
        $this->registerSessionManager();

        $this->registerSessionDriver();

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
     * @return void
     */
    protected function registerSessionDriver()
    {
        $this->app->singleton('session.store', function ($app) {
            // First, we will create the session manager which is responsible for the
            // creation of the various session drivers when they are needed by the
            // application instance, and will resolve them on a lazy load basis.
            return $app->make('session')->driver();
        });
    }
}
