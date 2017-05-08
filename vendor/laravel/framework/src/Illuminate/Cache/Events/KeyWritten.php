<?php

namespace Illuminate\Cache\Events;

class KeyWritten extends CacheEvent
{
    /**
     * The value that was written.
     *
     * 所写的值
     *
     * @var mixed
     */
    public $value;

    /**
     * The number of minutes the key should be valid.
     *
     * 关键字key的有效分钟
     *
     * @var int
     */
    public $minutes;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $minutes
     * @param  array  $tags
     * @return void
     */
    public function __construct($key, $value, $minutes, $tags = [])
    {
        //创建一个新的事件实例
        parent::__construct($key, $tags);

        $this->value = $value;
        $this->minutes = $minutes;
    }
}
