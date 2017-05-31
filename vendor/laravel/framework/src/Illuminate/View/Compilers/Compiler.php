<?php

namespace Illuminate\View\Compilers;

use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;

abstract class Compiler
{
    /**
     * The Filesystem instance.
     *
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Get the cache path for the compiled views.
     *
     * 获取已编译视图的缓存路径
     *
     * @var string
     */
    protected $cachePath;

    /**
     * Create a new compiler instance.
     *
     * 创建一个新的编译器实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $cachePath
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Filesystem $files, $cachePath)
    {
        if (! $cachePath) {
            throw new InvalidArgumentException('Please provide a valid cache path.');
        }

        $this->files = $files;
        $this->cachePath = $cachePath;
    }

    /**
     * Get the path to the compiled version of a view.
     *
     * 获取到已编译版本的视图的路径
     *
     * @param  string  $path
     * @return string
     */
    public function getCompiledPath($path)
    {
        return $this->cachePath.'/'.sha1($path).'.php';
    }

    /**
     * Determine if the view at the given path is expired.
     *
     * 确定给定路径的视图是否已过期
     *
     * @param  string  $path
     * @return bool
     */
    public function isExpired($path)
    {
        $compiled = $this->getCompiledPath($path);//获取到已编译版本的视图的路径

        // If the compiled file doesn't exist we will indicate that the view is expired
        // so that it can be re-compiled. Else, we will verify the last modification
        // of the views is less than the modification times of the compiled views.
        //
        // 如果编译后的文件不存在，我们将指出视图已过期，以便重新编译
        // 否则，我们将验证视图的最后修改小于已编译视图的修改时间
        //
        //                确定文件或目录是否存在
        if (! $this->files->exists($compiled)) {
            return true;
        }
        //         获取文件的最后修改时间
        return $this->files->lastModified($path) >=
               $this->files->lastModified($compiled);
    }
}
