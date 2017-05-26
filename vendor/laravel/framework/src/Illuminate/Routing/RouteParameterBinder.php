<?php

namespace Illuminate\Routing;

use Illuminate\Support\Arr;

class RouteParameterBinder
{
    /**
     * The route instance.
     *
     * 路由实例
     *
     * @var \Illuminate\Routing\Route
     */
    protected $route;

    /**
     * Create a new Route parameter binder instance.
     *
     * 创建一个新的路由参数绑定实例
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    public function __construct($route)
    {
        $this->route = $route;
    }

    /**
     * Get the parameters for the route.
     *
     * 获取路由参数
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function parameters($request)
    {
        // If the route has a regular expression for the host part of the URI, we will
        // compile that and get the parameter matches for this domain. We will then
        // merge them into this parameters array so that this array is completed.
        //
        // 如果该路由对URI的宿主部分有正则表达式，我们将编译并获取该域的参数匹配。然后，我们将它们合并到这个参数数组中，这样数组就完成了。
        //
        $parameters = $this->bindPathParameters($request);  //获取URI的路径部分的参数匹配

        // If the route has a regular expression for the host part of the URI, we will
        // compile that and get the parameter matches for this domain. We will then
        // merge them into this parameters array so that this array is completed.
        //
        // 如果该路由对URI的宿主部分有正则表达式，我们将编译并获取该域的参数匹配。然后，我们将它们合并到这个参数数组中，这样数组就完成了。
        //
        if (! is_null($this->route->compiled->getHostRegex())) { //如果域的正则表达式不为空
            $parameters = $this->bindHostParameters(  // 从请求的主机部分提取参数列表
                $request, $parameters
            );
        }
        // 返回 用默认值替换空参数 的 $parameters
        return $this->replaceDefaults($parameters);
    }

    /**
     * Get the parameter matches for the path portion of the URI.
     *
     * 获取URI的路径部分的参数匹配
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function bindPathParameters($request)
    {
        //                           返回正则表达式               请求的当前编码路径信息
        preg_match($this->route->compiled->getRegex(), '/'.$request->decodedPath(), $matches);
        // 返回 结合一组参数匹配与路由的键，$matches第二个以后的数组
        return $this->matchToKeys(array_slice($matches, 1));
    }

    /**
     * Extract the parameter list from the host part of the request.
     *
     * 从请求的主机部分提取参数列表
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $parameters
     * @return array
     */
    protected function bindHostParameters($request, $parameters)
    {
        //                    域的正则表达式                       主机名
        preg_match($this->route->compiled->getHostRegex(), $request->getHost(), $matches);
        // 返回              结合一组参数匹配与路由的键，$matches第二个以后的数组 与$parameters合并后返回
        return array_merge($this->matchToKeys(array_slice($matches, 1)), $parameters);
    }

    /**
     * Combine a set of parameter matches with the route's keys.
     *
     * 结合一组参数匹配与路由的键
     *
     * @param  array  $matches
     * @return array
     */
    protected function matchToKeys(array $matches)
    {
        //路由的所有参数名称为空则返回空数组
        if (empty($parameterNames = $this->route->parameterNames())) {
            return [];
        }
        //              比较两个数组的键名，并返回交集，$matches，array_flip($parameterNames)反转数组中所有的键以及它们关联的值
        $parameters = array_intersect_key($matches, array_flip($parameterNames));
        // 返回 字符串并且长度大于0的数组
        return array_filter($parameters, function ($value) {
            return is_string($value) && strlen($value) > 0;
        });
    }

    /**
     * Replace null parameters with their defaults.
     *
     * 用默认值替换空参数
     *
     * @param  array  $parameters
     * @return array
     */
    protected function replaceDefaults(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            //                                            使用“点”符号从数组中获取一个项
            $parameters[$key] = isset($value) ? $value : Arr::get($this->route->defaults, $key);
        }

        foreach ($this->route->defaults as $key => $value) {
            if (! isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}
