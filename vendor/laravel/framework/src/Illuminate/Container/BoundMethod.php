<?php

namespace Illuminate\Container;

use Closure;
use ReflectionMethod;
use ReflectionFunction;
use InvalidArgumentException;

class BoundMethod
{
    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * 调用给定的闭包/类@方法并注入它的依赖项
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     */
    public static function call($container, $callback, array $parameters = [], $defaultMethod = null)
    {
        if (static::isCallableWithAtSign($callback) || $defaultMethod) { // 确定给定的字符串是否是在类中@方法语法
            return static::callClass($container, $callback, $parameters, $defaultMethod); // 使用类@方法语法调用类的字符串引用
        }

        // 调用绑定到容器的方法
        return static::callBoundMethod($container, $callback, function () use ($container, $callback, $parameters) {
            return call_user_func_array(
                $callback, static::getMethodDependencies($container, $callback, $parameters) // 获取给定方法的所有依赖项
            );
        });
    }

    /**
     * Call a string reference to a class using Class@method syntax.
     *
     * 使用类@方法语法调用类的字符串引用
     *
     * @param  \Illuminate\Container\Container  $container 容器
     * @param  string  $target 目标
     * @param  array  $parameters 参数
     * @param  string|null  $defaultMethod 默认方法
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected static function callClass($container, $target, array $parameters = [], $defaultMethod = null)
    {
        $segments = explode('@', $target);

        // We will assume an @ sign is used to delimit the class name from the method
        // name. We will split on this @ sign and then build a callable array that
        // we can pass right back into the "call" method for dependency binding.
        //
        // 我们将假设一个@符号用于从方法名中定义类名。我们将在这个@符号上拆分，然后生成一个可调用数组，我们可以将它直接传递给依赖绑定的“调用”方法
        //
        $method = count($segments) == 2
                        ? $segments[1] : $defaultMethod;

        if (is_null($method)) { //如果$method为空，抛出错误
            throw new InvalidArgumentException('Method not provided.');
        }
        //调用给定的闭包/类@方法并注入它的依赖项
        return static::call(
            $container, [$container->make($segments[0]), $method], $parameters
        );
    }

    /**
     * Call a method that has been bound to the container.
     *
     * 调用绑定到容器的方法
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  callable  $callback
     * @param  mixed  $default
     * @return mixed
     */
    protected static function callBoundMethod($container, $callback, $default)
    {
        if (! is_array($callback)) { // 如果$calback是不数组
            return $default instanceof Closure ? $default() : $default; //返回，如果$default是闭包返回方法，否则直接返回
        }

        // Here we need to turn the array callable into a Class@method string we can use to
        // examine the container and see if there are any method bindings for this given
        // method. If there are, we can call this method binding callback immediately.
        //
        // 在这里，我们需要将数组调用成一个类@方法字符串，我们可以用来检查容器，看看是否有任何方法绑定此给定的方法。如果有，我们可以调用此方法绑定立即回调
        //
        $method = static::normalizeMethod($callback); // 将给定回调标准化为类@方法字符串

        if ($container->hasMethodBinding($method)) { // 确定容器是否有方法绑定
            return $container->callMethodBinding($method, $callback[0]); // 获取给定方法的方法绑定
        }

        return $default instanceof Closure ? $default() : $default; //返回，如果$default是闭包返回方法，否则直接返回
    }

    /**
     * Normalize the given callback into a Class@method string.
     *
     * 将给定回调标准化为类@方法字符串
     *
     * @param  callable  $callback
     * @return string
     */
    protected static function normalizeMethod($callback)
    {
        $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]); //获取类名

        return "{$class}@{$callback[1]}"; // 返回类名@方法名
    }

    /**
     * Get all dependencies for a given method.
     *
     * 获取给定方法的所有依赖项
     *
     * @param  \Illuminate\Container\Container
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @return array
     */
    protected static function getMethodDependencies($container, $callback, array $parameters = [])
    {
        $dependencies = []; // 依赖数组

        //获取给定回调的适当的反射实例参数数组并循环
        foreach (static::getCallReflector($callback)->getParameters() as $parameter) {
            static::addDependencyForCallParameter($container, $parameter, $parameters, $dependencies); // 获取给定调用参数的依赖关系
        }
        // 返回合并依赖数组和参数数组
        return array_merge($dependencies, $parameters);
    }

    /**
     * Get the proper reflection instance for the given callback.
     *
     * 获取给定回调的适当的反射实例
     *
     * @param  callable|string  $callback
     * @return \ReflectionFunctionAbstract
     */
    protected static function getCallReflector($callback)
    {
        if (is_string($callback) && strpos($callback, '::') !== false) { // 如果$callback是字符串并且包含::符号
            $callback = explode('::', $callback); //按::分隔
        }
        // 返回，如果$callback是数组，返回该类下的方法，否则直接返回$callback的反射对象
        return is_array($callback)
                        ? new ReflectionMethod($callback[0], $callback[1])
                        : new ReflectionFunction($callback);
    }

    /**
     * Get the dependency for the given call parameter.
     *
     * 获取给定调用参数的依赖关系
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \ReflectionParameter  $parameter
     * @param  array  $parameters
     * @param  array  $dependencies
     * @return mixed
     */
    protected static function addDependencyForCallParameter($container, $parameter,
                                                            array &$parameters, &$dependencies)
    {
        if (array_key_exists($parameter->name, $parameters)) { //如果$parameter参数名称在$parametes数组中存在，依赖数组添加
            $dependencies[] = $parameters[$parameter->name];

            unset($parameters[$parameter->name]);
        } elseif ($parameter->getClass()) { // 如果$parameter是个类，依赖数组添加从容器中解析给定的类名
            $dependencies[] = $container->make($parameter->getClass()->name);
        } elseif ($parameter->isDefaultValueAvailable()) { // 如果$parametr有默认值，依赖数组添加这个参数的默认值
            $dependencies[] = $parameter->getDefaultValue();
        }
    }

    /**
     * Determine if the given string is in Class@method syntax.
     *
     * 确定给定的字符串是否是在类中@方法语法
     *
     * @param  mixed  $callback
     * @return bool
     */
    protected static function isCallableWithAtSign($callback)
    {
        //$callback是字符串并且包含@符号
        return is_string($callback) && strpos($callback, '@') !== false;
    }
}
