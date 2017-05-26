<?php

namespace Illuminate\Routing;

use LogicException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use UnexpectedValueException;

class RouteAction
{
    /**
     * Parse the given action into an array.
     *
     * 将给定操作解析为数组
     *
     * @param  string  $uri
     * @param  mixed  $action
     * @return array
     */
    public static function parse($uri, $action)
    {
        // If no action is passed in right away, we assume the user will make use of
        // fluent routing. In that case, we set a default closure, to be executed
        // if the user never explicitly sets an action to handle the given uri.
        //
        // 如果没有正确的路由通过，我们假设用户将使用流畅的路由。在这种情况下，我们设置默认闭包，如果用户从未显式地设置一个操作来处理给定的URI，则将被执行。
        //
        if (is_null($action)) {
            return static::missingAction($uri); //获取没有动作的路由的动作
        }

        // If the action is already a Closure instance, we will just set that instance
        // as the "uses" property, because there is nothing else we need to do when
        // it is available. Otherwise we will need to find it in the action list.
        //
        // 如果该操作已经是闭包实例，我们将只将该实例设置为“uses”属性，因为当它可用时，我们不需要做其他任何事情。否则我们将需要在行动列表中找到它。
        //
        if (is_callable($action)) {
            return ['uses' => $action];
        }

        // If no "uses" property has been set, we will dig through the array to find a
        // Closure instance within this list. We will set the first Closure we come
        // across into the "uses" property that will get fired off by this route.
        //
        // 如果没有设置“uses”属性，我们将通过数组来查找列表中的闭包实例。我们将设置第一个关闭，我们遇到的“use”属性将被解雇了这条路线。
        //
        elseif (! isset($action['uses'])) {
            $action['uses'] = static::findCallable($action); //在动作数组中找到可调用的
        }

        if (is_string($action['uses']) && ! Str::contains($action['uses'], '@')) { //uses属性是字符串，并且包含@符
            $action['uses'] = static::makeInvokable($action['uses']); // 创建一个调用控制器动作
        }

        return $action;
    }

    /**
     * Get an action for a route that has no action.
     *
     * 获取没有动作的路由的动作
     *
     * @param  string  $uri
     * @return array
     */
    protected static function missingAction($uri)
    {
        return ['uses' => function () use ($uri) {
            throw new LogicException("Route for [{$uri}] has no action.");
        }];
    }

    /**
     * Find the callable in an action array.
     *
     * 在动作数组中找到可调用的
     *
     * @param  array  $action
     * @return callable
     */
    protected static function findCallable(array $action)
    {
        //通过给定的真值测试返回数组中的第一个元素
        return Arr::first($action, function ($value, $key) {
            return is_callable($value) && is_numeric($key);
        });
    }

    /**
     * Make an action for an invokable controller.
     *
     * 创建一个调用控制器动作
     *
     * @param  string $action
     * @return string
     */
    protected static function makeInvokable($action)
    {
        if (! method_exists($action, '__invoke')) {
            throw new UnexpectedValueException("Invalid route action: [{$action}].");
        }

        return $action.'@__invoke';
    }
}
