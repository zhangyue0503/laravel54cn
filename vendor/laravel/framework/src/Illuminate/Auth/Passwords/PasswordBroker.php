<?php

namespace Illuminate\Auth\Passwords;

use Closure;
use Illuminate\Support\Arr;
use UnexpectedValueException;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\PasswordBroker as PasswordBrokerContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class PasswordBroker implements PasswordBrokerContract
{
    /**
     * The password token repository.
     *
     * 密码令牌存储库
     *
     * @var \Illuminate\Auth\Passwords\TokenRepositoryInterface
     */
    protected $tokens;

    /**
     * The user provider implementation.
     *
     * 用户提供者程序实现
     *
     * @var \Illuminate\Contracts\Auth\UserProvider
     */
    protected $users;

    /**
     * The custom password validator callback.
     *
     * 自定义密码验证器回调
     *
     * @var \Closure
     */
    protected $passwordValidator;

    /**
     * Create a new password broker instance.
     *
     * 创建一个新的密码代理实例
     *
     * @param  \Illuminate\Auth\Passwords\TokenRepositoryInterface  $tokens
     * @param  \Illuminate\Contracts\Auth\UserProvider  $users
     * @return void
     */
    public function __construct(TokenRepositoryInterface $tokens,
                                UserProvider $users)
    {
        $this->users = $users;
        $this->tokens = $tokens;
    }

    /**
     * Send a password reset link to a user.
     *
     * 将密码重置链接发送给用户
     *
     * @param  array  $credentials
     * @return string
     */
    public function sendResetLink(array $credentials)
    {
        // First we will check to see if we found a user at the given credentials and
        // if we did not we will redirect back to this current URI with a piece of
        // "flash" data in the session to indicate to the developers the errors.
        //
        // 首先,我们将检查如果我们发现用户在给定的凭证,如果我们没有重定向回当前URI的一张“闪”数据会话显示开发人员的错误
        //
        //           为给定的凭证获取用户
        $user = $this->getUser($credentials);

        if (is_null($user)) {
            return static::INVALID_USER;
        }

        // Once we have the reset token, we are ready to send the message out to this
        // user with a link to reset their password. We will then redirect back to
        // the current URI having nothing set in the session to indicate errors.
        //
        // 一旦我们有了重置令牌，我们就准备将消息发送给这个用户，并使用一个链接来重置密码
        // 然后，我们将重定向回当前的URI，在会话中没有设置任何设置来指示错误
        //
        //        发送密码重置通知
        $user->sendPasswordResetNotification(
            $this->tokens->create($user)//创建一个新令牌
        );

        return static::RESET_LINK_SENT;
    }

    /**
     * Reset the password for the given token.
     *
     * 重置给定令牌的密码
     *
     * @param  array  $credentials
     * @param  \Closure  $callback
     * @return mixed
     */
    public function reset(array $credentials, Closure $callback)
    {
        // If the responses from the validate method is not a user instance, we will
        // assume that it is a redirect and simply return it from this method and
        // the user is properly redirected having an error message on the post.
        //
        // 如果验证方法的反应不是用户实例,我们将认为这是一个重定向,只是从这个方法返回它和正确地重定向用户的对这个职位产生一条错误消息
        //
        //           验证给定凭证的密码重置
        $user = $this->validateReset($credentials);

        if (! $user instanceof CanResetPasswordContract) {
            return $user;
        }

        $password = $credentials['password'];

        // Once the reset has been validated, we'll call the given callback with the
        // new password. This gives the user an opportunity to store the password
        // in their persistent storage. Then we'll delete the token and return.
        //
        // 一旦重新设置了重置，我们将使用新的密码调用给定的回调
        // 这为用户提供了在持久存储中存储密码的机会
        // 然后我们将删除令牌和返回
        //
        $callback($user, $password);
        //删除一个令牌记录
        $this->tokens->delete($user);

        return static::PASSWORD_RESET;
    }

    /**
     * Validate a password reset for the given credentials.
     *
     * 验证给定凭证的密码重置
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\CanResetPassword
     */
    protected function validateReset(array $credentials)
    {
        //                         为给定的凭证获取用户
        if (is_null($user = $this->getUser($credentials))) {
            return static::INVALID_USER;
        }
        //           确定密码是否匹配请求
        if (! $this->validateNewPassword($credentials)) {
            return static::INVALID_PASSWORD;
        }
        //确定一个令牌记录是否存在并且是有效的
        if (! $this->tokens->exists($user, $credentials['token'])) {
            return static::INVALID_TOKEN;
        }

        return $user;
    }

    /**
     * Set a custom password validator.
     *
     * 设置一个自定义密码验证器
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function validator(Closure $callback)
    {
        $this->passwordValidator = $callback;
    }

    /**
     * Determine if the passwords match for the request.
     *
     * 确定密码是否匹配请求
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validateNewPassword(array $credentials)
    {
        if (isset($this->passwordValidator)) {
            list($password, $confirm) = [
                $credentials['password'],
                $credentials['password_confirmation'],
            ];

            return call_user_func(
                $this->passwordValidator, $credentials
            ) && $password === $confirm;
        }
        //            确定密码是否对请求有效
        return $this->validatePasswordWithDefaults($credentials);
    }

    /**
     * Determine if the passwords are valid for the request.
     *
     * 确定密码是否对请求有效
     *
     * @param  array  $credentials
     * @return bool
     */
    protected function validatePasswordWithDefaults(array $credentials)
    {
        list($password, $confirm) = [
            $credentials['password'],
            $credentials['password_confirmation'],
        ];

        return $password === $confirm && mb_strlen($password) >= 6;
    }

    /**
     * Get the user for the given credentials.
     *
     * 为给定的凭证获取用户
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\CanResetPassword
     *
     * @throws \UnexpectedValueException
     */
    public function getUser(array $credentials)
    {
        //                获取指定数组，除了指定的数组项
        $credentials = Arr::except($credentials, ['token']);
        //                   通过给定的凭证检索用户
        $user = $this->users->retrieveByCredentials($credentials);

        if ($user && ! $user instanceof CanResetPasswordContract) {
            throw new UnexpectedValueException('User must implement CanResetPassword interface.');
        }

        return $user;
    }

    /**
     * Create a new password reset token for the given user.
     *
     * 为给定的用户创建一个新的密码重置令牌
     *
     * @param  CanResetPasswordContract $user
     * @return string
     */
    public function createToken(CanResetPasswordContract $user)
    {
        //                    创建一个新令牌
        return $this->tokens->create($user);
    }

    /**
     * Delete password reset tokens of the given user.
     *
     * 删除给定用户的密码重置令牌
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword $user
     * @return void
     */
    public function deleteToken(CanResetPasswordContract $user)
    {
        //删除一个令牌记录
        $this->tokens->delete($user);
    }

    /**
     * Validate the given password reset token.
     *
     * 验证给定的密码重置令牌
     *
     * @param  CanResetPasswordContract $user
     * @param  string $token
     * @return bool
     */
    public function tokenExists(CanResetPasswordContract $user, $token)
    {
        //               确定一个令牌记录是否存在并且是有效的
        return $this->tokens->exists($user, $token);
    }

    /**
     * Get the password reset token repository implementation.
     *
     * 获取密码重置令牌存储库实现
     *
     * @return \Illuminate\Auth\Passwords\TokenRepositoryInterface
     */
    public function getRepository()
    {
        return $this->tokens;
    }
}
