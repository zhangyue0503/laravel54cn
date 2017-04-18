<?php

namespace Illuminate\Foundation;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EnvironmentDetector
{
    /**
     * Detect the application's current environment.
     *
     * 检测应用程序的当前环境
     *
     * @param  \Closure  $callback
     * @param  array|null  $consoleArgs
     * @return string
     */
    public function detect(Closure $callback, $consoleArgs = null)
    {
        if ($consoleArgs) {
            return $this->detectConsoleEnvironment($callback, $consoleArgs); //从命令行参数设置应用程序环境
        }

        return $this->detectWebEnvironment($callback); //设置web请求的应用程序环境
    }

    /**
     * Set the application environment for a web request.
     *
     * 设置web请求的应用程序环境
     *
     * @param  \Closure  $callback
     * @return string
     */
    protected function detectWebEnvironment(Closure $callback)
    {
        return call_user_func($callback);
    }

    /**
     * Set the application environment from command-line arguments.
     *
     * 从命令行参数设置应用程序环境
     *
     * @param  \Closure  $callback
     * @param  array  $args
     * @return string
     */
    protected function detectConsoleEnvironment(Closure $callback, array $args)
    {
        // First we will check if an environment argument was passed via console arguments
        // and if it was that automatically overrides as the environment. Otherwise, we
        // will check the environment as a "web" request like a typical HTTP request.
        //
        // 首先我们将检查一个环境参数是否通过控制台参数传递，如果它是自动覆盖的环境
        // 否则，我们将检查环境作为一个“web”请求，像一个典型的HTTP请求
        //
        if (! is_null($value = $this->getEnvironmentArgument($args))) { //从控制台获取环境参数
            //获取数组的第一个元素,用于方法链接
            return head(array_slice(explode('=', $value), 1));
        }

        return $this->detectWebEnvironment($callback); //设置web请求的应用程序环境
    }

    /**
     * Get the environment argument from the console.
     *
     * 从控制台获取环境参数
     *
     * @param  array  $args
     * @return string|null
     */
    protected function getEnvironmentArgument(array $args)
    {
        return Arr::first($args, function ($value) {
            return Str::startsWith($value, '--env');
        });
    }
}
