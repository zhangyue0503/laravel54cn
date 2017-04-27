<?php

namespace Illuminate\Support;
//高阶集合代理
class HigherOrderCollectionProxy
{
    /**
     * The collection being operated on.
     *
     * 正在运行的集合
     *
     * @var \Illuminate\Support\Collection
     */
    protected $collection;

    /**
     * The method being proxied.
     *
     * 被代理的方法
     *
     * @var string
     */
    protected $method;

    /**
     * Create a new proxy instance.
     *
     * 创建一个新的代理实例
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  string  $method
     * @return void
     */
    public function __construct(Collection $collection, $method)
    {
        $this->method = $method;
        $this->collection = $collection;
    }

    /**
     * Proxy accessing an attribute onto the collection items.
     *
     * 代理访问属性到集合项上
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->collection->{$this->method}(function ($value) use ($key) {
            return is_array($value) ? $value[$key] : $value->{$key};
        });
    }

    /**
     * Proxy a method call onto the collection items.
     *
     * 代理方法调用到集合项上
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->collection->{$this->method}(function ($value) use ($method, $parameters) {
            return $value->{$method}(...$parameters);
        });
    }
}
