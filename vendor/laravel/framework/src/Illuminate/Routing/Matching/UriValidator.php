<?php

namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class UriValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
     *
     * 根据路由和请求验证给定的规则
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        //获取请求的当前路径信息
        $path = $request->path() == '/' ? '/' : '/'.$request->path();
        //               获取编译后的路由版本    返回域的正则表达式
        return preg_match($route->getCompiled()->getRegex(), rawurldecode($path));
    }
}
