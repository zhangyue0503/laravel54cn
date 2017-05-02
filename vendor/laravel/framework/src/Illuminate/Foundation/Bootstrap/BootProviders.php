<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
//启动提供者
class BootProviders
{
    /**
     * Bootstrap the given application.
	 *
	 * 引导给定应用程序
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $app->boot(); // 启动应用程序的服务提供者
    }
}
