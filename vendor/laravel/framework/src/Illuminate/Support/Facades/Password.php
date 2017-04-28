<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Auth\Passwords\PasswordBroker
 */
class Password extends Facade
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
     * 常量表示成功重置密码
     *
     * @var string
     */
    const PASSWORD_RESET = 'passwords.reset';

    /**
     * Constant representing the user not found response.
     *
     * 常量表示用户未找到响应
     *
     * @var string
     */
    const INVALID_USER = 'passwords.user';

    /**
     * Constant representing an invalid password.
     *
     * 常量表示无效密码
     *
     * @var string
     */
    const INVALID_PASSWORD = 'passwords.password';

    /**
     * Constant representing an invalid token.
     *
     * 常量表示无效令牌
     *
     * @var string
     */
    const INVALID_TOKEN = 'passwords.token';

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'auth.password';
    }
}
