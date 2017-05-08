<?php

namespace Illuminate\Auth;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;

class RequestGuard implements Guard
{
    use GuardHelpers;

    /**
     * The guard callback.
     *
     * guard回调
     *
     * @var callable
     */
    protected $callback;

    /**
     * The request instance.
     *
     * 请求实例
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new authentication guard.
     *
     * 创建一个新的身份验证保护
     *
     * @param  callable  $callback
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(callable $callback, Request $request)
    {
        $this->request = $request;
        $this->callback = $callback;
    }

    /**
     * Get the currently authenticated user.
	 *
	 * 获取当前身份验证用户
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        //
        // 如果我们已经检索了当前请求的用户，我们可以立即返回它
        // 我们不想在每次调用这个方法时获取用户数据，因为那样会非常慢
        //
        if (! is_null($this->user)) {
            return $this->user;
        }

        return $this->user = call_user_func(
            $this->callback, $this->request
        );
    }

    /**
     * Validate a user's credentials.
     *
     * 验证用户的凭证
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return ! is_null((new static(
            $this->callback, $credentials['request']
        ))->user());//获取当前身份验证用户
    }

    /**
     * Set the current request instance.
     *
     * 设置当前请求实例
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
