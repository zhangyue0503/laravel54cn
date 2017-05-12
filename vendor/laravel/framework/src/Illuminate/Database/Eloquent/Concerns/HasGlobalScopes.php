<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Closure;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Scope;

trait HasGlobalScopes
{
    /**
     * Register a new global scope on the model.
     *
     * 在模型上注册一个新的全局范围
     *
     * @param  \Illuminate\Database\Eloquent\Scope|\Closure|string  $scope
     * @param  \Closure|null  $implementation
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function addGlobalScope($scope, Closure $implementation = null)
    {
        if (is_string($scope) && ! is_null($implementation)) {
            return static::$globalScopes[static::class][$scope] = $implementation;
        } elseif ($scope instanceof Closure) {
            return static::$globalScopes[static::class][spl_object_hash($scope)] = $scope;
        } elseif ($scope instanceof Scope) {
            return static::$globalScopes[static::class][get_class($scope)] = $scope;
        }

        throw new InvalidArgumentException('Global scope must be an instance of Closure or Scope.');
    }

    /**
     * Determine if a model has a global scope.
     *
     * 确定一个模型是否具有全局范围
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return bool
     */
    public static function hasGlobalScope($scope)
    {
        //                  获得一个在模型中注册的全局范围
        return ! is_null(static::getGlobalScope($scope));
    }

    /**
     * Get a global scope registered with the model.
     *
     * 获得一个在模型中注册的全局范围
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return \Illuminate\Database\Eloquent\Scope|\Closure|null
     */
    public static function getGlobalScope($scope)
    {
        if (is_string($scope)) {
            //使用“点”符号从数组中获取一个项
            return Arr::get(static::$globalScopes, static::class.'.'.$scope);
        }

        return Arr::get(
            static::$globalScopes, static::class.'.'.get_class($scope)
        );
    }

    /**
     * Get the global scopes for this class instance.
     *
     * 获取此类实例的全局作用域
     *
     * @return array
     */
    public function getGlobalScopes()
    {
        //使用“点”符号从数组中获取一个项
        return Arr::get(static::$globalScopes, static::class, []);
    }
}
