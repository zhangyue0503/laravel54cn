<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Facade;
use Illuminate\Contracts\Foundation\Application;

class RegisterFacades
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
        Facade::clearResolvedInstances();// 清除所有已解决的实例

        Facade::setFacadeApplication($app); // 设置应用实例
		// 获取或创建别名加载程序实例 (从容器中解析给定类型(config)->获取配置))->在自动装载机堆栈上注册加载器
        AliasLoader::getInstance($app->make('config')->get('app.aliases', []))->register();
    }
}
