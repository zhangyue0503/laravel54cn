<?php

namespace Illuminate\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response;

class CheckResponseForModifications
{
    /**
     * Handle an incoming request.
     *
     * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof Response) {
            //决定是否响应验证器（ETag，Last-Modified）匹配请求中指定的条件值
            $response->isNotModified($request);
        }

        return $response;
    }
}
