<?php

namespace Illuminate\Routing;

use Illuminate\Database\Eloquent\Model;

class ImplicitRouteBinding
{
    /**
     * Resolve the implicit route bindings for the given route.
     *
     * 解决给定路由的隐式路由绑定
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Illuminate\Routing\Route  $route
     * @return void
     */
    public static function resolveForRoute($container, $route)
    {
        //              获取路由参数的键/值列表
        $parameters = $route->parameters();
        //               获取路由/控制器签名中列出的参数
        foreach ($route->signatureParameters(Model::class) as $parameter) {
            $class = $parameter->getClass();

            if (array_key_exists($parameter->name, $parameters) &&
                ! $route->parameter($parameter->name) instanceof Model) {
                //                   param有默认值吗?
                $method = $parameter->isDefaultValueAvailable() ? 'first' : 'firstOrFail';
                //              从容器中解析给定类型
                $model = $container->make($class->name);
                //为给定值设置参数
                $route->setParameter(
                    $parameter->name, $model->where(
                        $model->getRouteKeyName(), $parameters[$parameter->name]
                    )->{$method}()
                );
            }
        }
    }
}
