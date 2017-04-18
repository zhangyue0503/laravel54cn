<?php

namespace Illuminate\Routing;

use Illuminate\Http\RedirectResponse;
use Illuminate\Session\Store as SessionStore;
//转向器
class Redirector
{
    /**
     * The URL generator instance.
     *
     * 网址生成器实例
     *
     * @var \Illuminate\Routing\UrlGenerator
     */
    protected $generator;

    /**
     * The session store instance.
     *
     * 会话存储实例
     *
     * @var \Illuminate\Session\Store
     */
    protected $session;

    /**
     * Create a new Redirector instance.
	 *
	 * 创建一个重定向器实例
     *
     * @param  \Illuminate\Routing\UrlGenerator  $generator
     * @return void
     */
    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Create a new redirect response to the "home" route.
     *
     * 创建一个新的重定向响应到“home”的路由
     *
     * @param  int  $status
     * @return \Illuminate\Http\RedirectResponse
     */
    public function home($status = 302)
    {
        //        为给定路径创建新的重定向响应    获取指定路由的URL
        return $this->to($this->generator->route('home'), $status);
    }

    /**
     * Create a new redirect response to the previous location.
     *
     * 创建一个新的重定向响应到以前的位置
     *
     * @param  int    $status
     * @param  array  $headers
     * @param  mixed  $fallback
     * @return \Illuminate\Http\RedirectResponse
     */
    public function back($status = 302, $headers = [], $fallback = false)
    {
        //        创建一个新的重定向响应            获取之前请求的URL
        return $this->createRedirect($this->generator->previous($fallback), $status, $headers);
    }

    /**
     * Create a new redirect response to the current URI.
     *
     * 创建一个新的重定向响应当前URI
     *
     * @param  int    $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refresh($status = 302, $headers = [])
    {
        //      为给定路径创建新的重定向响应    获取请求实例   获取请求的当前路径信息
        return $this->to($this->generator->getRequest()->path(), $status, $headers);
    }

    /**
     * Create a new redirect response, while putting the current URL in the session.
     *
     * 在会话中放置当前URL时，创建一个新的重定向响应
     *
     * @param  string  $path
     * @param  int     $status
     * @param  array   $headers
     * @param  bool    $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function guest($path, $status = 302, $headers = [], $secure = null)
    {
        // 将键/值对或数组中的键/值对放入session('',获取当前请求的完整URL)
        $this->session->put('url.intended', $this->generator->full());
        //为给定路径创建新的重定向响应
        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * Create a new redirect response to the previously intended location.
     *
     * 创建一个新的重定向响应到先前预定的位置
     *
     * @param  string  $default
     * @param  int     $status
     * @param  array   $headers
     * @param  bool    $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function intended($default = '/', $status = 302, $headers = [], $secure = null)
    {
        // 将键/值对或数组中的键/值对放入session
        $path = $this->session->pull('url.intended', $default);
        //为给定路径创建新的重定向响应
        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * Create a new redirect response to the given path.
	 *
	 * 为给定路径创建新的重定向响应
     *
     * @param  string  $path
     * @param  int     $status
     * @param  array   $headers
     * @param  bool    $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function to($path, $status = 302, $headers = [], $secure = null)
    {
		//      创建一个新的重定向响应     UrlGenerator::to生成给定路径的绝对URL
        return $this->createRedirect($this->generator->to($path, [], $secure), $status, $headers);
    }

    /**
     * Create a new redirect response to an external URL (no validation).
     *
     * 创建一个新的重定向响应外部URL（没有验证）
     *
     * @param  string  $path
     * @param  int     $status
     * @param  array   $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function away($path, $status = 302, $headers = [])
    {
        //         创建一个新的重定向响应
        return $this->createRedirect($path, $status, $headers);
    }

    /**
     * Create a new redirect response to the given HTTPS path.
     *
     * 创建一个新的重定向响应到给定的HTTPS路径
     *
     * @param  string  $path
     * @param  int     $status
     * @param  array   $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function secure($path, $status = 302, $headers = [])
    {
        //         创建一个新的重定向响应
        return $this->to($path, $status, $headers, true);
    }

    /**
     * Create a new redirect response to a named route.
     *
     * 为命名路由创建新的重定向响应
     *
     * @param  string  $route
     * @param  array   $parameters
     * @param  int     $status
     * @param  array   $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function route($route, $parameters = [], $status = 302, $headers = [])
    {
        //        为给定路径创建新的重定向响应    获取指定路由的URL
        return $this->to($this->generator->route($route, $parameters), $status, $headers);
    }

    /**
     * Create a new redirect response to a controller action.
     *
     * 为控制器动作创建新的重定向响应
     *
     * @param  string  $action
     * @param  array   $parameters
     * @param  int     $status
     * @param  array   $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function action($action, $parameters = [], $status = 302, $headers = [])
    {
        //        为给定路径创建新的重定向响应    获取控制器动作的URL
        return $this->to($this->generator->action($action, $parameters), $status, $headers);
    }

    /**
     * Create a new redirect response.
	 *
	 * 创建一个新的重定向响应
     *
     * @param  string  $path
     * @param  int     $status
     * @param  array   $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function createRedirect($path, $status, $headers)
    {
		//    用给定的值调用给定的闭包，然后返回值(\Illuminate\Http\RedirectResponse,闭包(\Illuminate\Http\RedirectResponse))
        return tap(new RedirectResponse($path, $status, $headers), function ($redirect) {
            if (isset($this->session)) {
                $redirect->setSession($this->session); //设置会话存储实现
            }
            //   设置请求实例(UrlGenerator::获取请求实例)
            $redirect->setRequest($this->generator->getRequest());
        });
    }

    /**
     * Get the URL generator instance.
     *
     * 获取URL生成器实例
     *
     * @return \Illuminate\Routing\UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->generator;
    }

    /**
     * Set the active session store.
     *
     * 设置活动会话存储
     *
     * @param  \Illuminate\Session\Store  $session
     * @return void
     */
    public function setSession(SessionStore $session)
    {
        $this->session = $session;
    }
}
