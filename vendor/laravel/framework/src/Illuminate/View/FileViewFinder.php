<?php

namespace Illuminate\View;

use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;

class FileViewFinder implements ViewFinderInterface
{
    /**
     * The filesystem instance.
     *
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The array of active view paths.
     *
     * 活动视图路径的数组
     *
     * @var array
     */
    protected $paths;

    /**
     * The array of views that have been located.
     *
     * 已经找到的视图数组
     *
     * @var array
     */
    protected $views = [];

    /**
     * The namespace to file path hints.
     *
     * 文件路径提示的名称空间
     *
     * @var array
     */
    protected $hints = [];

    /**
     * Register a view extension with the finder.
     *
     * 用finder注册一个视图扩展名
     *
     * @var array
     */
    protected $extensions = ['blade.php', 'php', 'css'];

    /**
     * Create a new file view loader instance.
     *
     * 创建一个新的文件视图加载器实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  array  $paths
     * @param  array  $extensions
     * @return void
     */
    public function __construct(Filesystem $files, array $paths, array $extensions = null)
    {
        $this->files = $files;
        $this->paths = $paths;

        if (isset($extensions)) {
            $this->extensions = $extensions;
        }
    }

    /**
     * Get the fully qualified location of the view.
     *
     * 获得视图的完全限定位置
     *
     * @param  string  $name
     * @return string
     */
    public function find($name)
    {
        if (isset($this->views[$name])) {
            return $this->views[$name];
        }
        //返回视图名是否有任何提示信息
        if ($this->hasHintInformation($name = trim($name))) {
            //                          使用指定路径获取路径到模板
            return $this->views[$name] = $this->findNamespacedView($name);
        }
        //                          在路径列表中找到给定的视图
        return $this->views[$name] = $this->findInPaths($name, $this->paths);
    }

    /**
     * Get the path to a template with a named path.
     *
     * 使用指定路径获取路径到模板
     *
     * @param  string  $name
     * @return string
     */
    protected function findNamespacedView($name)
    {
        //                    获取带有指定路径的模板段
        list($namespace, $view) = $this->parseNamespaceSegments($name);
        //在路径列表中找到给定的视图
        return $this->findInPaths($view, $this->hints[$namespace]);
    }

    /**
     * Get the segments of a template with a named path.
     *
     * 获取带有指定路径的模板段
     *
     * @param  string  $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseNamespaceSegments($name)
    {
        $segments = explode(static::HINT_PATH_DELIMITER, $name);

        if (count($segments) != 2) {
            throw new InvalidArgumentException("View [$name] has an invalid name.");
        }

        if (! isset($this->hints[$segments[0]])) {
            throw new InvalidArgumentException("No hint path defined for [{$segments[0]}].");
        }

        return $segments;
    }

    /**
     * Find the given view in the list of paths.
     *
     * 在路径列表中找到给定的视图
     *
     * @param  string  $name
     * @param  array   $paths
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function findInPaths($name, $paths)
    {
        foreach ((array) $paths as $path) {
            //获取一组可能的视图文件
            foreach ($this->getPossibleViewFiles($name) as $file) {
                //确定文件或目录是否存在
                if ($this->files->exists($viewPath = $path.'/'.$file)) {
                    return $viewPath;
                }
            }
        }

        throw new InvalidArgumentException("View [$name] not found.");
    }

    /**
     * Get an array of possible view files.
     *
     * 获取一组可能的视图文件
     *
     * @param  string  $name
     * @return array
     */
    protected function getPossibleViewFiles($name)
    {
        return array_map(function ($extension) use ($name) {
            return str_replace('.', '/', $name).'.'.$extension;
        }, $this->extensions);
    }

    /**
     * Add a location to the finder.
     *
     * 在finder中添加一个位置
     *
     * @param  string  $location
     * @return void
     */
    public function addLocation($location)
    {
        $this->paths[] = $location;
    }

    /**
     * Prepend a location to the finder.
     *
     * 为查找器预先准备一个位置
     *
     * @param  string  $location
     * @return void
     */
    public function prependLocation($location)
    {
        array_unshift($this->paths, $location);
    }

    /**
     * Add a namespace hint to the finder.
     *
     * 向目录添加命名空间提示
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function addNamespace($namespace, $hints)
    {
        $hints = (array) $hints;

        if (isset($this->hints[$namespace])) {
            $hints = array_merge($this->hints[$namespace], $hints);
        }

        $this->hints[$namespace] = $hints;
    }

    /**
     * Prepend a namespace hint to the finder.
     *
     * 为查找器预先准备一个名称空间提示
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function prependNamespace($namespace, $hints)
    {
        $hints = (array) $hints;

        if (isset($this->hints[$namespace])) {
            $hints = array_merge($hints, $this->hints[$namespace]);
        }

        $this->hints[$namespace] = $hints;
    }

    /**
     * Replace the namespace hints for the given namespace.
     *
     * 替换给定名称空间的名称空间提示
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return void
     */
    public function replaceNamespace($namespace, $hints)
    {
        $this->hints[$namespace] = (array) $hints;
    }

    /**
     * Register an extension with the view finder.
     *
     * 用查看器注册一个扩展名
     *
     * @param  string  $extension
     * @return void
     */
    public function addExtension($extension)
    {
        if (($index = array_search($extension, $this->extensions)) !== false) {
            unset($this->extensions[$index]);
        }

        array_unshift($this->extensions, $extension);
    }

    /**
     * Returns whether or not the view name has any hint information.
     *
     * 返回视图名是否有任何提示信息
     *
     * @param  string  $name
     * @return bool
     */
    public function hasHintInformation($name)
    {
        return strpos($name, static::HINT_PATH_DELIMITER) > 0;
    }

    /**
     * Flush the cache of located views.
     *
     * 刷新位置视图的缓存
     *
     * @return void
     */
    public function flush()
    {
        $this->views = [];
    }

    /**
     * Get the filesystem instance.
     *
     * 获得文件系统实例
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

    /**
     * Get the active view paths.
     *
     * 获取活动视图路径
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Get the namespace to file path hints.
     *
     * 获取名称空间到文件路径提示
     *
     * @return array
     */
    public function getHints()
    {
        return $this->hints;
    }

    /**
     * Get registered extensions.
     *
     * 获得注册扩展
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }
}
