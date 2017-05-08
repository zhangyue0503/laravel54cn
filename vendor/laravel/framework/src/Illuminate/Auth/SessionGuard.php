<?php

namespace Illuminate\Auth;

use RuntimeException;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Auth\StatefulGuard;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Contracts\Auth\SupportsBasicAuth;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class SessionGuard implements StatefulGuard, SupportsBasicAuth
{
    use GuardHelpers;

    /**
     * The name of the Guard. Typically "session".
     *
     * Guard的名字。典型的“会话”
     *
     * Corresponds to guard name in authentication configuration.
     *
     * @var string
     */
    protected $name;

    /**
     * The user we last attempted to retrieve.
     *
     * 我们上次尝试检索的用户
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $lastAttempted;

    /**
     * Indicates if the user was authenticated via a recaller cookie.
     *
     * 显示用户是否通过recaller cookie进行了身份验证
     *
     * @var bool
     */
    protected $viaRemember = false;

    /**
     * The session used by the guard.
     *
     * guard使用的会话
     *
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * The Illuminate cookie creator service.
     *
     * Illuminate cookie的创造者服务
     *
     * @var \Illuminate\Contracts\Cookie\QueueingFactory
     */
    protected $cookie;

    /**
     * The request instance.
     *
     * 请求实例
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * The event dispatcher instance.
     *
     * 事件调度程序实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Indicates if the logout method has been called.
     *
     * 表示是否已调用logout方法
     *
     * @var bool
     */
    protected $loggedOut = false;

    /**
     * Indicates if a token user retrieval has been attempted.
     *
     * 表示是否已经尝试过令牌用户检索
     *
     * @var bool
     */
    protected $recallAttempted = false;

    /**
     * Create a new authentication guard.
     *
     * 创建一个新的身份验证保护
     *
     * @param  string  $name
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return void
     */
    public function __construct($name,
                                UserProvider $provider,
                                Session $session,
                                Request $request = null)
    {
        $this->name = $name;
        $this->session = $session;
        $this->request = $request;
        $this->provider = $provider;
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
        if ($this->loggedOut) {
            return;
        }

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
        //         从会话中获得一个项目(为auth会话值获取唯一标识符)
        $id = $this->session->get($this->getName());

        // First we will try to load the user using the identifier in the session if
        // one exists. Otherwise we will check for a "remember me" cookie in this
        // request, and if one exists, attempt to retrieve the user using that.
        //
        // 首先，如果存在的话，我们将尝试使用会话中的标识符来加载用户
        // 否则，我们将在这个请求中检查一个“记住我”的cookie，如果存在的话，尝试用它来检索用户
        //
        $user = null;

        if (! is_null($id)) {
            //             通过惟一标识符检索用户
            if ($user = $this->provider->retrieveById($id)) {
                //如果调度程序被设置，则触发验证事件
                $this->fireAuthenticatedEvent($user);
            }
        }

        // If the user is null, but we decrypt a "recaller" cookie we can attempt to
        // pull the user data on that cookie which serves as a remember cookie on
        // the application. Once we have a user we can return it to the caller.
        //
        // 如果用户为空,但我们解密“recaller”cookie我们可以尝试把用户数据作为一个记得cookie在应用程序上
        // 一旦我们有了一个用户，我们就可以把它返回给调用者
        //
        //            为请求获取解密的recaller cookie
        $recaller = $this->recaller();

        if (is_null($user) && ! is_null($recaller)) {
            //        通过“记住我”cookie令牌从存储库中拉出用户
            $user = $this->userFromRecaller($recaller);

            if ($user) {
                //  使用给定的ID更新会话(获取用户的唯一标识符)
                $this->updateSession($user->getAuthIdentifier());
                //如果调度程序被设置，则触发登录事件
                $this->fireLoginEvent($user, true);
            }
        }

        return $this->user = $user;
    }

    /**
     * Pull a user from the repository by its "remember me" cookie token.
     *
     * 通过“记住我”cookie令牌从存储库中拉出用户
     *
     * @param  string  $recaller
     * @return mixed
     */
    protected function userFromRecaller($recaller)
    {
        // 确定recaller是否有效
        if (! $recaller->valid() || $this->recallAttempted) {
            return;
        }

        // If the user is null, but we decrypt a "recaller" cookie we can attempt to
        // pull the user data on that cookie which serves as a remember cookie on
        // the application. Once we have a user we can return it to the caller.
        //
        // 如果用户为空,但我们解密“recaller”cookie我们可以尝试把用户数据作为一个记得cookie在应用程序上
        // 一旦我们有了一个用户，我们就可以把它返回给调用者
        //
        $this->recallAttempted = true;
        //                                           通过其唯一标识符检索用户并“记住我”令牌
        $this->viaRemember = ! is_null($user = $this->provider->retrieveByToken(
            //从recaller获取用户ID        从recaller获得“记住牌”令牌
            $recaller->id(), $recaller->token()
        ));

        return $user;
    }

    /**
     * Get the decrypted recaller cookie for the request.
     *
     * 为请求获取解密的recaller cookie
     *
     * @return \Illuminate\Auth\Recaller|null
     */
    protected function recaller()
    {
        if (is_null($this->request)) {
            return;
        }
        //                              返回参数通过key名(获取cookie的名字用于存储“recaller”)
        if ($recaller = $this->request->cookies->get($this->getRecallerName())) {
            return new Recaller($recaller);
        }
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
        if ($this->loggedOut) {
            return;
        }

        return $this->user()//获取当前经过身份验证的用户
                    ? $this->user()->getAuthIdentifier()//获取用户的唯一标识符
                    : $this->session->get($this->getName());//从会话中获得一个项目(为auth会话值获取唯一标识符)
    }

    /**
     * Log a user into the application without sessions or cookies.
     *
     * 在没有会话或cookie的情况下将用户登录到应用程序
     *
     * @param  array  $credentials
     * @return bool
     */
    public function once(array $credentials = [])
    {
        //用参数触发尝试事件
        $this->fireAttemptEvent($credentials);
        //验证用户的凭证
        if ($this->validate($credentials)) {
            //设置当前用户
            $this->setUser($this->lastAttempted);

            return true;
        }

        return false;
    }

    /**
     * Log the given user ID into the application without sessions or cookies.
     *
     * 将给定的用户ID记录到应用程序中，而不需要会话或cookie
     *
     * @param  mixed  $id
     * @return \Illuminate\Contracts\Auth\Authenticatable|false
     */
    public function onceUsingId($id)
    {
        //                                通过惟一标识符检索用户
        if (! is_null($user = $this->provider->retrieveById($id))) {
            $this->setUser($user);//设置当前用户

            return $user;
        }

        return false;
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
        //                                             通过给定的凭证检索用户
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);
        //确定用户是否匹配凭据
        return $this->hasValidCredentials($user, $credentials);
    }

    /**
     * Attempt to authenticate using HTTP Basic Auth.
     *
     * 尝试使用HTTP基本身份验证进行身份验证
     *
     * @param  string  $field
     * @param  array  $extraConditions
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function basic($field = 'email', $extraConditions = [])
    {
        //确定当前用户是否已通过身份验证
        if ($this->check()) {
            return;
        }

        // If a username is set on the HTTP basic request, we will return out without
        // interrupting the request lifecycle. Otherwise, we'll need to generate a
        // request indicating that the given credentials were invalid for login.
        //
        // 如果用户名设置在HTTP基本请求上，我们将返回，而不会中断请求生命周期
        // 否则，我们将需要生成一个请求，表明给定的凭证对于登录是无效的
        //
        // 尝试使用基本身份验证进行身份验证(获取当前请求实例,)
        if ($this->attemptBasic($this->getRequest(), $field, $extraConditions)) {
            return;
        }
        //获得基本身份验证的响应
        return $this->failedBasicResponse();
    }

    /**
     * Perform a stateless HTTP Basic login attempt.
     *
     * 执行无状态的HTTP基本登录尝试
     *
     * @param  string  $field
     * @param  array  $extraConditions
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function onceBasic($field = 'email', $extraConditions = [])
    {
        //                  获取HTTP基本请求的凭据数组(获取当前请求实例,)
        $credentials = $this->basicCredentials($this->getRequest(), $field);
        //在没有会话或cookie的情况下将用户登录到应用程序
        if (! $this->once(array_merge($credentials, $extraConditions))) {
            //获得基本身份验证的响应
            return $this->failedBasicResponse();
        }
    }

    /**
     * Attempt to authenticate using basic authentication.
     *
     * 尝试使用基本身份验证进行身份验证
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  string  $field
     * @param  array  $extraConditions
     * @return bool
     */
    protected function attemptBasic(Request $request, $field, $extraConditions = [])
    {
        //返回用户
        if (! $request->getUser()) {
            return false;
        }
        //尝试使用给定的凭据对用户进行身份验证
        return $this->attempt(array_merge(
            //获取HTTP基本请求的凭据数组
            $this->basicCredentials($request, $field), $extraConditions
        ));
    }

    /**
     * Get the credential array for a HTTP Basic request.
     *
     * 获取HTTP基本请求的凭据数组
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  string  $field
     * @return array
     */
    protected function basicCredentials(Request $request, $field)
    {
        //                      返回用户                           返回密码
        return [$field => $request->getUser(), 'password' => $request->getPassword()];
    }

    /**
     * Get the response for basic authentication.
     *
     * 获得基本身份验证的响应
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function failedBasicResponse()
    {
        return new Response('Invalid credentials.', 401, ['WWW-Authenticate' => 'Basic']);
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * 尝试使用给定的凭据对用户进行身份验证
     *
     * @param  array  $credentials
     * @param  bool   $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        //用参数触发尝试事件
        $this->fireAttemptEvent($credentials, $remember);
        //                                                通过给定的凭证检索用户
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        // If an implementation of UserInterface was returned, we'll ask the provider
        // to validate the user against the given credentials, and if they are in
        // fact valid we'll log the users into the application and return true.
        //
        // 如果返回的用户界面是一个实现,我们将要求供应商验证用户对给定的凭证,如果他们实际上是有效的我们会记录用户到应用程序并返回true
        //
        //确定用户是否匹配凭据
        if ($this->hasValidCredentials($user, $credentials)) {
            $this->login($user, $remember);//将用户登录到应用程序中

            return true;
        }

        // If the authentication attempt fails we will fire an event so that the user
        // may be notified of any suspicious attempts to access their account from
        // an unrecognized user. A developer may listen to this event as needed.
        //
        // 如果身份验证尝试失败，我们将触发一个事件，这样用户就可以收到任何可疑的尝试，从未被识别的用户访问他们的帐户
        // 开发人员可以根据需要侦听此事件
        //
        //使用给定的参数触发失败的身份验证尝试事件
        $this->fireFailedEvent($user, $credentials);

        return false;
    }

    /**
     * Determine if the user matches the credentials.
     *
     * 确定用户是否匹配凭据
     *
     * @param  mixed  $user
     * @param  array  $credentials
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        //                                        根据给定的凭据对用户进行验证
        return ! is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Log the given user ID into the application.
     *
     * 将给定的用户ID记录到应用程序中
     *
     * @param  mixed  $id
     * @param  bool   $remember
     * @return \Illuminate\Contracts\Auth\Authenticatable|false
     */
    public function loginUsingId($id, $remember = false)
    {
        //                                     通过惟一标识符检索用户
        if (! is_null($user = $this->provider->retrieveById($id))) {
            $this->login($user, $remember);//将用户登录到应用程序中

            return $user;
        }

        return false;
    }

    /**
     * Log a user into the application.
     *
     * 将用户登录到应用程序中
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(AuthenticatableContract $user, $remember = false)
    {
        //使用给定的ID更新会话(获取用户的唯一标识符)
        $this->updateSession($user->getAuthIdentifier());

        // If the user should be permanently "remembered" by the application we will
        // queue a permanent cookie that contains the encrypted copy of the user
        // identifier. We will then decrypt this later to retrieve the users.
        //
        // 如果用户应该永久地“记住”应用程序，我们将会对一个包含了用户标识符的加密副本的永久cookie进行排队
        // 稍后我们将对其进行解密，以检索用户
        //
        if ($remember) {
            //为用户创建一个新的“记住me”令牌，如果用户还不存在的话
            $this->ensureRememberTokenIsSet($user);
            //将recaller cookie放入cookie jar中
            $this->queueRecallerCookie($user);
        }

        // If we have an event dispatcher instance set we will fire an event so that
        // any listeners will hook into the authentication events and run actions
        // based on the login and logout events fired from the guard instances.
        //
        // 如果我们有一个事件调度程序实例集，我们将触发一个事件，以便任何侦听器都能连接到身份验证事件，并基于从警卫实例中触发的登录和注销事件运行操作
        //
        //如果调度程序被设置，则触发登录事件
        $this->fireLoginEvent($user, $remember);
        //设置当前用户
        $this->setUser($user);
    }

    /**
     * Update the session with the given ID.
     *
     * 使用给定的ID更新会话
     *
     * @param  string  $id
     * @return void
     */
    protected function updateSession($id)
    {
        //在会话中放置键/值对或数组键/值对(为auth会话值获取唯一标识符,)
        $this->session->put($this->getName(), $id);
        //为会话生成一个新的会话ID
        $this->session->migrate(true);
    }

    /**
     * Create a new "remember me" token for the user if one doesn't already exist.
     *
     * 为用户创建一个新的“记住me”令牌，如果用户还不存在的话
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function ensureRememberTokenIsSet(AuthenticatableContract $user)
    {
        //获取“记住我”会话的令牌值
        if (empty($user->getRememberToken())) {
            $this->cycleRememberToken($user);//为用户刷新“记住我”的标记
        }
    }

    /**
     * Queue the recaller cookie into the cookie jar.
     *
     * 将recaller cookie放入cookie jar中
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function queueRecallerCookie(AuthenticatableContract $user)
    {
        //获取保护器使用的cookie创建实例->用下一个响应发送一个cookie来发送(为给定的ID创建一个“记住我”cookie)
        $this->getCookieJar()->queue($this->createRecaller(
            //获取用户的唯一标识符                   获取“记住我”会话的令牌值
            $user->getAuthIdentifier().'|'.$user->getRememberToken()
        ));
    }

    /**
     * Create a "remember me" cookie for a given ID.
     *
     * 为给定的ID创建一个“记住我”cookie
     *
     * @param  string  $value
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function createRecaller($value)
    {
        //获取保护器使用的cookie创建实例->创建一个cookie,持续“永远”(五年)(获取cookie的名字用于存储“recaller”,)
        return $this->getCookieJar()->forever($this->getRecallerName(), $value);
    }

    /**
     * Log the user out of the application.
     *
     * 记录用户从应用程序中离开
     *
     * @return void
     */
    public function logout()
    {
        //获取当前经过身份验证的用户
        $user = $this->user();

        // If we have an event dispatcher instance, we can fire off the logout event
        // so any further processing can be done. This allows the developer to be
        // listening for anytime a user signs out of this application manually.
        //
        // 如果我们有一个事件调度程序实例，我们可以触发注销事件，这样就可以进行任何进一步的处理
        // 这允许开发人员在任何用户手动退出该应用程序时侦听
        //
        // 从会话和cookie中删除用户数据
        $this->clearUserDataFromStorage();

        if (! is_null($this->user)) {
            //为用户刷新“记住我”的标记
            $this->cycleRememberToken($user);
        }

        if (isset($this->events)) {
            //将事件触发，直到返回第一个非空响应
            $this->events->dispatch(new Events\Logout($user));
        }

        // Once we have fired the logout event we will clear the users out of memory
        // so they are no longer available as the user is no longer considered as
        // being signed into this application and should not be available here.
        //
        // 一旦我们解雇了注销事件我们将明确的用户内存所以他们不再可用的用户不再被认为是作为签署了这个应用程序,不应该
        //
        $this->user = null;

        $this->loggedOut = true;
    }

    /**
     * Remove the user data from the session and cookies.
     *
     * 从会话和cookie中删除用户数据
     *
     * @return void
     */
    protected function clearUserDataFromStorage()
    {
        //从会话中删除一个条目，返回它的值(为auth会话值获取唯一标识符)
        $this->session->remove($this->getName());
        //              为请求获取解密的recaller cookie
        if (! is_null($this->recaller())) {
            //获取保护器使用的cookie创建实例->用下一个响应发送一个cookie来发送(获取保护器使用的cookie创建实例->过期的cookie(获取cookie的名字用于存储“recaller”))
            $this->getCookieJar()->queue($this->getCookieJar()
                    ->forget($this->getRecallerName()));
        }
    }

    /**
     * Refresh the "remember me" token for the user.
     *
     * 为用户刷新“记住我”的标记
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function cycleRememberToken(AuthenticatableContract $user)
    {
        //为“记住我”的会话设置令牌值(生成一个更真实的“随机”alpha数字字符串)
        $user->setRememberToken($token = Str::random(60));
        //              在存储中为给定用户更新“记住我”令牌
        $this->provider->updateRememberToken($user, $token);
    }

    /**
     * Register an authentication attempt event listener.
     *
     * 注册一个身份验证尝试事件监听器
     *
     * @param  mixed  $callback
     * @return void
     */
    public function attempting($callback)
    {
        if (isset($this->events)) {
            //用分配器注册事件监听器
            $this->events->listen(Events\Attempting::class, $callback);
        }
    }

    /**
     * Fire the attempt event with the arguments.
     *
     * 用参数触发尝试事件
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return void
     */
    protected function fireAttemptEvent(array $credentials, $remember = false)
    {
        if (isset($this->events)) {
            //将事件触发，直到返回第一个非空响应
            $this->events->dispatch(new Events\Attempting(
                $credentials, $remember
            ));
        }
    }

    /**
     * Fire the login event if the dispatcher is set.
     *
     * 如果调度程序被设置，则触发登录事件
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    protected function fireLoginEvent($user, $remember = false)
    {
        if (isset($this->events)) {
            //将事件触发，直到返回第一个非空响应
            $this->events->dispatch(new Events\Login($user, $remember));
        }
    }

    /**
     * Fire the authenticated event if the dispatcher is set.
     *
     * 如果调度程序被设置，则触发验证事件
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function fireAuthenticatedEvent($user)
    {
        if (isset($this->events)) {
            //将事件触发，直到返回第一个非空响应
            $this->events->dispatch(new Events\Authenticated($user));
        }
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     *
     * 使用给定的参数触发失败的身份验证尝试事件
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $credentials
     * @return void
     */
    protected function fireFailedEvent($user, array $credentials)
    {
        if (isset($this->events)) {
            //将事件触发，直到返回第一个非空响应
            $this->events->dispatch(new Events\Failed($user, $credentials));
        }
    }

    /**
     * Get the last user we attempted to authenticate.
     *
     * 获取我们试图验证的最后一个用户
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function getLastAttempted()
    {
        return $this->lastAttempted;
    }

    /**
     * Get a unique identifier for the auth session value.
     *
     * 为auth会话值获取唯一标识符
     *
     * @return string
     */
    public function getName()
    {
        return 'login_'.$this->name.'_'.sha1(static::class);
    }

    /**
     * Get the name of the cookie used to store the "recaller".
     *
     * 获取cookie的名字用于存储“recaller”
     *
     * @return string
     */
    public function getRecallerName()
    {
        return 'remember_'.$this->name.'_'.sha1(static::class);
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * 确定用户是否通过“记住我”cookie进行了身份验证
     *
     * @return bool
     */
    public function viaRemember()
    {
        return $this->viaRemember;
    }

    /**
     * Get the cookie creator instance used by the guard.
     *
     * 获取保护器使用的cookie创建实例
     *
     * @return \Illuminate\Contracts\Cookie\QueueingFactory
     *
     * @throws \RuntimeException
     */
    public function getCookieJar()
    {
        if (! isset($this->cookie)) {
            throw new RuntimeException('Cookie jar has not been set.');
        }

        return $this->cookie;
    }

    /**
     * Set the cookie creator instance used by the guard.
     *
     * 设置保护器使用的cookie创建器实例
     *
     * @param  \Illuminate\Contracts\Cookie\QueueingFactory  $cookie
     * @return void
     */
    public function setCookieJar(CookieJar $cookie)
    {
        $this->cookie = $cookie;
    }

    /**
     * Get the event dispatcher instance.
     *
     * 获取事件调度程序实例
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance.
     *
     * 设置事件调度程序实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Get the session store used by the guard.
     *
     * 获得警卫使用的会话存储
     *
     * @return \Illuminate\Session\Store
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Get the user provider used by the guard.
     *
     * 获取保护器使用的用户提供程序
     *
     * @return \Illuminate\Contracts\Auth\UserProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set the user provider used by the guard.
     *
     * 设置保护器使用的用户提供程序
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @return void
     */
    public function setProvider(UserProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Return the currently cached user.
     *
     * 返回当前缓存的用户
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function getUser()
    {
        return $this->user;
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

        $this->loggedOut = false;
        //如果调度程序被设置，则触发验证事件
        $this->fireAuthenticatedEvent($user);

        return $this;
    }

    /**
     * Get the current request instance.
     *
     * 获取当前请求实例
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        //                              从PHP的超级全局变量创建一个新的请求
        return $this->request ?: Request::createFromGlobals();
    }

    /**
     * Set the current request instance.
     *
     * 设置当前的请求实例
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
