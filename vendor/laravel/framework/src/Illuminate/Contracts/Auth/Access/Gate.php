<?php

namespace Illuminate\Contracts\Auth\Access;

interface Gate
{
    /**
     * Determine if a given ability has been defined.
     *
     * 确定是否已经定义了给定的能力
     *
     * @param  string  $ability
     * @return bool
     */
    public function has($ability);

    /**
     * Define a new ability.
     *
     * 定义一个新的能力
     *
     * @param  string  $ability
     * @param  callable|string  $callback
     * @return $this
     */
    public function define($ability, $callback);

    /**
     * Define a policy class for a given class type.
     *
     * 定义一个给定类类型的策略类
     *
     * @param  string  $class
     * @param  string  $policy
     * @return $this
     */
    public function policy($class, $policy);

    /**
     * Register a callback to run before all Gate checks.
     *
     * 在所有的Gate检查之前注册一个回调来运行
     *
     * @param  callable  $callback
     * @return $this
     */
    public function before(callable $callback);

    /**
     * Register a callback to run after all Gate checks.
     *
     * 注册一个回调，以便在所有的Gate检查之后运行
     *
     * @param  callable  $callback
     * @return $this
     */
    public function after(callable $callback);

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * 确定当前用户是否应该授予给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function allows($ability, $arguments = []);

    /**
     * Determine if the given ability should be denied for the current user.
     *
     * 确定当前用户是否应该拒绝给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function denies($ability, $arguments = []);

    /**
     * Determine if the given ability should be granted.
     *
     * 确定是否给予给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function check($ability, $arguments = []);

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * 确定当前用户是否授予给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = []);

    /**
     * Get a policy instance for a given class.
     *
     * 获取给定类的策略实例
     *
     * @param  object|string  $class
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getPolicyFor($class);

    /**
     * Get a guard instance for the given user.
     *
     * 为给定用户获取保护实例
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @return static
     */
    public function forUser($user);
}
