<?php

namespace Illuminate\Cache;

use Closure;
use DateTime;
use ArrayAccess;
use Carbon\Carbon;
use BadMethodCallException;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class Repository implements CacheContract, ArrayAccess
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The cache store implementation.
     *
     * 缓存存储实现
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $store;

    /**
     * The event dispatcher implementation.
     *
     * 事件调度程序实现
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The default number of minutes to store items.
     *
     * 存储项目的默认分钟数
     *
     * @var float|int
     */
    protected $default = 60;

    /**
     * Create a new cache repository instance.
     *
     * 创建一个新的缓存存储库实例
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return void
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Determine if an item exists in the cache.
     *
     * 确定缓存中是否存在某个项
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        //                   通过键从缓存中检索一个项
        return ! is_null($this->get($key));
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * 通过键从缓存中检索一个项
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            //通过键从缓存中检索多个项
            return $this->many($key);
        }
        //                通过键从缓存中检索一个项(格式化缓存项的键)
        $value = $this->store->get($this->itemKey($key));

        // If we could not find the cache value, we will fire the missed event and get
        // the default value for this cache value. This default could be a callback
        // so we will execute the value function which will resolve it if needed.
        //
        // 如果我们找不到缓存值，我们将触发错过的事件，并获取该缓存值的默认值
        // 这个默认值可以是一个回调函数，因此我们将执行值函数，如果需要，它将解析它
        //
        if (is_null($value)) {
            //为这个缓存实例触发事件
            $this->event(new CacheMissed($key));

            $value = value($default);
        } else {
            //为这个缓存实例触发事件
            $this->event(new CacheHit($key, $value));
        }

        return $value;
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
        //通过键从缓存中检索多个项                     在每个项目上运行map
        $values = $this->store->many(collect($keys)->map(function ($value, $key) {
            return is_string($key) ? $key : $value;
        //重置基础阵列上的键  获取集合中的所有项目
        })->values()->all());
        //                        在每个项目上运行map
        return collect($values)->map(function ($value, $key) use ($keys) {
            return $this->handleManyResult($keys, $key, $value);//处理“许多”方法的结果
        })->all();//获取集合中的所有项目
    }

    /**
     * Handle a result for the "many" method.
     *
     * 处理“许多”方法的结果
     *
     * @param  array  $keys
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function handleManyResult($keys, $key, $value)
    {
        // If we could not find the cache value, we will fire the missed event and get
        // the default value for this cache value. This default could be a callback
        // so we will execute the value function which will resolve it if needed.
        //
        // 如果我们找不到缓存值，我们将触发错过的事件，并获取该缓存值的默认值
        // 这个默认值可以是一个回调函数，因此我们将执行值函数，如果需要，它将解析它
        //
        if (is_null($value)) {
            //为这个缓存实例触发事件
            $this->event(new CacheMissed($key));

            return isset($keys[$key]) ? value($keys[$key]) : null;
        }

        // If we found a valid value we will fire the "hit" event and return the value
        // back from this function. The "hit" event gives developers an opportunity
        // to listen for every possible cache "hit" throughout this applications.
        //
        // 如果我们找到一个有效值，我们将触发“命中”事件，并从该函数返回值
        // “命中”事件给开发人员一个机会来倾听每一个可能的“命中”在整个应用程序缓存
        //
        //为这个缓存实例触发事件
        $this->event(new CacheHit($key, $value));

        return $value;
    }

    /**
     * Retrieve an item from the cache and delete it.
     *
     * 从缓存中检索一个条目并删除它
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        //用给定的值调用给定的闭包，然后返回值 通过键从缓存中检索一个项
        return tap($this->get($key, $default), function ($value) use ($key) {
            $this->forget($key);//从缓存中删除一个项目
        });
    }

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
    public function put($key, $value, $minutes = null)
    {
        if (is_array($key)) {
            //将多个项目存储在缓存中，在给定的时间内
            return $this->putMany($key, $value);
        }

        //                     计算给定持续时间的分钟数
        if (! is_null($minutes = $this->getMinutes($minutes))) {
            //在缓存中存储一个条目，在给定的时间内  格式化缓存项的键
            $this->store->put($this->itemKey($key), $value, $minutes);
            //为这个缓存实例触发事件
            $this->event(new KeyWritten($key, $value, $minutes));
        }
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
        //                              计算给定持续时间的分钟数
        if (! is_null($minutes = $this->getMinutes($minutes))) {
            //将多个项目存储在缓存中，在给定的时间内
            $this->store->putMany($values, $minutes);

            foreach ($values as $key => $value) {
                //为这个缓存实例触发事件
                $this->event(new KeyWritten($key, $value, $minutes));
            }
        }
    }

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
    public function add($key, $value, $minutes)
    {
        //计算给定持续时间的分钟数
        if (is_null($minutes = $this->getMinutes($minutes))) {
            return false;
        }

        // If the store has an "add" method we will call the method on the store so it
        // has a chance to override this logic. Some drivers better support the way
        // this operation should work with a total "atomic" implementation of it.
        //
        // 如果存储有一个“add”方法，我们将在存储中调用这个方法，这样它就有机会重写这个逻辑
        // 一些驱动程序更好地支持这种操作应该在完全“原子”实现中工作的方式
        //
        if (method_exists($this->store, 'add')) {
            return $this->store->add(
                //格式化缓存项的键
                $this->itemKey($key), $value, $minutes
            );
        }

        // If the value did not exist in the cache, we will put the value in the cache
        // so it exists for subsequent requests. Then, we will return true so it is
        // easy to know if the value gets added. Otherwise, we will return false.
        //
        // 如果该值在缓存中不存在，那么我们将把值放在缓存中，这样它就存在于后续的请求中
        // 然后，我们将返回true，因此很容易知道是否增加了值。否则，我们将返回false
        //
        //             通过键从缓存中检索一个项
        if (is_null($this->get($key))) {
            //在缓存中存储一个项
            $this->put($key, $value, $minutes);

            return true;
        }

        return false;
    }

    /**
     * Increment the value of an item in the cache.
     *
     * 增加缓存中的项的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        //增加缓存中的项的值
        return $this->store->increment($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * 在缓存中减去一个项目的值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        //在缓存中减去一个项目的值
        return $this->store->decrement($key, $value);
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
        //在缓存中无限期地存储一个项    格式化缓存项的键
        $this->store->forever($this->itemKey($key), $value);
        //为这个缓存实例触发事件
        $this->event(new KeyWritten($key, $value, 0));
    }

    /**
     * Get an item from the cache, or store the default value.
     *
     * 从缓存中获取一个项目，或者存储默认值
     *
     * @param  string  $key
     * @param  \DateTime|float|int  $minutes
     * @param  \Closure  $callback
     * @return mixed
     */
    public function remember($key, $minutes, Closure $callback)
    {
        //通过键从缓存中检索一个项
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of minutes so it's available for all subsequent requests.
        //
        // 如果项目存在缓存中我们将立即返回这个,如果不是我们将执行给定的关闭和缓存的结果,对于一个给定的数分钟可用于所有后续请求
        //
        if (! is_null($value)) {
            return $value;
        }
        //在缓存中存储一个项
        $this->put($key, $value = $callback(), $minutes);

        return $value;
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * 从缓存中获取一个条目，或者永久存储默认值
     *
     * @param  string   $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function sear($key, Closure $callback)
    {
        //从缓存中获取一个条目，或者永久存储默认值
        return $this->rememberForever($key, $callback);
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * 从缓存中获取一个条目，或者永久存储默认值
     *
     * @param  string   $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function rememberForever($key, Closure $callback)
    {
        //通过键从缓存中检索一个项
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of minutes so it's available for all subsequent requests.
        //
        // 如果项目存在缓存中我们将立即返回这个,如果不是我们将执行给定的关闭和缓存的结果,对于一个给定的数分钟可用于所有后续请求
        //
        if (! is_null($value)) {
            return $value;
        }
        //在缓存中无限期地存储一个项
        $this->forever($key, $value = $callback());

        return $value;
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
        //用给定的值调用给定的闭包，然后返回值  从缓存中删除一个项目  格式化缓存项的键
        return tap($this->store->forget($this->itemKey($key)), function () use ($key) {
            //为这个缓存实例触发事件
            $this->event(new KeyForgotten($key));
        });
    }

    /**
     * Begin executing a new tags operation if the store supports it.
     *
     * 如果存储支持它，就开始执行新的标记操作
     *
     * @param  array|mixed  $names
     * @return \Illuminate\Cache\TaggedCache
     *
     * @throws \BadMethodCallException
     */
    public function tags($names)
    {
        if (! method_exists($this->store, 'tags')) {
            throw new BadMethodCallException('This cache store does not support tagging.');
        }

        $cache = $this->store->tags($names);

        if (! is_null($this->events)) {
            $cache->setEventDispatcher($this->events);
        }

        return $cache->setDefaultCacheTime($this->default);
    }

    /**
     * Format the key for a cache item.
     *
     * 格式化缓存项的键
     *
     * @param  string  $key
     * @return string
     */
    protected function itemKey($key)
    {
        return $key;
    }

    /**
     * Get the default cache time.
     *
     * 获取默认的缓存时间
     *
     * @return float|int
     */
    public function getDefaultCacheTime()
    {
        return $this->default;
    }

    /**
     * Set the default cache time in minutes.
     *
     * 在分钟内设置默认的缓存时间
     *
     * @param  float|int  $minutes
     * @return $this
     */
    public function setDefaultCacheTime($minutes)
    {
        $this->default = $minutes;

        return $this;
    }

    /**
     * Get the cache store implementation.
     *
     * 获取缓存存储实现
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Fire an event for this cache instance.
     *
     * 为这个缓存实例触发事件
     *
     * @param  string  $event
     * @return void
     */
    protected function event($event)
    {
        if (isset($this->events)) {
            //将事件触发，直到返回第一个非空响应
            $this->events->dispatch($event);
        }
    }

    /**
     * Set the event dispatcher instance.
     *
     * 设置事件调度程序实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Determine if a cached value exists.
     *
     * 确定缓存的值是否存在
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        //确定缓存中是否存在某个项
        return $this->has($key);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * 通过键从缓存中检索一个项
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        //通过键从缓存中检索一个项
        return $this->get($key);
    }

    /**
     * Store an item in the cache for the default time.
     *
     * 在缓存中存储一个条目，以默认时间
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        //在缓存中存储一个项
        $this->put($key, $value, $this->default);
    }

    /**
     * Remove an item from the cache.
     *
     * 从缓存中删除一个项目
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        //从缓存中删除一个项目
        $this->forget($key);
    }

    /**
     * Calculate the number of minutes with the given duration.
     *
     * 计算给定持续时间的分钟数
     *
     * @param  \DateTime|float|int  $duration
     * @return float|int|null
     */
    protected function getMinutes($duration)
    {
        if ($duration instanceof DateTime) {
            //获取当前日期和时间的Carbon实例  以秒为差        从DateTime中创建一个碳实例
            $duration = Carbon::now()->diffInSeconds(Carbon::instance($duration), false) / 60;
        }

        return (int) ($duration * 60) > 0 ? $duration : null;
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the store.
     *
     * 处理对宏的动态调用或将丢失的方法传递到存储中
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        //检查宏是否已注册
        if (static::hasMacro($method)) {
            //动态调用类的调用
            return $this->macroCall($method, $parameters);
        }

        return $this->store->$method(...$parameters);
    }

    /**
     * Clone cache repository instance.
     *
     * 克隆缓存存储库实例
     *
     * @return void
     */
    public function __clone()
    {
        $this->store = clone $this->store;
    }
}
