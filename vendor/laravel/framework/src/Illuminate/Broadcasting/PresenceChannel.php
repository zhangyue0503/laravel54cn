<?php

namespace Illuminate\Broadcasting;

class PresenceChannel extends Channel
{
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
        parent::__construct('presence-'.$name);
    }
}
