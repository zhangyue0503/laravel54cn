<?php

namespace Illuminate\Routing\Exceptions;

use Exception;

class UrlGenerationException extends Exception
{
    /**
     * Create a new exception for missing route parameters.
     *
     * 创建缺少路由参数的新异常
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return static
     */
    public static function forMissingParameters($route)
    {
        //                                                      获取路由实例的名称                  获取与路由关联的URI
        return new static("Missing required parameters for [Route: {$route->getName()}] [URI: {$route->uri()}].");
    }
}
