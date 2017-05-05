<?php

namespace Illuminate\Auth\Events;

use Illuminate\Queue\SerializesModels;

class Login
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * 经过身份验证的用户
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

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
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function __construct($user, $remember)
    {
        $this->user = $user;
        $this->remember = $remember;
    }
}
