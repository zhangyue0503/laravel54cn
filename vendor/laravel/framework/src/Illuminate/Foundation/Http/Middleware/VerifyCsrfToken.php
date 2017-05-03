<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Foundation\Application;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken
{
    /**
     * The application instance.
     *
     * 应用程序实例
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The encrypter implementation.
     *
     * 加密的实现
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * 应该从CSRF验证中排除的URIs
     *
     * @var array
     */
    protected $except = [];

    /**
     * Create a new middleware instance.
     *
     * 创建一个新的中间件实例
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @return void
     */
    public function __construct(Application $app, Encrypter $encrypter)
    {
        $this->app = $app;
        $this->encrypter = $encrypter;
    }

    /**
     * Handle an incoming request.
     *
     * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        if (
            $this->isReading($request) ||//确定HTTP请求是否使用“读”谓词
            $this->runningUnitTests() ||//确定应用程序是否正在运行单元测试
            $this->inExceptArray($request) ||//确定请求是否有一个应该通过CSRF验证的URI
            $this->tokensMatch($request)//确定会话和输入CSRF令牌是否匹配
        ) {
            return $this->addCookieToResponse($request, $next($request));//将CSRF令牌添加到响应cookie中
        }

        throw new TokenMismatchException;
    }

    /**
     * Determine if the HTTP request uses a ‘read’ verb.
     *
     * 确定HTTP请求是否使用“读”谓词
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isReading($request)
    {
        //获取请求的方法
        return in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']);
    }

    /**
     * Determine if the application is running unit tests.
     *
     * 确定应用程序是否正在运行单元测试
     *
     * @return bool
     */
    protected function runningUnitTests()
    {
        //确定我们是否在控制台中运行                     确定我们是否在单元测试中运行
        return $this->app->runningInConsole() && $this->app->runningUnitTests();
    }

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     *
     * 确定请求是否有一个应该通过CSRF验证的URI
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }
            //确定当前请求URI是否与模式匹配
            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * 确定会话和输入CSRF令牌是否匹配
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        //从请求中获取CSRF令牌
        $token = $this->getTokenFromRequest($request);
        //                   获取与请求关联的会话  得到的CSRF令牌值
        return is_string($request->session()->token()) &&
               is_string($token) &&
               //             获取与请求关联的会话  得到的CSRF令牌值
               hash_equals($request->session()->token(), $token);
    }

    /**
     * Get the CSRF token from the request.
     *
     * 从请求中获取CSRF令牌
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getTokenFromRequest($request)
    {
        //              从请求中检索输入项          从请求中检索一个头部
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');
        //                                从请求中检索一个头部
        if (! $token && $header = $request->header('X-XSRF-TOKEN')) {
            $token = $this->encrypter->decrypt($header);//对给定值进行解密
        }

        return $token;
    }

    /**
     * Add the CSRF token to the response cookies.
     *
     * 将CSRF令牌添加到响应cookie中
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addCookieToResponse($request, $response)
    {
        $config = config('session');
        //                 设置cookie
        $response->headers->setCookie(
            new Cookie(
                //                  获取与请求关联的会话  得到的CSRF令牌值 获取当前日期和时间的Carbon实例
                'XSRF-TOKEN', $request->session()->token(), Carbon::now()->getTimestamp() + 60 * $config['lifetime'],
                $config['path'], $config['domain'], $config['secure'], false
            )
        );

        return $response;
    }
}
