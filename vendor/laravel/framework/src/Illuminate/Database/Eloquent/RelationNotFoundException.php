<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class RelationNotFoundException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * 创建一个新的异常实例
     *
     * @param  mixed  $model
     * @param  string  $relation
     * @return static
     */
    public static function make($model, $relation)
    {
        $class = get_class($model);

        return new static("Call to undefined relationship [{$relation}] on model [{$class}].");
    }
}
