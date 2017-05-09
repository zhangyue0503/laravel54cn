<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Redis\Factory as Redis;

class RedisStore extends TaggableStore implements Store
{
    /**
     * The Redis factory implementation.
     *
     * Redis工厂实现
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * A string that should be prepended to keys.
     *
     * 一个字符串,应该是返回键
     *
     * @var string
     */
    protected $prefix;

    /**
     * The Redis connection that should be used.
     *
     * 应该使用的Redis连接
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new Redis store.
     *
     * 创建一个新的Redis存储
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @param  string  $prefix
     * @param  string  $connection
     * @return void
     */
    public function __construct(Redis $redis, $prefix = '', $connection = 'default')
    {
        $this->redis = $redis;
        $this->setPrefix($prefix);//设置高速缓存键前缀
        $this->setConnection($connection);//设置要使用的连接名称
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
        //    获取Redis连接实例
        $value = $this->connection()->get($this->prefix.$key);
        //                           非系列化的价值
        return ! is_null($value) ? $this->unserialize($value) : null;
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
        $results = [];
        //                获取Redis连接实例
        $values = $this->connection()->mget(array_map(function ($key) {
            return $this->prefix.$key;
        }, $keys));

        foreach ($values as $index => $value) {
            //                          反序列化值
            $results[$keys[$index]] = $this->unserialize($value);
        }

        return $results;
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
        //获取Redis连接实例
        $this->connection()->setex(
            $this->prefix.$key, (int) max(1, $minutes * 60), $this->serialize($value)
        );
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
        //获取Redis连接实例
        $this->connection()->multi();

        foreach ($values as $key => $value) {
            //在缓存中存储一个条目，在给定的时间内
            $this->put($key, $value, $minutes);
        }
        //获取Redis连接实例
        $this->connection()->exec();
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
        $lua = "return redis.call('exists',KEYS[1])<1 and redis.call('setex',KEYS[1],ARGV[2],ARGV[1])";
        //              获取Redis连接实例
        return (bool) $this->connection()->eval(
            //                               序列化值
            $lua, 1, $this->prefix.$key, $this->serialize($value), (int) max(1, $minutes * 60)
        );
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
        //              获取Redis连接实例
        return $this->connection()->incrby($this->prefix.$key, $value);
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
        //              获取Redis连接实例
        return $this->connection()->decrby($this->prefix.$key, $value);
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
        //      获取Redis连接实例
        $this->connection()->set($this->prefix.$key, $this->serialize($value));
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
        //               获取Redis连接实例
        return (bool) $this->connection()->del($this->prefix.$key);
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
        //    获取Redis连接实例
        $this->connection()->flushdb();

        return true;
    }

    /**
     * Begin executing a new tags operation.
     *
     * 开始执行新的标记操作
     *
     * @param  array|mixed  $names
     * @return \Illuminate\Cache\RedisTaggedCache
     */
    public function tags($names)
    {
        //               Redis标记缓存
        return new RedisTaggedCache(
            //创建一个新的TagSet实例
            $this, new TagSet($this, is_array($names) ? $names : func_get_args())
        );
    }

    /**
     * Get the Redis connection instance.
     *
     * 获取Redis连接实例
     *
     * @return \Predis\ClientInterface
     */
    public function connection()
    {
        //通过名称获得一个Redis连接
        return $this->redis->connection($this->connection);
    }

    /**
     * Set the connection name to be used.
     *
     * 设置要使用的连接名称
     *
     * @param  string  $connection
     * @return void
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the Redis database instance.
     *
     * 获取Redis数据库实例
     *
     * @return \Illuminate\Contracts\Redis\Factory
     */
    public function getRedis()
    {
        return $this->redis;
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

    /**
     * Serialize the value.
     *
     * 序列化值
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function serialize($value)
    {
        return is_numeric($value) ? $value : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * 反序列化值
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }
}
