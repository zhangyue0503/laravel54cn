<?php

namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class SchemeValidator implements ValidatorInterface
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
        //确定路由是否只响应HTTP请求
        if ($route->httpOnly()) {
            return ! $request->secure();//确定请求是否是HTTPS
        } elseif ($route->secure()) {
            return $request->secure();
        }

        return true;
    }
}
