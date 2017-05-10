<?php

namespace Illuminate\Contracts\Auth;

use Closure;

interface PasswordBroker
{
    /**
     * Constant representing a successfully sent reminder.
     *
     * 常量表示成功发送的提醒
     *
     * @var string
     */
    const RESET_LINK_SENT = 'passwords.sent';

    /**
     * Constant representing a successfully reset password.
     *
     * 常量表示一个成功的重设密码
     *
     * @var string
     */
    const PASSWORD_RESET = 'passwords.reset';

    /**
     * Constant representing the user not found response.
     *
     * 常量表示用户没有找到响应
     *
     * @var string
     */
    const INVALID_USER = 'passwords.user';

    /**
     * Constant representing an invalid password.
     *
     * 常量表示一个无效的密码
     *
     * @var string
     */
    const INVALID_PASSWORD = 'passwords.password';

    /**
     * Constant representing an invalid token.
     *
     * 常量表示无效的令牌
     *
     * @var string
     */
    const INVALID_TOKEN = 'passwords.token';

    /**
     * Send a password reset link to a user.
     *
     * 将密码重置链接发送给用户
     *
     * @param  array  $credentials
     * @return string
     */
    public function sendResetLink(array $credentials);

    /**
     * Reset the password for the given token.
     *
     * 重置给定令牌的密码
     *
     * @param  array     $credentials
     * @param  \Closure  $callback
     * @return mixed
     */
    public function reset(array $credentials, Closure $callback);

    /**
     * Set a custom password validator.
     *
     * 设置一个自定义密码验证器
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function validator(Closure $callback);

    /**
     * Determine if the passwords match for the request.
     *
     * 确定密码是否匹配请求
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validateNewPassword(array $credentials);
}
