<?php

namespace Illuminate\Translation;

use Illuminate\Filesystem\Filesystem;

class FileLoader implements LoaderInterface
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
     * The default path for the loader.
     *
     * 加载程序的默认路径
     *
     * @var string
     */
    protected $path;

    /**
     * All of the namespace hints.
     *
     * 所有的名称空间提示
     *
     * @var array
     */
    protected $hints = [];

    /**
     * Create a new file loader instance.
     *
     * 创建一个新的文件加载器实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $path
     * @return void
     */
    public function __construct(Filesystem $files, $path)
    {
        $this->path = $path;
        $this->files = $files;
    }

    /**
     * Load the messages for the given locale.
     *
     * 为给定的地区加载消息
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        if ($group == '*' && $namespace == '*') {
            //          从给定的JSON文件路径加载一个地区
            return $this->loadJsonPath($this->path, $locale);
        }

        if (is_null($namespace) || $namespace == '*') {
            //从给定路径加载区域设置
            return $this->loadPath($this->path, $locale, $group);
        }
        //        加载一个名称空间的翻译组
        return $this->loadNamespaced($locale, $group, $namespace);
    }

    /**
     * Load a namespaced translation group.
     *
     * 加载一个名称空间的翻译组
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    protected function loadNamespaced($locale, $group, $namespace)
    {
        if (isset($this->hints[$namespace])) {
            //从给定路径加载区域设置
            $lines = $this->loadPath($this->hints[$namespace], $locale, $group);
            //加载一个本地名称空间的翻译组，用于覆盖
            return $this->loadNamespaceOverrides($lines, $locale, $group, $namespace);
        }

        return [];
    }

    /**
     * Load a local namespaced translation group for overrides.
     *
     * 加载一个本地名称空间的翻译组，用于覆盖
     *
     * @param  array  $lines
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    protected function loadNamespaceOverrides(array $lines, $locale, $group, $namespace)
    {
        $file = "{$this->path}/vendor/{$namespace}/{$locale}/{$group}.php";
        //确定文件或目录是否存在
        if ($this->files->exists($file)) {
            //                                           获取文件的返回值
            return array_replace_recursive($lines, $this->files->getRequire($file));
        }

        return $lines;
    }

    /**
     * Load a locale from a given path.
     *
     * 从给定路径加载区域设置
     *
     * @param  string  $path
     * @param  string  $locale
     * @param  string  $group
     * @return array
     */
    protected function loadPath($path, $locale, $group)
    {
        //确定文件或目录是否存在
        if ($this->files->exists($full = "{$path}/{$locale}/{$group}.php")) {
            //            获取文件的返回值
            return $this->files->getRequire($full);
        }

        return [];
    }

    /**
     * Load a locale from the given JSON file path.
     *
     * 从给定的JSON文件路径加载一个地区
     *
     * @param  string  $path
     * @param  string  $locale
     * @return array
     */
    protected function loadJsonPath($path, $locale)
    {
        //确定文件或目录是否存在
        if ($this->files->exists($full = "{$path}/{$locale}.json")) {
            //                          获取文件的内容
            return json_decode($this->files->get($full), true);
        }

        return [];
    }

    /**
     * Add a new namespace to the loader.
     *
     * 向加载程序添加一个新的名称空间
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $this->hints[$namespace] = $hint;
    }

    /**
     * Get an array of all the registered namespaces.
     *
     * 获取所有已注册名称空间的数组
     *
     * @return array
     */
    public function namespaces()
    {
        return $this->hints;
    }
}
