<?php

namespace Illuminate\Redis\Connections;

use Closure;

class PhpRedisConnection extends Connection
{
    /**
     * Create a new Predis connection.
     *
     * 创建一个新的Predis连接
     *
     * @param  \Redis  $client
     * @return void
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * Returns the value of the given key.
     *
     * 返回给定键的值
     *
     * @param  string  $key
     * @return string|null
     */
    public function get($key)
    {
        $result = $this->client->get($key);

        return $result !== false ? $result : null;
    }

    /**
     * Get the values of all the given keys.
     *
     * 获取所有给定键的值
     *
     * @param  array  $keys
     * @return array
     */
    public function mget(array $keys)
    {
        return array_map(function ($value) {
            return $value !== false ? $value : null;
        }, $this->client->mget($keys));
    }

    /**
     * Set the string value in argument as value of the key.
     *
     * 将参数中的字符串值设置为键值
     *
     * @param string  $key
     * @param mixed  $value
     * @param string|null  $expireResolution
     * @param int|null  $expireTTL
     * @param string|null  $flag
     * @return bool
     */
    public function set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
    {
        //对Redis数据库运行一个命令
        return $this->command('set', [
            $key,
            $value,
            $expireResolution ? [$expireResolution, $flag => $expireTTL] : null,
        ]);
    }

    /**
     * Removes the first count occurences of the value element from the list.
     *
     * 从列表中删除值元素的第一个计数
     *
     * @param  string  $key
     * @param  int  $count
     * @param  $value  $value
     * @return int|false
     */
    public function lrem($key, $count, $value)
    {
        //对Redis数据库运行一个命令
        return $this->command('lrem', [$key, $value, $count]);
    }

    /**
     * Removes and returns a random element from the set value at key.
     *
     * 从键值中删除并返回一个随机元素
     *
     * @param  string  $key
     * @param  int|null  $count
     * @return mixed|false
     */
    public function spop($key, $count = null)
    {
        //对Redis数据库运行一个命令
        return $this->command('spop', [$key]);
    }

    /**
     * Add one or more members to a sorted set or update its score if it already exists.
     *
     * 将一个或多个成员添加到一个已排序的集合中，或者在已经存在的情况中更新它的得分
     *
     * @param  string  $key
     * @param  mixed  $dictionary
     * @return int
     */
    public function zadd($key, ...$dictionary)
    {
        if (count($dictionary) === 1) {
            $_dictionary = [];

            foreach ($dictionary[0] as $member => $score) {
                $_dictionary[] = $score;
                $_dictionary[] = $member;
            }

            $dictionary = $_dictionary;
        }

        return $this->client->zadd($key, ...$dictionary);
    }

    /**
     * Evaluate a LUA script serverside, from the SHA1 hash of the script instead of the script itself.
     *
     * 从脚本的SHA1散列中评估一个LUA脚本服务器端，而不是脚本本身
     *
     * @param  string  $script
     * @param  int  $numkeys
     * @param  mixed  $arguments
     * @return mixed
     */
    public function evalsha($script, $numkeys, ...$arguments)
    {
        //对Redis数据库运行一个命令
        return $this->command('evalsha', [
            $this->script('load', $script), $arguments, $numkeys,
        ]);
    }

    /**
     * Proxy a call to the eval function of PhpRedis.
     *
     * 代理对PhpRedis的eval函数的调用
     *
     * @param  array  $parameters
     * @return mixed
     */
    protected function proxyToEval(array $parameters)
    {
        //对Redis数据库运行一个命令
        return $this->command('eval', [
            isset($parameters[0]) ? $parameters[0] : null,
            array_slice($parameters, 2),
            isset($parameters[1]) ? $parameters[1] : null,
        ]);
    }

    /**
     * Subscribe to a set of given channels for messages.
     *
     * 订阅一组给定的消息通道
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function subscribe($channels, Closure $callback)
    {
        $this->client->subscribe((array) $channels, function ($redis, $channel, $message) use ($callback) {
            $callback($message, $channel);
        });
    }

    /**
     * Subscribe to a set of given channels with wildcards.
     *
     * 使用通配符订阅一组给定通道
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @return void
     */
    public function psubscribe($channels, Closure $callback)
    {
        $this->client->psubscribe((array) $channels, function ($redis, $pattern, $channel, $message) use ($callback) {
            $callback($message, $channel);
        });
    }

    /**
     * Subscribe to a set of given channels for messages.
     *
     * 订阅一组给定的消息通道
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @param  string  $method
     * @return void
     */
    public function createSubscription($channels, Closure $callback, $method = 'subscribe')
    {
        //
    }

    /**
     * Disconnects from the Redis instance.
     *
     * 从Redis实例断开连接
     *
     * @return void
     */
    public function disconnect()
    {
        $this->client->close();
    }

    /**
     * Pass other method calls down to the underlying client.
     *
     * 将其他方法调用传递给底层客户端
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $method = strtolower($method);

        if ($method == 'eval') {
            //代理对PhpRedis的eval函数的调用
            return $this->proxyToEval($parameters);
        }

        if ($method == 'zrangebyscore' || $method == 'zrevrangebyscore') {
            $parameters = array_map(function ($parameter) {
                return is_array($parameter) ? array_change_key_case($parameter) : $parameter;
            }, $parameters);
        }
        //将其他方法调用传递给底层客户端
        return parent::__call($method, $parameters);
    }
}
