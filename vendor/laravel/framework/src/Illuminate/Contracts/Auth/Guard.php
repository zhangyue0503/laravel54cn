<?php

namespace Illuminate\Contracts\Auth;

interface Guard
{
    /**
     * Determine if the current user is authenticated.
     *
     * 确定当前用户是否已通过身份验证
     *
     * @return bool
     */
    public function check();

    /**
     * Determine if the current user is a guest.
     *
     * 确定当前用户是否是一个客户
     *
     * @return bool
     */
    public function guest();

    /**
     * Get the currently authenticated user.
     *
     * 获取当前经过身份验证的用户
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user();

    /**
     * Get the ID for the currently authenticated user.
     *
     * 获取当前身份验证用户的ID
     *
     * @return int|null
     */
    public function id();

    /**
     * Validate a user's credentials.
     *
     * 验证用户的凭证
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = []);

    /**
     * Set the current user.
     *
     * 设置当前用户
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser(Authenticatable $user);
}
