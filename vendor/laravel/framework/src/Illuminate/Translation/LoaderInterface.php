<?php

namespace Illuminate\Translation;

interface LoaderInterface
{
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
    public function load($locale, $group, $namespace = null);

    /**
     * Add a new namespace to the loader.
     *
     * 向加载程序添加一个新的名称空间
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint);

    /**
     * Get an array of all the registered namespaces.
     *
     * 获取所有已注册名称空间的数组
     *
     * @return array
     */
    public function namespaces();
}
