<?php

namespace Illuminate\Support;

use Countable;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
//视图错误包
class ViewErrorBag implements Countable
{
    /**
     * The array of the view error bags.
     *
     * 视图错误包的数组
     *
     * @var array
     */
    protected $bags = [];

    /**
     * Checks if a named MessageBag exists in the bags.
     *
     * 判断是否一个叫消息包存在于包数组中
     *
     * @param  string  $key
     * @return bool
     */
    public function hasBag($key = 'default')
    {
        return isset($this->bags[$key]);
    }

    /**
     * Get a MessageBag instance from the bags.
     *
     * 从包数组中获取消息包实例
     *
     * @param  string  $key
     * @return \Illuminate\Contracts\Support\MessageBag
     */
    public function getBag($key)
    {
        //     使用“点”符号从数组中获取一个项           创建一个新的信息包实例
        return Arr::get($this->bags, $key) ?: new MessageBag;
    }

    /**
     * Get all the bags.
     *
     * 获取所有包
     *
     * @return array
     */
    public function getBags()
    {
        return $this->bags;
    }

    /**
     * Add a new MessageBag instance to the bags.
     *
     * 在包数组中添加一个新的消息包实例
     *
     * @param  string  $key
     * @param  \Illuminate\Contracts\Support\MessageBag  $bag
     * @return $this
     */
    public function put($key, MessageBagContract $bag)
    {
        $this->bags[$key] = $bag;

        return $this;
    }

    /**
     * Determine if the default message bag has any messages.
     *
     * 确定默认信息包是否有任何消息
     *
     * @return bool
     */
    public function any()
    {
        //获取默认包中的消息数
        return $this->count() > 0;
    }

    /**
     * Get the number of messages in the default bag.
     *
     * 获取默认包中的消息数
     *
     * @return int
     */
    public function count()
    {
        //      从包数组中获取消息包实例    获取容器中的消息数
        return $this->getBag('default')->count();
    }

    /**
     * Dynamically call methods on the default bag.
     *
     * 动态调用默认包的方法
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        //从包数组中获取消息包实例
        return $this->getBag('default')->$method(...$parameters);
    }

    /**
     * Dynamically access a view error bag.
     *
     * 动态访问视图错误包
     *
     * @param  string  $key
     * @return \Illuminate\Contracts\Support\MessageBag
     */
    public function __get($key)
    {
        //从包数组中获取消息包实例
        return $this->getBag($key);
    }

    /**
     * Dynamically set a view error bag.
     *
     * 动态设置视图错误包
     *
     * @param  string  $key
     * @param  \Illuminate\Contracts\Support\MessageBag  $value
     * @return void
     */
    public function __set($key, $value)
    {
        //在包数组中添加一个新的消息包实例
        $this->put($key, $value);
    }
}
