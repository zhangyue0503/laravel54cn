<?php

namespace Illuminate\Foundation\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;
//授权请求
trait AuthorizesRequests
{
    /**
     * Authorize a given action for the current user.
     *
     * 授权当前用户的给定操作
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = [])
    {
        //                                  猜测能力的名称，如果它没有提供
        list($ability, $arguments) = $this->parseAbilityAndArguments($ability, $arguments);
        //                      确定当前用户是否授予给定的能力
        return app(Gate::class)->authorize($ability, $arguments);
    }

    /**
     * Authorize a given action for a user.
     *
     * 为用户授权给定的动作
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeForUser($user, $ability, $arguments = [])
    {
        //                                  猜测能力的名称，如果它没有提供
        list($ability, $arguments) = $this->parseAbilityAndArguments($ability, $arguments);
        //                     为给定用户获取保护实例   确定当前用户是否授予给定的能力
        return app(Gate::class)->forUser($user)->authorize($ability, $arguments);
    }

    /**
     * Guesses the ability's name if it wasn't provided.
     *
     * 猜测能力的名称，如果它没有提供
     *
     * @param  mixed  $ability
     * @param  mixed|array  $arguments
     * @return array
     */
    protected function parseAbilityAndArguments($ability, $arguments)
    {
        if (is_string($ability) && strpos($ability, '\\') === false) {
            return [$ability, $arguments];
        }

        $method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['function'];

        //             规范从方法名称中猜出的能力名称
        return [$this->normalizeGuessedAbilityName($method), $ability];
    }

    /**
     * Normalize the ability name that has been guessed from the method name.
     *
     * 规范从方法名称中猜出的能力名称
     *
     * @param  string  $ability
     * @return string
     */
    protected function normalizeGuessedAbilityName($ability)
    {
        //          获取资源方法到能力名称的映射
        $map = $this->resourceAbilityMap();

        return isset($map[$ability]) ? $map[$ability] : $ability;
    }

    /**
     * Authorize a resource action based on the incoming request.
     *
     * 基于传入请求授权资源操作
     *
     * @param  string  $model
     * @param  string|null  $parameter
     * @param  array  $options
     * @param  \Illuminate\Http\Request|null  $request
     * @return void
     */
    public function authorizeResource($model, $parameter = null, array $options = [], $request = null)
    {
        $parameter = $parameter ?: strtolower(class_basename($model));

        $middleware = [];

        //          获取资源方法到能力名称的映射
        foreach ($this->resourceAbilityMap() as $method => $ability) {
            $modelName = in_array($method, ['index', 'create', 'store']) ? $model : $parameter;

            $middleware["can:{$ability},{$modelName}"][] = $method;
        }

        foreach ($middleware as $middlewareName => $methods) {
            //   注册控制器上的中间件                        设置中间件的控制器方法应适用于
            $this->middleware($middlewareName, $options)->only($methods);
        }
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * 获取资源方法到能力名称的映射
     *
     * @return array
     */
    protected function resourceAbilityMap()
    {
        return [
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ];
    }
}
