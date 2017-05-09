<?php

namespace Illuminate\Config;

use ArrayAccess;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class Repository implements ArrayAccess, ConfigContract
{
    /**
     * All of the configuration items.
     *
     * 所有配置项
     *
     * @var array
     */
    protected $items = [];

    /**
     * Create a new configuration repository.
	 *
	 * 创建一个新的配置库
     *
     * @param  array  $items
     * @return void
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * 确定给定的配置值是否存在
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        //使用“点”符号检查数组中的项或项是否存在
        return Arr::has($this->items, $key);
    }

    /**
     * Get the specified configuration value.
	 *
	 * 获取指定的配置值
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        //使用“点”符号从数组中获取一个项
        return Arr::get($this->items, $key, $default);
    }

    /**
     * Set a given configuration value.
	 *
	 * 设置给定的配置值
     *
     * @param  array|string  $key
     * @param  mixed   $value
     * @return void
     */
    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            //使用“点”符号将数组项设置为给定值
            Arr::set($this->items, $key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * 预先考虑值在数组配置值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend($key, $value)
    {
        //获取指定的配置值
        $array = $this->get($key);

        array_unshift($array, $value);
        //设置给定的配置值
        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * 将一个值推到一个数组配置值上
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        //获取指定的配置值
        $array = $this->get($key);

        $array[] = $value;
        //设置给定的配置值
        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * 获取应用程序的所有配置项
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        //确定给定的配置值是否存在
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * 获得一个配置选项
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        //获取指定的配置值
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * 设置一个配置选项
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        //设置给定的配置值
        $this->set($key, $value);
    }

    /**
     * Unset a configuration option.
     *
     * 取消配置选项
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        //设置给定的配置值
        $this->set($key, null);
    }
}
