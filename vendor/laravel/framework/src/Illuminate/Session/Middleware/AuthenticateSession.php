<?php

namespace Illuminate\Session\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

class AuthenticateSession
{
    /**
     * The authentication factory implementation.
     *
     * 身份验证工厂实现
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * 创建一个新的中间件实例
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(AuthFactory $auth)
    {
        $this->auth = $auth;
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
        //获取用户请求             获取与请求关联的会话
        if (! $request->user() || ! $request->session()) {
            return $next($request);
        }
        //                  检查一个键是否存在并且不是空                 确定用户是否通过“记住我”cookie进行了身份验证
        if (! $request->session()->has('password_hash') && $this->auth->viaRemember()) {
            $this->logout($request);//将用户从应用程序中记录下来
        }

        if (! $request->session()->has('password_hash')) {
            $this->storePasswordHashInSession($request);//在会话中存储用户当前密码散列
        }
        //                      从会话中获取项目                             获取用户的密码
        if ($request->session()->get('password_hash') !== $request->user()->getAuthPassword()) {
            $this->logout($request);
        }

        return tap($next($request), function () use ($request) {
            $this->storePasswordHashInSession($request);
        });
    }

    /**
     * Store the user's current password hash in the session.
     *
     * 在会话中存储用户当前密码散列
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function storePasswordHashInSession($request)
    {
        //获取用户请求
        if (! $request->user()) {
            return;
        }
        //             将键/值对或数组中的键/值对放入session
        $request->session()->put([
            //                                    获取用户的密码
            'password_hash' => $request->user()->getAuthPassword(),
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * 将用户从应用程序中记录下来
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function logout($request)
    {
        $this->auth->logout();//记录用户从应用程序中离开
        //                  从会话中移除所有项目
        $request->session()->flush();

        throw new AuthenticationException;
    }
}
