<?php

namespace Illuminate\Redis\Connections;

use Closure;

abstract class Connection
{
    /**
     * The Predis client.
     *
     * Predis客户端
     *
     * @var \Predis\Client
     */
    protected $client;

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
    abstract public function createSubscription($channels, Closure $callback, $method = 'subscribe');

    /**
     * Get the underlying Redis client.
     *
     * 获得底层的Redis客户端
     *
     * @return mixed
     */
    public function client()
    {
        return $this->client;
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
        //订阅一组给定的消息通道
        return $this->createSubscription($channels, $callback, __FUNCTION__);
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
        //订阅一组给定的消息通道
        return $this->createSubscription($channels, $callback, __FUNCTION__);
    }

    /**
     * Run a command against the Redis database.
     *
     * 对Redis数据库运行一个命令
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function command($method, array $parameters = [])
    {
        return $this->client->{$method}(...$parameters);
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
        //对Redis数据库运行一个命令
        return $this->command($method, $parameters);
    }
}
