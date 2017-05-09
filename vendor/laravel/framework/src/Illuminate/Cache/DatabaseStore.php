<?php

namespace Illuminate\Cache;

use Closure;
use Exception;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class DatabaseStore implements Store
{
    use RetrievesMultipleKeys;

    /**
     * The database connection instance.
     *
     * 数据库连接实例
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The encrypter instance.
     *
     * 加密实例
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The name of the cache table.
     *
     * 缓存表的名称
     *
     * @var string
     */
    protected $table;

    /**
     * A string that should be prepended to keys.
     *
     * 一个字符串,应该是返回键
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new database store.
     *
     * 创建一个新的数据库存储
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @param  string  $table
     * @param  string  $prefix
     * @return void
     */
    public function __construct(ConnectionInterface $connection, EncrypterContract $encrypter,
                                $table, $prefix = '')
    {
        $this->table = $table;
        $this->prefix = $prefix;
        $this->encrypter = $encrypter;
        $this->connection = $connection;
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
        $prefixed = $this->prefix.$key;
        //获取缓存表的查询构建器->将基本WHERE子句添加到查询中->执行查询和得到的第一个结果
        $cache = $this->table()->where('key', '=', $prefixed)->first();

        // If we have a cache record we will check the expiration time against current
        // time on the system and see if the record has expired. If it has, we will
        // remove the records from the database table so it isn't returned again.
        //
        // 如果我们有缓存记录，我们将检查系统中当前时间的过期时间，并查看记录是否已经过期
        // 如果有，我们将从数据库表中删除记录，这样就不会再返回
        //
        if (is_null($cache)) {
            return;
        }

        $cache = is_array($cache) ? (object) $cache : $cache;

        // If this cache expiration date is past the current time, we will remove this
        // item from the cache. Then we will return a null value since the cache is
        // expired. We will use "Carbon" to make this comparison with the column.
        //
        // 如果这个缓存过期日期超过当前时间，我们将从缓存中删除该项
        // 然后，由于缓存过期，我们将返回一个null值
        // 我们将用“碳”来与这一列进行比较
        //
        // 获取当前日期和时间的Carbon实例
        if (Carbon::now()->getTimestamp() >= $cache->expiration) {
            $this->forget($key);//从缓存中删除一个项目

            return;
        }
        //对给定值进行解密
        return $this->encrypter->decrypt($cache->value);
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
        $key = $this->prefix.$key;

        // All of the cached values in the database are encrypted in case this is used
        // as a session data store by the consumer. We'll also calculate the expire
        // time and place that on the table so we will check it on our retrieval.
        //
        // 数据库中的所有缓存值都是经过加密的，以防用户使用它作为会话数据存储
        // 我们还将计算到期时间，并将其放在表上，以便在检索时检查它
        //
        //                  对给定值进行加密
        $value = $this->encrypter->encrypt($value);
        //获取当前系统时间
        $expiration = $this->getTime() + (int) ($minutes * 60);

        try {
            //获取缓存表的查询构建器->将新记录插入数据库
            $this->table()->insert(compact('key', 'value', 'expiration'));
        } catch (Exception $e) {
            //获取缓存表的查询构建器->将基本WHERE子句添加到查询中->更新数据库中的记录
            $this->table()->where('key', $key)->update(compact('value', 'expiration'));
        }
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
        //       在缓存中增加或减少一个项
        return $this->incrementOrDecrement($key, $value, function ($current, $value) {
            return $current + $value;
        });
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
        //     在缓存中增加或减少一个项
        return $this->incrementOrDecrement($key, $value, function ($current, $value) {
            return $current - $value;
        });
    }

    /**
     * Increment or decrement an item in the cache.
     *
     * 在缓存中增加或减少一个项
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \Closure  $callback
     * @return int|bool
     */
    protected function incrementOrDecrement($key, $value, Closure $callback)
    {
        //                  在事务中执行闭包
        return $this->connection->transaction(function () use ($key, $value, $callback) {
            $prefixed = $this->prefix.$key;
            //获取缓存表的查询构建器->将基本WHERE子句添加到查询中->锁定表中选定的行进行更新->执行查询和得到的第一个结果
            $cache = $this->table()->where('key', $prefixed)
                        ->lockForUpdate()->first();

            // If there is no value in the cache, we will return false here. Otherwise the
            // value will be decrypted and we will proceed with this function to either
            // increment or decrement this value based on the given action callbacks.
            //
            // 如果缓存中没有值，我们将返回false
            // 否则，该值将被解密，我们将继续使用这个函数，根据给定的动作回调来增加或递减这个值
            //
            if (is_null($cache)) {
                return false;
            }

            $cache = is_array($cache) ? (object) $cache : $cache;
            //                      对给定值进行解密
            $current = $this->encrypter->decrypt($cache->value);

            // Here we'll call this callback function that was given to the function which
            // is used to either increment or decrement the function. We use a callback
            // so we do not have to recreate all this logic in each of the functions.
            //
            // 这里我们将调用这个函数的回调函数它是用来增加或递减函数的
            // 我们使用一个回调，这样我们就不必在每个函数中重新创建所有的逻辑
            //
            $new = $callback((int) $current, $value);

            if (! is_numeric($current)) {
                return false;
            }

            // Here we will update the values in the table. We will also encrypt the value
            // since database cache values are encrypted by default with secure storage
            // that can't be easily read. We will return the new value after storing.
            //
            // 在这里，我们将更新表中的值。我们还将加密这个值，因为数据库缓存的值在默认情况下是加密的，而不容易读取的安全存储
            // 我们将在存储后返回新值
            //
            //获取缓存表的查询构建器->将基本WHERE子句添加到查询中->更新数据库中的记录
            $this->table()->where('key', $prefixed)->update([
                'value' => $this->encrypter->encrypt($new),//对给定值进行加密
            ]);

            return $new;
        });
    }

    /**
     * Get the current system time.
     *
     * 获取当前系统时间
     *
     * @return int
     */
    protected function getTime()
    {
        //获取当前日期和时间的Carbon实例
        return Carbon::now()->getTimestamp();
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
        $this->put($key, $value, 5256000);
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
        //获取缓存表的查询构建器->将基本WHERE子句添加到查询中->从数据库中删除记录
        $this->table()->where('key', '=', $this->prefix.$key)->delete();

        return true;
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
        //获取缓存表的查询构建器->从数据库中删除记录
        return (bool) $this->table()->delete();
    }

    /**
     * Get a query builder for the cache table.
     *
     * 获取缓存表的查询构建器
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        //                      获取缓存表的查询构建器
        return $this->connection->table($this->table);
    }

    /**
     * Get the underlying database connection.
     *
     * 获取底层数据库连接
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the encrypter instance.
     *
     * 获取加密实例
     *
     * @return \Illuminate\Contracts\Encryption\Encrypter
     */
    public function getEncrypter()
    {
        return $this->encrypter;
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
