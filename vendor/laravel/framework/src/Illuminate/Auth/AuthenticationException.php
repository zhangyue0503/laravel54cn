<?php

namespace Illuminate\Auth;

use Exception;

class AuthenticationException extends Exception
{
    /**
     * All of the guards that were checked.
     *
     * 所有被检查的警卫
     *
     * @var array
     */
    protected $guards;

    /**
     * Create a new authentication exception.
     *
     * 创建一个新的身份验证异常
     *
     * @param  string  $message
     * @param  array  $guards
     * @return void
     */
    public function __construct($message = 'Unauthenticated.', array $guards = [])
    {
        parent::__construct($message);

        $this->guards = $guards;
    }

    /**
     * Get the guards that were checked.
     *
     * 检查检查过的警卫
     *
     * @return array
     */
    public function guards()
    {
        return $this->guards;
    }
}
