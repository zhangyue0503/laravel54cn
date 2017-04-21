<?php

namespace Illuminate\Session\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Contracts\Session\Session;
use Illuminate\Session\CookieSessionHandler;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class StartSession
{
    /**
     * The session manager.
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $manager;

    /**
     * Indicates if the session was handled for the current request.
     *
     * @var bool
     */
    protected $sessionHandled = false;

    /**
     * Create a new session middleware.
	 *
	 * 创建一个新的session中间件
     *
     * @param  \Illuminate\Session\SessionManager  $manager
     * @return void
     */
    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle an incoming request.
	 *
	 * 处理传入的请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->sessionHandled = true;

        // If a session driver has been configured, we will need to start the session here
        // so that the data is ready for an application. Note that the Laravel sessions
        // do not make use of PHP "native" sessions in any way since they are crappy.
		//
		// 如果已配置会话驱动程序，我们将需要在这里启动会话，以便为应用程序准备好数据
		// 请注意，Laravel会话不让任何PHP会话使用“本地”因为他们是蹩脚的
		//
		//    确定会话驱动程序是否已配置
        if ($this->sessionConfigured()) {
            $request->setLaravelSession(//在请求上设置session实例
                $session = $this->startSession($request) //为给定的请求启动会话
            );
			//必要时删除会话中的垃圾
            $this->collectGarbage($session);
        }

        $response = $next($request);

        // Again, if the session has been configured we will need to close out the session
        // so that the attributes may be persisted to some storage medium. We will also
        // add the session identifier cookie to the application response headers now.
		//
		// 再次，如果会话已被配置，我们将需要关闭会话，以便将属性保留到某些存储介质中
		// 我们也会将会话标识符cookie添加到应用程序响应头中
		//
        if ($this->sessionConfigured()) {//确定会话驱动程序是否已配置
            $this->storeCurrentUrl($request, $session);//如果需要，存储请求的当前URL

            $this->addCookieToResponse($response, $session);//将会话cookie添加到应用程序响应中
        }

        return $response;
    }

    /**
     * Perform any final actions for the request lifecycle.
	 *
	 * 执行任何最后的行动请求的生命周期
	 * * 在请求的周期中执行最后的操作
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function terminate($request, $response)
    {
        if ($this->sessionHandled && $this->sessionConfigured() && ! $this->usingCookieSessions()) {
            $this->manager->driver()->save();
        }
    }

    /**
     * Start the session for the given request.
	 *
	 * 为给定的请求启动会话
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Session\Session
     */
    protected function startSession(Request $request)
    {
		//     用给定的值调用给定的闭包，然后返回值(从管理器获取会话实现)
        return tap($this->getSession($request), function ($session) use ($request) {
            $session->setRequestOnHandler($request);//在处理程序实例上设置请求,Illuminate\Session\Store

            $session->start();//启动会话，从处理程序读取数据,Illuminate\Session\Store
		});
    }

    /**
     * Get the session implementation from the manager.
	 *
	 * 从管理器获取会话实现
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Session\Session
     */
    public function getSession(Request $request)
    {
		//      用给定的值调用给定的闭包，然后返回值(获取驱动实例)
        return tap($this->manager->driver(), function ($session) use ($request) {
            $session->setId($request->cookies->get($session->getName()));
        });
    }

    /**
     * Remove the garbage from the session if necessary.
	 *
	 * 必要时删除会话中的垃圾
     *
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @return void
     */
    protected function collectGarbage(Session $session)
    {
        $config = $this->manager->getSessionConfig();

        // Here we will see if this request hits the garbage collection lottery by hitting
        // the odds needed to perform garbage collection on any given request. If we do
        // hit it, we'll call this handler to let it delete all the expired sessions.
        if ($this->configHitsLottery($config)) {
            $session->getHandler()->gc($this->getSessionLifetimeInSeconds());
        }
    }

    /**
     * Determine if the configuration odds hit the lottery.
     *
     * @param  array  $config
     * @return bool
     */
    protected function configHitsLottery(array $config)
    {
        return random_int(1, $config['lottery'][1]) <= $config['lottery'][0];
    }

    /**
     * Store the current URL for the request if necessary.
	 *
	 * 如果需要，存储请求的当前URL
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @return void
     */
    protected function storeCurrentUrl(Request $request, $session)
    {
        if ($request->method() === 'GET' && $request->route() && ! $request->ajax()) {
            $session->setPreviousUrl($request->fullUrl());
        }
    }

    /**
     * Add the session cookie to the application response.
	 *
	 * 将会话cookie添加到应用程序响应中
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @return void
     */
    protected function addCookieToResponse(Response $response, Session $session)
    {
        if ($this->usingCookieSessions()) {
            $this->manager->driver()->save();
        }
		//     确定配置的会话驱动程序是否持久()
        if ($this->sessionIsPersistent($config = $this->manager->getSessionConfig())) {
            $response->headers->setCookie(new Cookie(
                $session->getName(), $session->getId(), $this->getCookieExpirationDate(),
                $config['path'], $config['domain'], Arr::get($config, 'secure', false),
                Arr::get($config, 'http_only', true)
            ));
        }
    }

    /**
     * Get the session lifetime in seconds.
     *
     * @return int
     */
    protected function getSessionLifetimeInSeconds()
    {
        return Arr::get($this->manager->getSessionConfig(), 'lifetime') * 60;
    }

    /**
     * Get the cookie lifetime in seconds.
     *
     * @return \DateTimeInterface
     */
    protected function getCookieExpirationDate()
    {
        $config = $this->manager->getSessionConfig();

        return $config['expire_on_close'] ? 0 : Carbon::now()->addMinutes($config['lifetime']);
    }

    /**
     * Determine if a session driver has been configured.
	 *
	 * 确定会话驱动程序是否已配置
     *
     * @return bool
     */
    protected function sessionConfigured()
    {
		//                                       获取会话配置
        return ! is_null(Arr::get($this->manager->getSessionConfig(), 'driver'));
    }

    /**
     * Determine if the configured session driver is persistent.
	 *
	 * 确定配置的会话驱动程序是否持久
     *
     * @param  array|null  $config
     * @return bool
     */
    protected function sessionIsPersistent(array $config = null)
    {
        $config = $config ?: $this->manager->getSessionConfig();

        return ! in_array($config['driver'], [null, 'array']);
    }

    /**
     * Determine if the session is using cookie sessions.
     *
     * @return bool
     */
    protected function usingCookieSessions()
    {
        if ($this->sessionConfigured()) {
            return $this->manager->driver()->getHandler() instanceof CookieSessionHandler;
        }

        return false;
    }
}
