<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequests
{
    /**
     * The rate limiter instance.
     *
     * 速度限制器实例
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new request throttler.
     *
     * 创建一个新的请求节流器
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * 处理传入的请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $maxAttempts
     * @param  float|int  $decayMinutes
     * @return mixed
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        //             解析请求签名
        $key = $this->resolveRequestSignature($request);
        //                  确定给定的键是否被“访问”过多次
        if ($this->limiter->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            //            创建一个“太多尝试”的响应
            return $this->buildResponse($key, $maxAttempts);
        }
        //为给定的衰减时间增加给定键的计数器
        $this->limiter->hit($key, $decayMinutes);

        $response = $next($request);
        //将限制头信息添加到给定的响应中
        return $this->addHeaders(
            $response, $maxAttempts,
        //         计算剩余尝试的次数
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature.
     *
     * 解析请求签名
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature($request)
    {
        //获取请求/路由/ IP地址的唯一指纹
        return $request->fingerprint();
    }

    /**
     * Create a 'too many attempts' response.
     *
     * 创建一个“太多尝试”的响应
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildResponse($key, $maxAttempts)
    {
        $response = new Response('Too Many Attempts.', 429);
        //                   获得秒数，直到“键”再次访问
        $retryAfter = $this->limiter->availableIn($key);
        //将限制头信息添加到给定的响应中
        return $this->addHeaders(
            $response, $maxAttempts,
        //          计算剩余尝试的次数
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );
    }

    /**
     * Add the limit header information to the given response.
     *
     * 将限制头信息添加到给定的响应中
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @param  int|null  $retryAfter
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addHeaders(Response $response, $maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ];

        if (! is_null($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
            //                           获取当前日期和时间的Carbon实例
            $headers['X-RateLimit-Reset'] = Carbon::now()->getTimestamp() + $retryAfter;
        }
        //              添加新标头当前到HTTP标头数组
        $response->headers->add($headers);

        return $response;
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * 计算剩余尝试的次数
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int|null  $retryAfter
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null)
    {
        if (is_null($retryAfter)) {
            //                  为给定的键获取剩余的重试次数
            return $this->limiter->retriesLeft($key, $maxAttempts);
        }

        return 0;
    }
}
