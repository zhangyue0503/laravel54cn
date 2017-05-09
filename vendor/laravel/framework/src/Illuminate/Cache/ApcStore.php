<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;

class ApcStore extends TaggableStore implements Store
{
    use RetrievesMultipleKeys;

    /**
     * The APC wrapper instance.
     *
     * APC包装器实例
     *
     * @var \Illuminate\Cache\ApcWrapper
     */
    protected $apc;

    /**
     * A string that should be prepended to keys.
     *
     * 一个字符串,应该是返回键
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new APC store.
     *
     * 创建一个新的APC商店
     *
     * @param  \Illuminate\Cache\ApcWrapper  $apc
     * @param  string  $prefix
     * @return void
     */
    public function __construct(ApcWrapper $apc, $prefix = '')
    {
        $this->apc = $apc;
        $this->prefix = $prefix;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * 通过键从缓存中检索一个项
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        //从缓存中获取一个项目
        $value = $this->apc->get($this->prefix.$key);

        if ($value !== false) {
            return $value;
        }
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
        //在缓存中存储一个项
        $this->apc->put($this->prefix.$key, $value, (int) ($minutes * 60));
    }

    /**
     * Increment the value of an item in the cache.
     *
     * 增加缓存中的项的值
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        //增加缓存中的项的值
        return $this->apc->increment($this->prefix.$key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * 在缓存中减去一个项目的值
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        //在缓存中减去一个项目的值
        return $this->apc->decrement($this->prefix.$key, $value);
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
        // 在缓存中存储一个条目，在给定的时间内
        $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * 从缓存中删除一个项目
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        //从缓存中删除一个项目
        return $this->apc->delete($this->prefix.$key);
    }

    /**
     * Remove all items from the cache.
     *
     * 从缓存中删除所有项目
     *
     * @return bool
     */
    public function flush()
    {
        //从缓存中删除所有项目
        return $this->apc->flush();
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
        return $this->prefix;
    }
}
