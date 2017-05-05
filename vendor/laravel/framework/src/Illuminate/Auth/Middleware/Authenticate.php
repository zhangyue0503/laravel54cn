<?php

namespace Illuminate\Auth\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    /**
     * The authentication factory instance.
     *
     * 身份验证工厂实例
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
    public function __construct(Auth $auth)
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
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($guards);//确定用户是否登录到任何给定的保护中

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
	 *
	 * 确定用户是否登录到任何给定的保护中
     *
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate(array $guards)
    {
        if (empty($guards)) {
            return $this->auth->authenticate();//确定用户是否登录到任何给定的保护中
        }

        foreach ($guards as $guard) {
            //通过名称获取守护实例->确定是否给予给定的能力
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);//设置工厂应该提供的默认保护
            }
        }

        throw new AuthenticationException('Unauthenticated.', $guards);
    }
}
