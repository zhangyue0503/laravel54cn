<?php

namespace Illuminate\Routing;

use ReflectionMethod;
use ReflectionFunction;
use Illuminate\Support\Str;

class RouteSignatureParameters
{
    /**
     * Extract the route action's signature parameters.
     *
     * 提取路由动作的签名参数
     *
     * @param  array  $action
     * @param  string  $subClass
     * @return array
     */
    public static function fromAction(array $action, $subClass = null)
    {
        //如果$action['uses']是字符串，返回 用字符串获取给定类/方法的参数 否则建立反射获取$action['uses']类的参数
        $parameters = is_string($action['uses'])
                        ? static::fromClassMethodString($action['uses'])
                        : (new ReflectionFunction($action['uses']))->getParameters();
        //如果subClass是null，返回参数，否则过滤数组，返回参数的类继承自subClass的参数
        return is_null($subClass) ? $parameters : array_filter($parameters, function ($p) use ($subClass) {
            return $p->getClass() && $p->getClass()->isSubclassOf($subClass);
        });
    }

    /**
     * Get the parameters for the given class / method by string.
     *
     * 用字符串获取给定类/方法的参数
     *
     * @param  string  $uses
     * @return array
     */
    protected static function fromClassMethodString($uses)
    {
        list($class, $method) = Str::parseCallback($uses); //解析 类@方法 类型回调到类和方法
        //建立反射获取$uses的参数列表
        return (new ReflectionMethod($class, $method))->getParameters();
    }
}
