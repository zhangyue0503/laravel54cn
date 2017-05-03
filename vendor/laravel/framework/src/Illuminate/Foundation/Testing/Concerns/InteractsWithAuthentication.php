<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
//认证配合
trait InteractsWithAuthentication
{
    /**
     * Set the currently logged in user for the application.
     *
     * 为应用程序设置当前登录的用户
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string|null  $driver
     * @return $this
     */
    public function actingAs(UserContract $user, $driver = null)
    {
        //为应用程序设置当前登录的用户
        $this->be($user, $driver);

        return $this;
    }

    /**
     * Set the currently logged in user for the application.
     *
     * 为应用程序设置当前登录的用户
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string|null  $driver
     * @return void
     */
    public function be(UserContract $user, $driver = null)
    {
        //                图从本地缓存中得到守卫   设置当前用户
        $this->app['auth']->guard($driver)->setUser($user);
        //设置工厂应该服务的默认保护驱动程序
        $this->app['auth']->shouldUse($driver);
    }

    /**
     * Assert that the user is authenticated.
     *
     * 断言用户是通过身份验证的
     *
     * @param  string|null  $guard
     * @return $this
     */
    public function seeIsAuthenticated($guard = null)
    {
        //     断言一个条件是正确的    如果用户通过身份验证，则返回true
        $this->assertTrue($this->isAuthenticated($guard), 'The user is not authenticated');

        return $this;
    }

    /**
     * Assert that the user is not authenticated.
     *
     * 断言用户没有经过身份验证
     *
     * @param  string|null  $guard
     * @return $this
     */
    public function dontSeeIsAuthenticated($guard = null)
    {
        //   断言一个条件是假的     如果用户通过身份验证，则返回true
        $this->assertFalse($this->isAuthenticated($guard), 'The user is authenticated');

        return $this;
    }

    /**
     * Return true if the user is authenticated, false otherwise.
     *
     * 如果用户通过身份验证，则返回true
     *
     * @param  string|null  $guard
     * @return bool
     */
    protected function isAuthenticated($guard = null)
    {
        //      从容器中解析给定类型 图从本地缓存中得到守卫  确定当前用户是否已通过身份验证
        return $this->app->make('auth')->guard($guard)->check();
    }

    /**
     * Assert that the user is authenticated as the given user.
     *
     * 断言用户是作为给定用户进行身份验证的
     *
     * @param  $user
     * @param  string|null  $guard
     * @return $this
     */
    public function seeIsAuthenticatedAs($user, $guard = null)
    {
        //           从容器中解析给定类型 图从本地缓存中得到守卫     获取当前经过身份验证的用户
        $expected = $this->app->make('auth')->guard($guard)->user();
        //断言变量是给定类型的
        $this->assertInstanceOf(
            get_class($expected), $user,
            'The currently authenticated user is not who was expected'
        );
        //断言两个变量具有相同的类型和值
        $this->assertSame(
            //获取用户的唯一标识符
            $expected->getAuthIdentifier(), $user->getAuthIdentifier(),
            'The currently authenticated user is not who was expected'
        );

        return $this;
    }

    /**
     * Assert that the given credentials are valid.
     *
     * 断言给定的凭证是有效的
     *
     * @param  array  $credentials
     * @param  string|null  $guard
     * @return $this
     */
    public function seeCredentials(array $credentials, $guard = null)
    {
        //断言一个条件是正确的
        $this->assertTrue(
            //返回true是凭证是有效的，否则是假的
            $this->hasCredentials($credentials, $guard), 'The given credentials are invalid.'
        );

        return $this;
    }

    /**
     * Assert that the given credentials are invalid.
     *
     * 断言给定的凭证是无效的
     *
     * @param  array  $credentials
     * @param  string|null  $guard
     * @return $this
     */
    public function dontSeeCredentials(array $credentials, $guard = null)
    {
        //断言一个条件是假的
        $this->assertFalse(
        //返回true是凭证是有效的，否则是假的
            $this->hasCredentials($credentials, $guard), 'The given credentials are valid.'
        );

        return $this;
    }

    /**
     * Return true is the credentials are valid, false otherwise.
     *
     * 返回true是凭证是有效的，否则是假的
     *
     * @param  array  $credentials
     * @param  string|null  $guard
     * @return bool
     */
    protected function hasCredentials(array $credentials, $guard = null)
    {
        //           从容器中解析给定类型 图从本地缓存中得到守卫   获取保护器使用的用户提供程序
        $provider = $this->app->make('auth')->guard($guard)->getProvider();
        //               通过给定的凭证检索用户
        $user = $provider->retrieveByCredentials($credentials);
        //                      根据给定的凭据对用户进行验证
        return $user && $provider->validateCredentials($user, $credentials);
    }
}
