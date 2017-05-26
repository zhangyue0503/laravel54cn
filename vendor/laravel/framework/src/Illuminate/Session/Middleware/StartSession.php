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
     * 会话管理器
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $manager;

    /**
     * Indicates if the session was handled for the current request.
     *
     * 表示如果会话是针对当前请求处理的
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
        //                                 确定会话驱动程序是否已配置          确定会话是否使用cookie会话
        if ($this->sessionHandled && $this->sessionConfigured() && ! $this->usingCookieSessions()) {
            //            获取驱动实例
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
        $config = $this->manager->getSessionConfig();//获取会话配置

        // Here we will see if this request hits the garbage collection lottery by hitting
        // the odds needed to perform garbage collection on any given request. If we do
        // hit it, we'll call this handler to let it delete all the expired sessions.
        //
        // 在这里，我们将看到这个请求是否通过点击在任何给定请求上执行垃圾收集所需的概率来达到垃圾收集彩票
        // 如果我们点击它，我们将调用这个处理程序，让它删除所有过期的会话
        //
        //          确定配置中是否有彩票中奖
        if ($this->configHitsLottery($config)) {
            //获取会话处理程序实例                获得会话生命周期数秒
            $session->getHandler()->gc($this->getSessionLifetimeInSeconds());
        }
    }

    /**
     * Determine if the configuration odds hit the lottery.
     *
     * 确定配置中是否有彩票中奖
     *
     * @param  array  $config
     * @return bool
     */
    protected function configHitsLottery(array $config)
    {
        //在$min和$max之间获取一个随机整数
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
        //获取请求的方法                     获取路由处理请求             确定请求是否是Ajax调用的结果
        if ($request->method() === 'GET' && $request->route() && ! $request->ajax()) {
            //在会话中设置“之前”的URL              从请求获取完整的URL
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
        //确定会话是否使用cookie会话
        if ($this->usingCookieSessions()) {
            //          获取驱动实例
            $this->manager->driver()->save();
        }
		//     确定配置的会话驱动程序是否持久()                       获取会话配置
        if ($this->sessionIsPersistent($config = $this->manager->getSessionConfig())) {
            //                 设置cookie
            $response->headers->setCookie(new Cookie(
                //获得会话的名称             获取当前会话ID            用秒来获取cookie的生命周期
                $session->getName(), $session->getId(), $this->getCookieExpirationDate(),
            //                                        使用“点”符号从数组中获取一个项
                $config['path'], $config['domain'], Arr::get($config, 'secure', false),
                Arr::get($config, 'http_only', true)
            ));
        }
    }

    /**
     * Get the session lifetime in seconds.
     *
     * 获得会话生命周期数秒
     *
     * @return int
     */
    protected function getSessionLifetimeInSeconds()
    {
        //使用“点”符号从数组中获取一个项       获取会话配置
        return Arr::get($this->manager->getSessionConfig(), 'lifetime') * 60;
    }

    /**
     * Get the cookie lifetime in seconds.
     *
     * 用秒来获取cookie的生命周期
     *
     * @return \DateTimeInterface
     */
    protected function getCookieExpirationDate()
    {
        $config = $this->manager->getSessionConfig();//获取会话配置
        //                                    获取当前日期和时间的Carbon实例  添加几分钟到实例
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
		//           使用“点”符号从数组中获取一个项          获取会话配置
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
        $config = $config ?: $this->manager->getSessionConfig();//获取会话配置

        return ! in_array($config['driver'], [null, 'array']);
    }

    /**
     * Determine if the session is using cookie sessions.
     *
     * 确定会话是否使用cookie会话
     *
     * @return bool
     */
    protected function usingCookieSessions()
    {
        //确定会话驱动程序是否已配置
        if ($this->sessionConfigured()) {
            //               获取驱动实例      获取会话处理程序实例
            return $this->manager->driver()->getHandler() instanceof CookieSessionHandler;
        }

        return false;
    }
}
