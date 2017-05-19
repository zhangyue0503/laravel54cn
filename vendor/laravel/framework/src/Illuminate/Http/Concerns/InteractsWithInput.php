<?php

namespace Illuminate\Http\Concerns;

use SplFileInfo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
//与输入交互
trait InteractsWithInput
{
    /**
     * Retrieve a server variable from the request.
     *
     * 从请求中检索服务器变量
     *
     * @param  string  $key
     * @param  string|array|null  $default
     * @return string|array
     */
    public function server($key = null, $default = null)
    {
        // 返回 从server源检索参数项
        return $this->retrieveItem('server', $key, $default);
    }

    /**
     * Determine if a header is set on the request.
     *
     * 确定是否在请求上设置标头
     *
     * @param  string  $key
     * @return bool
     */
    public function hasHeader($key)
    {
        //                   从请求中检索标头
        return ! is_null($this->header($key));
    }

    /**
     * Retrieve a header from the request.
     *
     * 从请求中检索标头
     *
     * @param  string  $key
     * @param  string|array|null  $default
     * @return string|array
     */
    public function header($key = null, $default = null)
    {
        // 返回 从headers源检索参数项
        return $this->retrieveItem('headers', $key, $default);
    }

    /**
     * Get the bearer token from the request headers.
     *
     * 从请求标头获取承载令牌
     *
     * @return string|null
     */
    public function bearerToken()
    {
        //              从请求中检索标头
        $header = $this->header('Authorization', '');
        //确定给定的子字符串是否属于给定的字符串
        if (Str::startsWith($header, 'Bearer ')) {
            //返回由开始和长度参数指定的字符串的一部分
            return Str::substr($header, 7);
        }
    }

