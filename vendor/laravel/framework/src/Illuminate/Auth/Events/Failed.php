<?php

namespace Illuminate\Auth\Events;

class Failed
{
    /**
     * The user the attempter was trying to authenticate as.
     *
     * 尝试者试图对其进行身份验证
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public $user;

    /**
     * The credentials provided by the attempter.
     *
     * 尝试者提供的凭据
     *
     * @var array
     */
    public $credentials;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $credentials
     */
    public function __construct($user, $credentials)
    {
        $this->user = $user;
        $this->credentials = $credentials;
    }
}
