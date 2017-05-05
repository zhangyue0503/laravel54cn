<?php

namespace Illuminate\Contracts\Auth;

interface Factory
{
    /**
     * Get a guard instance by name.
     *
     * 通过名称获取守护实例
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function guard($name = null);

    /**
     * Set the default guard the factory should serve.
     *
     * 设置工厂应该提供的默认保护
     *
     * @param  string  $name
     * @return void
     */
    public function shouldUse($name);
}
