<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Contracts\Foundation\Application;
//控制台的设置请求
class SetRequestForConsole
{
    /**
     * Bootstrap the given application.
     *
     * 引导给定的应用程序
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        //在容器中注册一个已存在的实例         根据给定的URI和配置创建请求
        $app->instance('request', Request::create(
            //从容器中解析给定类型->获取指定的配置值
            $app->make('config')->get('app.url', 'http://localhost'), 'GET', [], [], [], $_SERVER
        ));
    }
}
