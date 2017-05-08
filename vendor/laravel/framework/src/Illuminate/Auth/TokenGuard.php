<?php

namespace Illuminate\Auth;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;

class TokenGuard implements Guard
{
    use GuardHelpers;

    /**
     * The request instance.
     *
     * 请求实例
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The name of the query string item from the request containing the API token.
     *
     * 来自包含API令牌的请求的查询字符串项的名称
     *
     * @var string
     */
    protected $inputKey;

    /**
     * The name of the token "column" in persistent storage.
     *
     * 持久存储中的令牌“列”的名称
     *
     * @var string
     */
    protected $storageKey;

    /**
     * Create a new authentication guard.
     *
     * 创建一个新的身份验证保护
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(UserProvider $provider, Request $request)
    {
        $this->request = $request;
        $this->provider = $provider;
        $this->inputKey = 'api_token';
        $this->storageKey = 'api_token';
    }

    /**
     * Get the currently authenticated user.
     *
     * 获取当前经过身份验证的用户
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

        $user = null;
        //        获取当前请求的令牌
        $token = $this->getTokenForRequest();

        if (! empty($token)) {
            //                      通过给定的凭证检索用户
            $user = $this->provider->retrieveByCredentials(
                [$this->storageKey => $token]
            );
        }

        return $this->user = $user;
    }

    /**
     * Get the token for the current request.
     *
     * 获取当前请求的令牌
     *
     * @return string
     */
    public function getTokenForRequest()
    {
        $token = $this->request->query($this->inputKey);//从请求中检索查询字符串项

        if (empty($token)) {
            $token = $this->request->input($this->inputKey);//从请求中检索输入项
        }

        if (empty($token)) {
            $token = $this->request->bearerToken();//从请求标头获取承载令牌
        }

        if (empty($token)) {
            $token = $this->request->getPassword();//返回密码
        }

        return $token;
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
        if (empty($credentials[$this->inputKey])) {
            return false;
        }

        $credentials = [$this->storageKey => $credentials[$this->inputKey]];
        //                通过给定的凭证检索用户
        if ($this->provider->retrieveByCredentials($credentials)) {
            return true;
        }

        return false;
    }

    /**
     * Set the current request instance.
     *
     * 设置当前的请求实例
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
