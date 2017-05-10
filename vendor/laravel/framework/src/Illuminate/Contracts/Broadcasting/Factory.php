<?php

namespace Illuminate\Contracts\Broadcasting;

interface Factory
{
    /**
     * Get a broadcaster implementation by name.
     *
     * 通过名称获得广播实现
     *
     * @param  string  $name
     * @return void
     */
    public function connection($name = null);
}
