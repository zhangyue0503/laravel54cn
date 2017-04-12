<?php

namespace Illuminate\Contracts\Support;

interface Jsonable
{
    /**
     * Convert the object to its JSON representation.
     *
     * 将对象转换为JSON表示形式
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0);
}
