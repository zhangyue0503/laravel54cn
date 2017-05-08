<?php

namespace Illuminate\Cache\Events;

abstract class CacheEvent
{
    /**
     * The key of the event.
     *
     * 事件的key
     *
     * @var string
     */
    public $key;

    /**
     * The tags that were assigned to the key.
     *
     * 分配给关键字的标签
     *
     * @var array
     */
    public $tags;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  string  $key
     * @param  array  $tags
     * @return void
     */
    public function __construct($key, array $tags = [])
    {
        $this->key = $key;
        $this->tags = $tags;
    }

    /**
     * Set the tags for the cache event.
     *
     * 为缓存事件设置标签
     *
     * @param  array  $tags
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }
}
