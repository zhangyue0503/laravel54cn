<?php

namespace Illuminate\View\Compilers;

interface CompilerInterface
{
    /**
     * Get the path to the compiled version of a view.
     *
     * 获取到已编译版本的视图的路径
     *
     * @param  string  $path
     * @return string
     */
    public function getCompiledPath($path);

    /**
     * Determine if the given view is expired.
     *
     * 确定给定的视图是否已过期
     *
     * @param  string  $path
     * @return bool
     */
    public function isExpired($path);

    /**
     * Compile the view at the given path.
     *
     * 在给定的路径上编译视图
     *
     * @param  string  $path
     * @return void
     */
    public function compile($path);
}
