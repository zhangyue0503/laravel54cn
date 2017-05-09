<?php

namespace Illuminate\Cache;

use Memcached;
use Carbon\Carbon;
use ReflectionMethod;
use Illuminate\Contracts\Cache\Store;

class MemcachedStore extends TaggableStore implements Store
{
    /**
     * The Memcached instance.
     *
     * Memcached实例
     *
     * @var \Memcached
     */
    protected $memcached;

    /**
     * A string that should be prepended to keys.
     *
     * 一个字符串,应该是返回键
     *
     * @var string
     */
    protected $prefix;

    /**
     * Indicates whether we are using Memcached version >= 3.0.0.
     *
     * 表明我们是否正在使用Memcached > = 3.0.0版本
     *
     * @var bool
     */
    protected $onVersionThree;

    /**
     * Create a new Memcached store.
     *
     * 创建一个新的Memcached存储
     *
     * @param  \Memcached  $memcached
     * @param  string      $prefix
     * @return void
     */
    public function __construct($memcached, $prefix = '')
    {
        $this->setPrefix($prefix);//设置高速缓存键前缀
        $this->memcached = $memcached;

        $this->onVersionThree = (new ReflectionMethod('Memcached', 'getMulti'))
                            ->getNumberOfParameters() == 2;
    }

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
        $value = $this->memcached->get($this->prefix.$key);

        if ($this->memcached->getResultCode() == 0) {
            return $value;
        }
    }

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
    public function many(array $keys)
    {
        $prefixedKeys = array_map(function ($key) {
            return $this->prefix.$key;
        }, $keys);

        if ($this->onVersionThree) {
            $values = $this->memcached->getMulti($prefixedKeys, Memcached::GET_PRESERVE_ORDER);
        } else {
            $null = null;

            $values = $this->memcached->getMulti($prefixedKeys, $null, Memcached::GET_PRESERVE_ORDER);
        }

        if ($this->memcached->getResultCode() != 0) {
            return array_fill_keys($keys, null);
        }

        return array_combine($keys, $values);
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
        //                                                 获得给定的分钟数的UNIX时间戳
        $this->memcached->set($this->prefix.$key, $value, $this->toTimestamp($minutes));
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * 将多个项目存储在缓存中，在给定的时间内
     *
     * @param  array  $values
     * @param  float|int  $minutes
     * @return void
     */
    public function putMany(array $values, $minutes)
    {
        $prefixedValues = [];

        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix.$key] = $value;
        }
        //                                                 获得给定的分钟数的UNIX时间戳
        $this->memcached->setMulti($prefixedValues, $this->toTimestamp($minutes));
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * 如果键不存在，则在缓存中存储一个项
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  float|int  $minutes
     * @return bool
     */
    public function add($key, $value, $minutes)
    {
        //                                                 获得给定的分钟数的UNIX时间戳
        return $this->memcached->add($this->prefix.$key, $value, $this->toTimestamp($minutes));
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
        return $this->memcached->increment($this->prefix.$key, $value);
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
        return $this->memcached->decrement($this->prefix.$key, $value);
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
        //在缓存中存储一个条目，在给定的时间内
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
        return $this->memcached->delete($this->prefix.$key);
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
        return $this->memcached->flush();
    }

    /**
     * Get the UNIX timestamp for the given number of minutes.
     *
     * 获得给定的分钟数的UNIX时间戳
     *
     * @param  int  $minutes
     * @return int
     */
    protected function toTimestamp($minutes)
    {
        //                         获取当前日期和时间的Carbon实例->在实例中添加秒
        return $minutes > 0 ? Carbon::now()->addSeconds($minutes * 60)->getTimestamp() : 0;
    }

    /**
     * Get the underlying Memcached connection.
     *
     * 获取底层的Memcached连接
     *
     * @return \Memcached
     */
    public function getMemcached()
    {
        return $this->memcached;
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

    /**
     * Set the cache key prefix.
     *
     * 设置高速缓存键前缀
     *
     * @param  string  $prefix
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = ! empty($prefix) ? $prefix.':' : '';
    }
}
