<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Routing\Redirector;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;

class FormRequestServiceProvider extends ServiceProvider
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
        //
    }

    /**
     * Bootstrap the application services.
     *
     * 引导应用程序服务
     *
     * @return void
     */
    public function boot()
    {
        //在解决回调后注册一个新的
        $this->app->afterResolving(ValidatesWhenResolved::class, function ($resolved) {
            $resolved->validate();//验证给定的类实例
        });
        //注册一个新的解析回调
        $this->app->resolving(FormRequest::class, function ($request, $app) {
            $this->initializeRequest($request, $app['request']);//使用来自给定请求的数据初始化表单请求
            //设置容器实现->设置Redirector实例(从容器中解析给定类型(转向器))
            $request->setContainer($app)->setRedirector($app->make(Redirector::class));
        });
    }

    /**
     * Initialize the form request with data from the given request.
     *
     * 使用来自给定请求的数据初始化表单请求
     *
     * @param  \Illuminate\Foundation\Http\FormRequest  $form
     * @param  \Symfony\Component\HttpFoundation\Request  $current
     * @return void
     */
    protected function initializeRequest(FormRequest $form, Request $current)
    {
        //                         返回所有参数数组
        $files = $current->files->all();

        $files = is_array($files) ? array_filter($files) : $files;
        //设置此请求的参数
        $form->initialize(
            $current->query->all(), $current->request->all(), $current->attributes->all(),
            $current->cookies->all(), $files, $current->server->all(), $current->getContent()
        );
        //设置请求的JSON有效载荷
        $form->setJson($current->json());
        //获取session
        if ($session = $current->getSession()) {
            $form->setLaravelSession($session);//在请求上设置session实例
        }
        //设置用户解析器回调
        $form->setUserResolver($current->getUserResolver());
        //设置路由回调解析器
        $form->setRouteResolver($current->getRouteResolver());
    }
}
