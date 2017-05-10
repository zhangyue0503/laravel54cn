<?php

namespace Illuminate\Contracts\Cache;

use Closure;

interface Repository
{
    /**
     * Determine if an item exists in the cache.
     *
     * 确定缓存中是否存在某个项
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key);

    /**
     * Retrieve an item from the cache by key.
     *
     * 通过键从缓存中检索一个项
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Retrieve an item from the cache and delete it.
     *
     * 从缓存中检索一个条目并删除它
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function pull($key, $default = null);

    /**
     * Store an item in the cache.
     *
     * 在缓存中存储一个项
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  \DateTime|float|int  $minutes
     * @return void
     */
    public function put($key, $value, $minutes);

    /**
     * Store an item in the cache if the key does not exist.
     *
     * 如果键不存在，则在缓存中存储一个项
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  \DateTime|float|int  $minutes
     * @return bool
     */
    public function add($key, $value, $minutes);

    /**
     * Increment the value of an item in the cache.
     *
     * 增加缓存中的项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1);

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1);

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value);

    /**
     * Get an item from the cache, or store the default value.
     *
     * @param  string  $key
     * @param  \DateTime|float|int  $minutes
     * @param  \Closure  $callback
     * @return mixed
     */
    public function remember($key, $minutes, Closure $callback);

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param  string   $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function sear($key, Closure $callback);

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * 从缓存中获取一个条目，或者永久存储默认值
     *
     * @param  string   $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function rememberForever($key, Closure $callback);

    /**
     * Remove an item from the cache.
     *
     * 从缓存中删除一个项目
     *
     * @param  string $key
     * @return bool
     */
    public function forget($key);
}
