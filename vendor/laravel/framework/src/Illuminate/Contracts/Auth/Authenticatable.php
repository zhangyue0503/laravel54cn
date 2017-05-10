<?php

namespace Illuminate\Contracts\Auth;

interface Authenticatable
{
    /**
     * Get the name of the unique identifier for the user.
     *
     * 为用户获取唯一标识符的名称
     *
     * @return string
     */
    public function getAuthIdentifierName();

    /**
     * Get the unique identifier for the user.
     *
     * 获取用户的唯一标识符
     *
     * @return mixed
     */
    public function getAuthIdentifier();

    /**
     * Get the password for the user.
     *
     * 获取用户的密码
     *
     * @return string
     */
    public function getAuthPassword();

    /**
     * Get the token value for the "remember me" session.
     *
     * 获取“记住我”会话的令牌值
     *
     * @return string
     */
    public function getRememberToken();

    /**
     * Set the token value for the "remember me" session.
     *
     * 为“记住我”的会话设置令牌值
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value);

    /**
     * Get the column name for the "remember me" token.
     *
     * 获取“记住我”标记的列名
     *
     * @return string
     */
    public function getRememberTokenName();
}
