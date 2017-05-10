<?php

namespace Illuminate\Contracts\Routing;

interface ResponseFactory
{
    /**
     * Return a new response from the application.
     *
     * 从应用程序返回新的响应
     *
     * @param  string  $content
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\Response
     */
    public function make($content = '', $status = 200, array $headers = []);

    /**
     * Return a new view response from the application.
     *
     * 从应用程序返回一个新的视图响应
     *
     * @param  string  $view
     * @param  array  $data
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\Response
     */
    public function view($view, $data = [], $status = 200, array $headers = []);

    /**
     * Return a new JSON response from the application.
     *
     * 从应用程序返回一个新的JSON响应
     *
     * @param  string|array  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Http\JsonResponse
     */
    public function json($data = [], $status = 200, array $headers = [], $options = 0);

    /**
     * Return a new JSONP response from the application.
     *
     * 从应用程序返回一个新的JSONP响应
     *
     * @param  string  $callback
     * @param  string|array  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonp($callback, $data = [], $status = 200, array $headers = [], $options = 0);

    /**
     * Return a new streamed response from the application.
     *
     * 从应用程序返回一个新的流响应
     *
     * @param  \Closure  $callback
     * @param  int  $status
     * @param  array  $headers
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function stream($callback, $status = 200, array $headers = []);

    /**
     * Create a new file download response.
     *
     * 创建一个新的文件下载响应
     *
     * @param  \SplFileInfo|string  $file
     * @param  string  $name
     * @param  array  $headers
     * @param  string|null  $disposition
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($file, $name = null, array $headers = [], $disposition = 'attachment');

    /**
     * Create a new redirect response to the given path.
     *
     * 为给定的路径创建一个新的重定向响应
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectTo($path, $status = 302, $headers = [], $secure = null);

    /**
     * Create a new redirect response to a named route.
     *
     * 为指定的路由创建一个新的重定向响应
     *
     * @param  string  $route
     * @param  array  $parameters
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToRoute($route, $parameters = [], $status = 302, $headers = []);

    /**
     * Create a new redirect response to a controller action.
     *
     * 为控制器动作创建一个新的重定向响应
     *
     * @param  string  $action
     * @param  array  $parameters
     * @param  int  $status
     * @param  array  $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToAction($action, $parameters = [], $status = 302, $headers = []);

    /**
     * Create a new redirect response, while putting the current URL in the session.
     *
     * 创建一个新的重定向响应，同时将当前的URL放在会话中
     *
     * @param  string  $path
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectGuest($path, $status = 302, $headers = [], $secure = null);

    /**
     * Create a new redirect response to the previously intended location.
     *
     * 创建一个新的重定向响应，以响应先前的目标位置
     *
     * @param  string  $default
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToIntended($default = '/', $status = 302, $headers = [], $secure = null);
}
