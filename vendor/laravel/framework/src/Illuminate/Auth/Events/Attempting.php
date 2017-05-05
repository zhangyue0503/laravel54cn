<?php

namespace Illuminate\Auth\Events;

class Attempting
{
    /**
     * The credentials for the user.
     *
     * 用户的凭证
     *
     * @var array
     */
    public $credentials;

    /**
     * Indicates if the user should be "remembered".
     *
     * 表示用户是否应该被“记住”
     *
     * @var bool
     */
    public $remember;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  array  $credentials
     * @param  bool  $remember
     */
    public function __construct($credentials, $remember)
    {
        $this->remember = $remember;
        $this->credentials = $credentials;
    }
}
