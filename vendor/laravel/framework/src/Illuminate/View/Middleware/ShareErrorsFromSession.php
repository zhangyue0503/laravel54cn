<?php

namespace Illuminate\View\Middleware;

use Closure;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Contracts\View\Factory as ViewFactory;

class ShareErrorsFromSession
{
    /**
     * The view factory implementation.
     *
     * 视图工厂实现
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * Create a new error binder instance.
     *
     * 创建一个新的错误绑定实例
     *
     * @param  \Illuminate\Contracts\View\Factory  $view
     * @return void
     */
    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
    }

    /**
     * Handle an incoming request.
     *
     * 处理传入的请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // If the current session has an "errors" variable bound to it, we will share
        // its value with all view instances so the views can easily access errors
        // without having to bind. An empty bag is set when there aren't errors.
        //
        // 如果当前会话有一个与之绑定的“错误”变量，那么我们将与所有视图实例共享它的值，这样视图就可以很容易地访问错误而不必绑定
        // 当没有错误时，将设置一个空袋子
        //
        //    向环境中添加一段共享数据
        $this->view->share(
            //           获取与请求关联的会话  从会话中获取项目
            'errors', $request->session()->get('errors') ?: new ViewErrorBag
        );

        // Putting the errors in the view for every view allows the developer to just
        // assume that some errors are always available, which is convenient since
        // they don't have to continually run checks for the presence of errors.
        //
        // 将错误放在每个视图的视图中，使得开发人员可以假设总是有一些错误，这很方便，因为他们不必为出现错误而不断地运行检查
        //

        return $next($request);
    }
}
