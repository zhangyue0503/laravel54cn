<?php

namespace Illuminate\Support;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
//流式对象容器
class Fluent implements ArrayAccess, Arrayable, Jsonable, JsonSerializable
{
    /**
     * All of the attributes set on the container.
     *
     * 容器上设置的所有属性
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Create a new fluent container instance.
     *
     * 创建一个新的流容器实例
     *
     * @param  array|object    $attributes
     * @return void
     */
    public function __construct($attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Get an attribute from the container.
     *
     * 从容器中获取属性
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return value($default);
    }

    /**
     * Get the attributes from the container.
     *
     * 从容器中获取所有属性
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Convert the Fluent instance to an array.
     *
     * 转换流实例为数组
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * 转换对象为可被JSON序列化
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();// 转换流实例为数组
    }

    /**
     * Convert the Fluent instance to JSON.
     *
     * 转换流实例为JSON
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        //                   转换对象为可被JSON序列化
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Determine if the given offset exists.
     *
     * 确定给定偏移是否存在
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->{$offset});
    }

    /**
     * Get the value for a given offset.
     *
     * 获取给定偏移量的值
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    /**
     * Set the value at the given offset.
     *
     * 设置给定偏移量的值
     *
     * @param  string  $offset
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    /**
     * Unset the value at the given offset.
     *
     * 删除给定偏移量的值
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->{$offset});
    }

    /**
     * Handle dynamic calls to the container to set attributes.
     *
     * 处理容器的动态调用以设置属性
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return $this
     */
    public function __call($method, $parameters)
    {
        $this->attributes[$method] = count($parameters) > 0 ? $parameters[0] : true;

        return $this;
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * 动态检索属性值
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        //        从容器中获取属性
        return $this->get($key);
    }

    /**
     * Dynamically set the value of an attribute.
     *
     * 动态设置属性值
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Dynamically check if an attribute is set.
     *
     * 动态检查属性是否设置
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Dynamically unset an attribute.
     *
     * 动态删除属性
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}
