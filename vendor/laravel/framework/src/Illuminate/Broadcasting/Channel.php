<?php

namespace Illuminate\Broadcasting;

class Channel
{
    /**
     * The channel's name.
     *
     * 频道名称
     *
     * @var string
     */
    public $name;

    /**
     * Create a new channel instance.
     *
     * 创建一个新的频道实例
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Convert the channel instance to a string.
     *
     * 将频道实例转换为字符串
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
