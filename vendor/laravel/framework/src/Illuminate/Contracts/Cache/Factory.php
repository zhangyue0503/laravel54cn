<?php

namespace Illuminate\Contracts\Cache;

interface Factory
{
    /**
     * Get a cache store instance by name.
     *
     * 以名称获取缓存存储实例
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function store($name = null);
}
