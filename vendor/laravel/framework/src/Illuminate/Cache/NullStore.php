<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;

class NullStore extends TaggableStore implements Store
{
    use RetrievesMultipleKeys;

    /**
     * The array of stored values.
     *
     * 存储值的数组
     *
     * @var array
     */
    protected $storage = [];

    /**
     * Retrieve an item from the cache by key.
     *
     * 通过键从缓存中检索一个项
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        //
    }

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
    public function put($key, $value, $minutes)
    {
        //
    }

    /**
     * Increment the value of an item in the cache.
     *
     * 增加缓存中的项的值
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        //
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * 在缓存中减去一个项目的值
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        //
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * 在缓存中无限期地存储一个项
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        //
    }

    /**
     * Remove an item from the cache.
     *
     * 从缓存中删除一个项目
     *
     * @param  string  $key
     * @return void
     */
    public function forget($key)
    {
        //
    }

    /**
     * Remove all items from the cache.
     *
     * 从缓存中删除所有项
     *
     * @return bool
     */
    public function flush()
    {
        return true;
    }

    /**
     * Get the cache key prefix.
     *
     * 获取高速缓存键前缀
     *
     * @return string
     */
    public function getPrefix()
    {
        return '';
    }
}