    /**
     * Determine if the request contains a given input item key.
     *
     * 确定请求是否包含给定的输入项键
     *
     * @param  string|array  $key
     * @return bool
     */
    public function exists($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        //获取请求的所有输入和文件
        $input = $this->all();

        foreach ($keys as $value) {
            //使用“点”符号检查数组中的项或项是否存在
            if (! Arr::has($input, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the request contains a non-empty value for an input item.
     *
     * 确定请求中是否包含的非空值的输入项
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            //确定给定的输入键是否为“has”的空字符串
            if ($this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the given input key is an empty string for "has".
     *
     * 确定给定的输入键是否为“has”的空字符串
     *
     * @param  string  $key
     * @return bool
     */
    protected function isEmptyString($key)
    {
        //从请求中检索输入项
        $value = $this->input($key);

        return ! is_bool($value) && ! is_array($value) && trim((string) $value) === '';
    }

    /**
     * Get all of the input and files for the request.
     *
     * 获取请求的所有输入和文件
     *
     * @return array
     */
    public function all()
    {
        //递归地使用第二个数组（$a2）的值替换第一个数组（$a1）的值    从请求中检索输入项      获取请求上所有文件的数组
        return array_replace_recursive($this->input(), $this->allFiles());
    }

    /**
     * Retrieve an input item from the request.
     *
     * 从请求中检索输入项
     *
     * @param  string  $key
     * @param  string|array|null  $default
     * @return string|array
     */
    public function input($key = null, $default = null)
    {
        // 使用“点”符号从数组或对象中获取项
        return data_get(
            //获取请求的输入源
            $this->getInputSource()->all() + $this->query->all(), $key, $default
        );
    }

    /**
     * Get a subset containing the provided keys with values from the input data.
     *
     * 获取包含来自输入数据的值的所提供键的子集
     *
     * @param  array|mixed  $keys
     * @return array
     */
    public function only($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = [];
        //获取请求的所有输入和文件
        $input = $this->all();

        foreach ($keys as $key) {
            //如果没有给定key的方法，整个数组将被替换     使用“点”符号从数组或对象中获取项
            Arr::set($results, $key, data_get($input, $key));
        }

        return $results;
    }

    /**
     * Get all of the input except for a specified array of items.
     *
     * 获取除指定数组项之外的所有输入
     *
     * @param  array|mixed  $keys
     * @return array
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        //获取请求的所有输入和文件
        $results = $this->all();
        //使用“点”符号从给定数组中移除一个或多个数组项
        Arr::forget($results, $keys);

        return $results;
    }

    /**
     * Intersect an array of items with the input data.
     *
     * 与输入数据相交的项目数组
     *
     * @param  array|mixed  $keys
     * @return array
     */
    public function intersect($keys)
    {
        //                      获取包含来自输入数据的值的所提供键的子集
        return array_filter($this->only(is_array($keys) ? $keys : func_get_args()));
    }

    /**
     * Retrieve a query string item from the request.
     *
     * 从请求中检索查询字符串项
     *
     * @param  string  $key
     * @param  string|array|null  $default
     * @return string|array
     */
    public function query($key = null, $default = null)
    {
        // 返回 从query源检索参数项
        return $this->retrieveItem('query', $key, $default);
    }

    /**
     * Determine if a cookie is set on the request.
     *
     * 确定是否在请求上设置cookie
     *
     * @param  string  $key
     * @return bool
     */
    public function hasCookie($key)
    {
        //                检索从请求来的cookie
        return ! is_null($this->cookie($key));
    }

    /**
     * Retrieve a cookie from the request.
     *
     * 检索从请求来的cookie
     *
     * @param  string  $key
     * @param  string|array|null  $default
     * @return string|array
     */
    public function cookie($key = null, $default = null)
    {
        // 返回 从cookies源检索参数项
        return $this->retrieveItem('cookies', $key, $default);
    }

    /**
     * Get an array of all of the files on the request.
     *
     * 获取请求上所有文件的数组
     *
     * @return array
     */
    public function allFiles()
    {
        $files = $this->files->all();
        //请求的所有转换文件 : 请求的所有转换文件 : 转换给定的数组从symfony 上传文件到Laravel 上传文件
        return $this->convertedFiles
                    ? $this->convertedFiles
            //                                 转换给定的数组从symfony 上传文件到Laravel 上传文件
                    : $this->convertedFiles = $this->convertUploadedFiles($files);
    }

    /**
     * Convert the given array of Symfony UploadedFiles to custom Laravel UploadedFiles.
     *
     * 转换给定的数组从symfony 上传文件到Laravel 上传文件
     *
     * @param  array  $files
     * @return array
     */
    protected function convertUploadedFiles(array $files)
    {
        return array_map(function ($file) {
            if (is_null($file) || (is_array($file) && empty(array_filter($file)))) {
                return $file;
            }
            //返回  文件数组 ? 转换文件 : 从基础实例创建新的文件实例
            return is_array($file)
            //                 转换给定的数组从symfony 上传文件到Laravel 上传文件
                        ? $this->convertUploadedFiles($file)
                //            从基础实例创建新的文件实例
                        : UploadedFile::createFromBase($file);
        }, $files);
    }

    /**
     * Determine if the uploaded data contains a file.
     *
     * 确定数据是否包含一个文件上传
     *
     * @param  string  $key
     * @return bool
     */
    public function hasFile($key)
    {
        //                          从请求中检索文件
        if (! is_array($files = $this->file($key))) {
            $files = [$files];
        }

        foreach ($files as $file) {
            // 检查给定文件是否是有效的文件实例
            if ($this->isValidFile($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check that the given file is a valid file instance.
     *
     * 检查给定文件是否是有效的文件实例
     *
     * @param  mixed  $file
     * @return bool
     */
    protected function isValidFile($file)
    {
        return $file instanceof SplFileInfo && $file->getPath() != '';
    }

    /**
     * Retrieve a file from the request.
     *
     * 从请求中检索文件
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return \Illuminate\Http\UploadedFile|array|null
     */
    public function file($key = null, $default = null)
    {
        //                取请求上所有文件的数组
        return data_get($this->allFiles(), $key, $default);
    }

    /**
     * Retrieve a parameter item from a given source.
     *
     * 从给定源检索参数项
     *
     * @param  string  $source
     * @param  string  $key
     * @param  string|array|null  $default
     * @return string|array
     */
    protected function retrieveItem($source, $key, $default)
    {
        if (is_null($key)) {
            return $this->$source->all();
        }

        return $this->$source->get($key, $default);
    }
}
