<?php

namespace Illuminate\Contracts\Filesystem;

interface Factory
{
    /**
     * Get a filesystem implementation.
     *
     * 获得文件系统实现
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function disk($name = null);
}
