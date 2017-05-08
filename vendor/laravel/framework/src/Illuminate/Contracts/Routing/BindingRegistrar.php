<?php

namespace Illuminate\Contracts\Routing;

interface BindingRegistrar
{
    /**
     * Add a new route parameter binder.
     *
     * 添加一个新的路由参数绑定器
     *
     * @param  string  $key
     * @param  string|callable  $binder
     * @return void
     */
    public function bind($key, $binder);

    /**
     * Get the binding callback for a given binding.
     *
     * 为给定的绑定获取绑定回调
     *
     * @param  string  $key
     * @return \Closure
     */
    public function getBindingCallback($key);
}
