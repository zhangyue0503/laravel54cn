<?php

namespace Illuminate\Contracts\Routing;

interface UrlRoutable
{
    /**
     * Get the value of the model's route key.
     *
     * 获取模型路由键的值
     *
     * @return mixed
     */
    public function getRouteKey();

    /**
     * Get the route key for the model.
     *
     * 从模型中获取路由键值
     *
     * @return string
     */
    public function getRouteKeyName();
}
