<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Lang;
//登录节流
trait ThrottlesLogins
{
    /**
     * Determine if the user has too many failed login attempts.
     *
     * 确定用户是否有太多失败的登录尝试
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function hasTooManyLoginAttempts(Request $request)
    {
        // 获得速率限制实例->确定给定的键是否被“访问”过多次
        return $this->limiter()->tooManyAttempts(
            //获得给定请求的节流键
            $this->throttleKey($request), 5, 1
        );
    }

    /**
     * Increment the login attempts for the user.
     *
     * 增加用户的登录尝试
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function incrementLoginAttempts(Request $request)
    {
        // 获得速率限制实例->为给定的衰减时间增加给定键的计数器（获得给定请求的节流键）
        $this->limiter()->hit($this->throttleKey($request));
    }

    /**
     * Redirect the user after determining they are locked out.
     *
     * 在确定用户被锁定后重定向用户
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendLockoutResponse(Request $request)
    {
        // 获得速率限制实例->获得秒数，直到“键”再次访问
        $seconds = $this->limiter()->availableIn(
            //获得给定请求的节流键
            $this->throttleKey($request)
        );

        $message = Lang::get('auth.throttle', ['seconds' => $seconds]);
        //获得控制器使用的登录用户名
        $errors = [$this->username() => $message];
        //确定当前请求是否可能需要JSON响应
        if ($request->expectsJson()) {
            //从应用程序返回新的响应->从应用程序返回一个新的JSON响应
            return response()->json($errors, 423);
        }
        //得到重定向器的实例->创建一个新的重定向响应到以前的位置
        return redirect()->back()
            //在会话中闪存输入的数组(从输入数据中获取包含所提供的键的子集(获得控制器使用的登录用户名,))
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);//将错误的容器闪存到会话中
    }

    /**
     * Clear the login locks for the given user credentials.
     *
     * 为给定的用户凭证清除登录锁
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function clearLoginAttempts(Request $request)
    {
        // 获得速率限制实例->清除给定键的点击和锁定(获得给定请求的节流键)
        $this->limiter()->clear($this->throttleKey($request));
    }

    /**
     * Fire an event when a lockout occurs.
     *
     * 当锁定发生时，触发一个事件
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function fireLockoutEvent(Request $request)
    {
        event(new Lockout($request));
    }

    /**
     * Get the throttle key for the given request.
     *
     * 获得给定请求的节流键
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function throttleKey(Request $request)
    {
        //将给定的字符串转为小写(从请求中检索输入项(获得控制器使用的登录用户名))
        return Str::lower($request->input($this->username())).'|'.$request->ip();
    }

    /**
     * Get the rate limiter instance.
     *
     * 获得速率限制实例
     *
     * @return \Illuminate\Cache\RateLimiter
     */
    protected function limiter()
    {
        return app(RateLimiter::class);
    }
}
