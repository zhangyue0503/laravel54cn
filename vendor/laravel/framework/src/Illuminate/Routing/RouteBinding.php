<?php

namespace Illuminate\Routing;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RouteBinding
{
    /**
     * Create a Route model binding for a given callback.
     *
     * 为给定回调创建路由模型绑定
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Closure|string  $binder
     * @return \Closure
     */
    public static function forCallback($container, $binder)
    {
        if (is_string($binder)) {
            return static::createClassBinding($container, $binder); //使用IoC容器创建一个基于类的绑定使用
        }

        return $binder;
    }

    /**
     * Create a class based binding using the IoC container.
     *
     * 使用IoC容器创建一个基于类的绑定使用
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  string  $binding
     * @return \Closure
     */
    protected static function createClassBinding($container, $binding)
    {
        return function ($value, $route) use ($container, $binding) {
            // If the binding has an @ sign, we will assume it's being used to delimit
            // the class name from the bind method name. This allows for bindings
            // to run multiple bind methods in a single class for convenience.
            //
            // 如果绑定有@符号，我们将假定它用于从绑定方法名中定义类名
            // 这允许绑定在单个类中运行多个绑定方法以方便使用
            //
            list($class, $method) = Str::parseCallback($binding, 'bind');// 解析 类@方法 类型回调到类和方法
            //             从容器中解析给定类型
            $callable = [$container->make($class), $method];

            return call_user_func($callable, $value, $route);
        };
    }

    /**
     * Create a Route model binding for a model.
     *
     * 为模型创建路由模型绑定
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  string  $class
     * @param  \Closure|null  $callback
     * @return \Closure
     */
    public static function forModel($container, $class, $callback = null)
    {
        return function ($value) use ($container, $class, $callback) {
            if (is_null($value)) {
                return;
            }

            // For model binders, we will attempt to retrieve the models using the first
            // method on the model instance. If we cannot retrieve the models we'll
            // throw a not found exception otherwise we will return the instance.
            //
            // 模型粘合剂，我们将尝试检索模型的第一个方法的模型实例
            // 如果我们不能检索模型，我们将抛出一个没有发现异常，否则我们将返回实例
            //
            $instance = $container->make($class);//从容器中解析给定类型
            //   Illuminate\Database\Eloquent\Model::where(从模型中获取路由键值(主键),$value)->第一条结果
            if ($model = $instance->where($instance->getRouteKeyName(), $value)->first()) {
                return $model;
            }

            // If a callback was supplied to the method we will call that to determine
            // what we should do when the model is not found. This just gives these
            // developer a little greater flexibility to decide what will happen.
            //
            // 如果给该方法提供了回调，我们将调用该方法以确定在没有找到该模型时该做什么
            // 这只是给这些开发者一个更大的灵活性来决定会发生什么
            //
            if ($callback instanceof Closure) {
                return call_user_func($callback, $value);
            }

            throw (new ModelNotFoundException)->setModel($class);  //抛出错误  设置受影响的Eloquent型和实例ids
        };
    }
}
