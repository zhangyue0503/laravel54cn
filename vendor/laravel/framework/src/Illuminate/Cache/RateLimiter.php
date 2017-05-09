<?php

namespace Illuminate\Cache;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as Cache;

class RateLimiter
{
    /**
     * The cache store implementation.
     *
     * 缓存存储实现
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new rate limiter instance.
     *
     * 创建一个新的速率限制实例
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * 确定给定的键是否被“访问”过多次
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  float|int  $decayMinutes
     * @return bool
     */
    public function tooManyAttempts($key, $maxAttempts, $decayMinutes = 1)
    {
        //确定缓存中是否存在某个项
        if ($this->cache->has($key.':lockout')) {
            return true;
        }
        //获取给定键的尝试次数
        if ($this->attempts($key) >= $maxAttempts) {
            $this->lockout($key, $decayMinutes);//将锁定键添加到缓存中

            $this->resetAttempts($key);//重置给定键的尝试次数

            return true;
        }

        return false;
    }

    /**
     * Add the lockout key to the cache.
     *
     * 将锁定键添加到缓存中
     *
     * @param  string  $key
     * @param  int  $decayMinutes
     * @return void
     */
    protected function lockout($key, $decayMinutes)
    {
        //如果键不存在，则在缓存中存储一个项
        $this->cache->add(
            //                获取当前日期和时间的Carbon实例
            $key.':lockout', Carbon::now()->getTimestamp() + ($decayMinutes * 60), $decayMinutes
        );
    }

    /**
     * Increment the counter for a given key for a given decay time.
     *
     * 为给定的衰减时间增加给定键的计数器
     *
     * @param  string  $key
     * @param  float|int  $decayMinutes
     * @return int
     */
    public function hit($key, $decayMinutes = 1)
    {
        ////如果键不存在，则在缓存中存储一个项
        $this->cache->add($key, 0, $decayMinutes);
        //                       增加缓存中的项的值
        return (int) $this->cache->increment($key);
    }

    /**
     * Get the number of attempts for the given key.
     *
     * 获取给定键的尝试次数
     *
     * @param  string  $key
     * @return mixed
     */
    public function attempts($key)
    {
        //                  通过键从缓存中检索一个项
        return $this->cache->get($key, 0);
    }

    /**
     * Reset the number of attempts for the given key.
     *
     * 重置给定键的尝试次数
     *
     * @param  string  $key
     * @return mixed
     */
    public function resetAttempts($key)
    {
        // 从缓存中获取一个条目，或者永久存储默认值
        return $this->cache->forget($key);
    }

    /**
     * Get the number of retries left for the given key.
     *
     * 为给定的键获取剩余的重试次数
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return int
     */
    public function retriesLeft($key, $maxAttempts)
    {
        //获取给定键的尝试次数
        $attempts = $this->attempts($key);

        return $maxAttempts - $attempts;
    }

    /**
     * Clear the hits and lockout for the given key.
     *
     * 清除给定键的点击和锁定
     *
     * @param  string  $key
     * @return void
     */
    public function clear($key)
    {
        //重置给定键的尝试次数
        $this->resetAttempts($key);
        //从缓存中删除一个项目
        $this->cache->forget($key.':lockout');
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     *
     * 获得秒数，直到“键”再次访问
     *
     * @param  string  $key
     * @return int
     */
    public function availableIn($key)
    {
        //通过键从缓存中检索一个项                     获取当前日期和时间的Carbon实例
        return $this->cache->get($key.':lockout') - Carbon::now()->getTimestamp();
    }
}
