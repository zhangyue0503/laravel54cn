<?php

namespace Illuminate\Contracts\Support;

interface MessageBag
{
    /**
     * Get the keys present in the message bag.
     *
     * 获取信息袋中出现的密钥
     *
     * @return array
     */
    public function keys();

    /**
     * Add a message to the bag.
     *
     * 将消息添加到包中
     *
     * @param  string  $key
     * @param  string  $message
     * @return $this
     */
    public function add($key, $message);

    /**
     * Merge a new array of messages into the bag.
     *
     * 将新的消息数组合并到包中
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array  $messages
     * @return $this
     */
    public function merge($messages);

    /**
     * Determine if messages exist for a given key.
     *
     * 确定给定键是否存在消息
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key);

    /**
     * Get the first message from the bag for a given key.
     *
     * 从包中获取一个给定键的第一个消息
     *
     * @param  string  $key
     * @param  string  $format
     * @return string
     */
    public function first($key = null, $format = null);

    /**
     * Get all of the messages from the bag for a given key.
     *
     * 从包中获取所有的消息，获取一个给定的键
     *
     * @param  string  $key
     * @param  string  $format
     * @return array
     */
    public function get($key, $format = null);

    /**
     * Get all of the messages for every key in the bag.
     *
     * 获取包中每个关键字的所有消息
     *
     * @param  string  $format
     * @return array
     */
    public function all($format = null);

    /**
     * Get the default message format.
     *
     * 获取默认消息格式
     *
     * @return string
     */
    public function getFormat();

    /**
     * Set the default message format.
     *
     * 设置默认消息格式
     *
     * @param  string  $format
     * @return $this
     */
    public function setFormat($format = ':message');

    /**
     * Determine if the message bag has any messages.
     *
     * 确定消息包是否有任何消息
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Get the number of messages in the container.
     *
     * 获取容器中的消息数
     *
     * @return int
     */
    public function count();

    /**
     * Get the instance as an array.
     *
     * 将实例作为数组
     *
     * @return array
     */
    public function toArray();
}
