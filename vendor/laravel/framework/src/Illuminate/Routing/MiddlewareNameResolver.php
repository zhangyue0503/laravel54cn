<?php

namespace Illuminate\Routing;

use Closure;

class MiddlewareNameResolver
{
    /**
     * Resolve the middleware name to a class name(s) preserving passed parameters.
     *
     * 将中间件名称解析为保存传递参数的类名
     *
     * @param  string  $name
     * @param  array  $map
     * @param  array  $middlewareGroups
     * @return string|array
     */
    public static function resolve($name, $map, $middlewareGroups)
    {
        // When the middleware is simply a Closure, we will return this Closure instance
        // directly so that Closures can be registered as middleware inline, which is
        // convenient on occasions when the developers are experimenting with them.
        //
        // 当中间件仅仅是一个闭包时，我们将直接返回这个闭包实例，这样闭包就可以作为内嵌的中间件注册，这在开发人员进行测试时是很方便的
        //
        if ($name instanceof Closure) {
            return $name;
        } elseif (isset($map[$name]) && $map[$name] instanceof Closure) {
            return $map[$name];

        // If the middleware is the name of a middleware group, we will return the array
        // of middlewares that belong to the group. This allows developers to group a
        // set of middleware under single keys that can be conveniently referenced.
            //
            // 如果中间件是中间件组的名称，我们将返回属于该组的中间件的数组。这允许开发人员将一组中间件分组到可以方便地引用的单个密钥中
            //
        } elseif (isset($middlewareGroups[$name])) {
            return static::parseMiddlewareGroup(//解析中间件组并将其格式化为使用
                $name, $map, $middlewareGroups
            );

        // Finally, when the middleware is simply a string mapped to a class name the
        // middleware name will get parsed into the full class name and parameters
        // which may be run using the Pipeline which accepts this string format.
            //
            // 最后，当中间件仅仅是一个映射到类名的字符串时，中间件名称将被解析为完整的类名和参数，这些名称和参数可以使用接受该字符串格式的管道来运行
            //
        } else {
            list($name, $parameters) = array_pad(explode(':', $name, 2), 2, null);

            return (isset($map[$name]) ? $map[$name] : $name).
                   (! is_null($parameters) ? ':'.$parameters : '');
        }
    }

    /**
     * Parse the middleware group and format it for usage.
     *
     * 解析中间件组并将其格式化为使用
     *
     * @param  string  $name
     * @param  array  $map
     * @param  array  $middlewareGroups
     * @return array
     */
    protected static function parseMiddlewareGroup($name, $map, $middlewareGroups)
    {
        $results = [];

        foreach ($middlewareGroups[$name] as $middleware) {
            // If the middleware is another middleware group we will pull in the group and
            // merge its middleware into the results. This allows groups to conveniently
            // reference other groups without needing to repeat all their middlewares.
            //
            // 如果中间件是另一个中间件组，我们将把它拉入组中，并将其中间件合并到结果中
            // 这使得组可以方便地引用其他组，而不需要重复所有的中间件
            //
            if (isset($middlewareGroups[$middleware])) {
                $results = array_merge($results, static::parseMiddlewareGroup(//解析中间件组并将其格式化为使用
                    $middleware, $map, $middlewareGroups
                ));

                continue;
            }

            list($middleware, $parameters) = array_pad(
                explode(':', $middleware, 2), 2, null
            );

            // If this middleware is actually a route middleware, we will extract the full
            // class name out of the middleware list now. Then we'll add the parameters
            // back onto this class' name so the pipeline will properly extract them.
            //
            // 如果这个中间件实际上是一个路由中间件，那么现在我们将从中间件列表中提取出完整的类名
            // 然后，我们将把参数添加到这个类的名称中，这样管道就可以正确地提取它们
            //
            if (isset($map[$middleware])) {
                $middleware = $map[$middleware];
            }

            $results[] = $middleware.($parameters ? ':'.$parameters : '');
        }

        return $results;
    }
}
