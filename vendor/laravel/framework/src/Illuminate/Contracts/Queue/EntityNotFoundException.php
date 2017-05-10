<?php

namespace Illuminate\Contracts\Queue;

use InvalidArgumentException;

class EntityNotFoundException extends InvalidArgumentException
{
    /**
     * Create a new exception instance.
     *
     * 创建一个新的异常实例
     *
     * @param  string  $type
     * @param  mixed  $id
     * @return void
     */
    public function __construct($type, $id)
    {
        $id = (string) $id;

        parent::__construct("Queueable entity [{$type}] not found for ID [{$id}].");
    }
}
