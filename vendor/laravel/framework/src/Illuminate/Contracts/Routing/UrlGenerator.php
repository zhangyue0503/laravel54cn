<?php

namespace Illuminate\Contracts\Routing;
//Url生成器
interface UrlGenerator
{
    /**
     * Get the current URL for the request.
     *
     * 获取请求的当前URL
     *
     * @return string
     */
    public function current();

    /**
     * Generate an absolute URL to the given path.
     *
     * 为给定的路径生成一个绝对URL
     *
     * @param  string  $path
     * @param  mixed  $extra
     * @param  bool  $secure
     * @return string
     */
    public function to($path, $extra = [], $secure = null);

    /**
     * Generate a secure, absolute URL to the given path.
     *
     * 为给定的路径生成一个安全的、绝对的URL
     *
     * @param  string  $path
     * @param  array   $parameters
     * @return string
     */
    public function secure($path, $parameters = []);

    /**
     * Generate the URL to an application asset.
     *
     * 生成应用程序资产的URL
     *
     * @param  string  $path
     * @param  bool    $secure
     * @return string
     */
    public function asset($path, $secure = null);

    /**
     * Get the URL to a named route.
     *
     * 获取到指定路径的URL
     *
     * @param  string  $name
     * @param  mixed   $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function route($name, $parameters = [], $absolute = true);

    /**
     * Get the URL to a controller action.
     *
     * 获取控制器动作的URL
     *
     * @param  string  $action
     * @param  mixed $parameters
     * @param  bool $absolute
     * @return string
     */
    public function action($action, $parameters = [], $absolute = true);

    /**
     * Set the root controller namespace.
     *
     * 设置根控制器名称空间
     *
     * @param  string  $rootNamespace
     * @return $this
     */
    public function setRootControllerNamespace($rootNamespace);
}
