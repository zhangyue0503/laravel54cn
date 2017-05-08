<?php

namespace Illuminate\Auth;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

/**
 * These methods are typically the same across all guards.
 *
 * 这些方法在所有的警卫中都是相同的
 */
trait GuardHelpers
{
    /**
     * The currently authenticated user.
     *
     * 当前通过身份验证的用户
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $user;

    /**
     * The user provider implementation.
     *
     * 用户提供程序实现
     *
     * @var \Illuminate\Contracts\Auth\UserProvider
     */
    protected $provider;

    /**
     * Determine if the current user is authenticated.
     *
     * 确定当前用户是否已通过身份验证
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function authenticate()
    {
        if (! is_null($user = $this->user())) {
            return $user;
        }

        throw new AuthenticationException;
    }

    /**
     * Determine if the current user is authenticated.
	 *
	 * 确定当前用户是否身份验证
     *
     * @return bool
     */
    public function check()
    {
        //               获取当前经过身份验证的用户
        return ! is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
	 *
	 * 确定当前用户是否为客人
	 *
     * @return bool
     */
    public function guest()
    {
        return ! $this->check();//确定当前用户是否身份验证
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * 获取当前身份验证用户的ID
     *
     * @return int|null
     */
    public function id()
    {
        //获取当前经过身份验证的用户
        if ($this->user()) {
            // 获取当前经过身份验证的用户->获取用户的唯一标识符
            return $this->user()->getAuthIdentifier();
        }
    }

    /**
     * Set the current user.
     *
     * 设置当前用户
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return $this
     */
    public function setUser(AuthenticatableContract $user)
    {
        $this->user = $user;

        return $this;
    }
}
