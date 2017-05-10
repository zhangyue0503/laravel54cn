<?php

namespace Illuminate\Contracts\Filesystem;

interface Cloud extends Filesystem
{
    /**
     * Get the URL for the file at the given path.
     *
     * 获取给定路径上的文件的URL
     *
     * @param  string  $path
     * @return string
     */
    public function url($path);
}
