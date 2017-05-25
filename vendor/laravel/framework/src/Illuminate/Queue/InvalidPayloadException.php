<?php

namespace Illuminate\Queue;

use InvalidArgumentException;

class InvalidPayloadException extends InvalidArgumentException
{
    /**
     * Create a new exception instance.
     *
     * 创建一个新的异常实例
     *
     * @param  string|null  $message
     * @return void
     */
    public function __construct($message = null)
    {
        parent::__construct($message ?: json_last_error());
    }
}
