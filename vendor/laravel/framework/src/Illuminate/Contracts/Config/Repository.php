<?php

namespace Illuminate\Contracts\Config;

interface Repository
{
    /**
     * Determine if the given configuration value exists.
     *
     * 确定给定的配置值是否存在
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key);

    /**
     * Get the specified configuration value.
     *
     * 获得指定的配置值
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Get all of the configuration items for the application.
     *
     * 获取应用程序的所有配置项
     *
     * @return array
     */
    public function all();

    /**
     * Set a given configuration value.
	 *
	 * 设置给定的配置值
     *
     * @param  array|string  $key
     * @param  mixed   $value
     * @return void
     */
    public function set($key, $value = null);

    /**
     * Prepend a value onto an array configuration value.
     *
     * 预先考虑值在数组配置值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend($key, $value);

    /**
     * Push a value onto an array configuration value.
     *
     * 将一个值推到一个数组配置值上
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value);
}
