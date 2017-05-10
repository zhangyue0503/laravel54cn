<?php

namespace Illuminate\Contracts\Cache;

interface Store
{
    /**
     * Retrieve an item from the cache by key.
     *
     * 通过键从缓存中检索一个项
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key);

    /**
     * Retrieve multiple items from the cache by key.
     *
     * 通过键从缓存中检索多个项
     *
     * Items not found in the cache will have a null value.
     *
     * 在缓存中未找到的项将具有空值
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys);

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * 在缓存中存储一个条目，在给定的时间内
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  float|int  $minutes
     * @return void
     */
    public function put($key, $value, $minutes);

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * 将多个项目存储在缓存中，在给定的时间内
     *
     * @param  array  $values
     * @param  float|int  $minutes
     * @return void
     */
    public function putMany(array $values, $minutes);

    /**
     * Increment the value of an item in the cache.
     *
     * 增加缓存中的项的值
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function increment($key, $value = 1);

    /**
     * Decrement the value of an item in the cache.
     *
     * 增加缓存中的项的值
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function decrement($key, $value = 1);

    /**
     * Store an item in the cache indefinitely.
     *
     * 在缓存中无限期地存储一个项
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value);

    /**
     * Remove an item from the cache.
     *
     * 从缓存中删除一个项目
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key);

    /**
     * Remove all items from the cache.
     *
     * 从缓存中删除所有项
     *
     * @return bool
     */
    public function flush();

    /**
     * Get the cache key prefix.
     *
     * 获取高速缓存键前缀
     *
     * @return string
     */
    public function getPrefix();
}
