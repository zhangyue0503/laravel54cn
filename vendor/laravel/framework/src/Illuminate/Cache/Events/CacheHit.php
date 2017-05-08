<?php

namespace Illuminate\Cache\Events;

class CacheHit extends CacheEvent
{
    /**
     * The value that was retrieved.
     *
     * 检索到的值
     *
     * @var mixed
     */
    public $value;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $tags
     * @return void
     */
    public function __construct($key, $value, array $tags = [])
    {
        //创建一个新的事件实例
        parent::__construct($key, $tags);

        $this->value = $value;
    }
}
