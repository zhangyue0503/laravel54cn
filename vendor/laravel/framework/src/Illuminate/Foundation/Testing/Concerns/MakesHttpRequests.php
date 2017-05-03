<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

trait MakesHttpRequests
{
    /**
     * Additional server variables for the request.
     *
     * 请求的附加服务器变量
     *
     * @var array
     */
    protected $serverVariables = [];

    /**
     * Define a set of server variables to be sent with the requests.
     *
     * 定义要用请求发送的一组服务器变量
     *
     * @param  array  $server
     * @return $this
     */
    protected function withServerVariables(array $server)
    {
        $this->serverVariables = $server;

        return $this;
    }

    /**
     * Disable middleware for the test.
     *
     * 为测试禁用中间件
     *
     * @return $this
     */
    public function withoutMiddleware()
    {
        //在容器中注册一个已存在的实例
        $this->app->instance('middleware.disable', true);

        return $this;
    }

    /**
     * Visit the given URI with a GET request.
     *
     * 使用GET请求访问给定的URI
     *
     * @param  string  $uri
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function get($uri, array $headers = [])
    {
        //              转换头数组,数组$ _SERVER var的HTTP_ *格式
        $server = $this->transformHeadersToServerVars($headers);
        //调用给定的URI并返回响应
        return $this->call('GET', $uri, [], [], [], $server);
    }

    /**
     * Visit the given URI with a GET request, expecting a JSON response.
     *
     * 使用GET请求访问给定的URI，期望得到JSON响应
     *
     * @param  string  $uri
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function getJson($uri, array $headers = [])
    {
        //使用JSON请求调用给定的URI
        return $this->json('GET', $uri, [], $headers);
    }

    /**
     * Visit the given URI with a POST request.
     *
     * 使用POST请求访问给定的URI
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function post($uri, array $data = [], array $headers = [])
    {
        //              转换头数组,数组$ _SERVER var的HTTP_ *格式
        $server = $this->transformHeadersToServerVars($headers);
        //调用给定的URI并返回响应
        return $this->call('POST', $uri, $data, [], [], $server);
    }

    /**
     * Visit the given URI with a POST request, expecting a JSON response.
     *
     * 使用POST请求访问给定的URI，期望得到JSON响应
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function postJson($uri, array $data = [], array $headers = [])
    {
        //使用JSON请求调用给定的URI
        return $this->json('POST', $uri, $data, $headers);
    }

    /**
     * Visit the given URI with a PUT request.
     *
     * 使用PUT请求访问给定的URI
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function put($uri, array $data = [], array $headers = [])
    {
        //              转换头数组,数组$ _SERVER var的HTTP_ *格式
        $server = $this->transformHeadersToServerVars($headers);
        //调用给定的URI并返回响应
        return $this->call('PUT', $uri, $data, [], [], $server);
    }

    /**
     * Visit the given URI with a PUT request, expecting a JSON response.
     *
     * 使用PUT请求访问给定的URI，期望得到JSON响应
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function putJson($uri, array $data = [], array $headers = [])
    {
        //使用JSON请求调用给定的URI
        return $this->json('PUT', $uri, $data, $headers);
    }

    /**
     * Visit the given URI with a PATCH request.
     *
     * 使用PATCH请求访问给定的URI
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function patch($uri, array $data = [], array $headers = [])
    {
        //              转换头数组,数组$ _SERVER var的HTTP_ *格式
        $server = $this->transformHeadersToServerVars($headers);
        //调用给定的URI并返回响应
        return $this->call('PATCH', $uri, $data, [], [], $server);
    }

    /**
     * Visit the given URI with a PATCH request, expecting a JSON response.
     *
     * 使用PATCH请求访问给定的URI，期望得到JSON响应
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function patchJson($uri, array $data = [], array $headers = [])
    {
        //使用JSON请求调用给定的URI
        return $this->json('PATCH', $uri, $data, $headers);
    }

    /**
     * Visit the given URI with a DELETE request.
     *
     * 使用DELETE请求访问给定的URI
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function delete($uri, array $data = [], array $headers = [])
    {
        //              转换头数组,数组$ _SERVER var的HTTP_ *格式
        $server = $this->transformHeadersToServerVars($headers);
        //调用给定的URI并返回响应
        return $this->call('DELETE', $uri, $data, [], [], $server);
    }

    /**
     * Visit the given URI with a DELETE request, expecting a JSON response.
     *
     * 使用DELETE请求访问给定的URI，期望得到JSON响应
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function deleteJson($uri, array $data = [], array $headers = [])
    {
        //使用JSON请求调用给定的URI
        return $this->json('DELETE', $uri, $data, $headers);
    }

    /**
     * Call the given URI with a JSON request.
     *
     * 使用JSON请求调用给定的URI
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function json($method, $uri, array $data = [], array $headers = [])
    {
        //              从给定的数据数组中提取文件上传
        $files = $this->extractFilesFromDataArray($data);

        $content = json_encode($data);

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);
        //调用给定的URI并返回响应
        return $this->call(
            //                                  转换头数组,数组$ _SERVER var的HTTP_ *格式
            $method, $uri, [], [], $files, $this->transformHeadersToServerVars($headers), $content
        );
    }

    /**
     * Call the given URI and return the Response.
     *
     * 调用给定的URI并返回响应
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $server
     * @param  string  $content
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $kernel = $this->app->make(HttpKernel::class); //从容器中解析给定类型
        //                               从给定的数据数组中提取文件上传
        $files = array_merge($files, $this->extractFilesFromDataArray($parameters));
        //根据给定的URI和配置创建请求
        $symfonyRequest = SymfonyRequest::create(
            //将给定的URI转换为完全限定的URL
            $this->prepareUrlForRequest($uri), $method, $parameters,
            $cookies, $files, array_replace($this->serverVariables, $server), $content
        );
        //处理传入的HTTP请求
        $response = $kernel->handle(
            //             从symfony实例创建一个Illuminate请求
            $request = Request::createFromBase($symfonyRequest)
        );
        //为请求生命周期执行任何最终操作
        $kernel->terminate($request, $response);
        //从给定的响应中创建测试响应实例
        return $this->createTestResponse($response);
    }

    /**
     * Turn the given URI into a fully qualified URL.
     *
     * 将给定的URI转换为完全限定的URL
     *
     * @param  string  $uri
     * @return string
     */
    protected function prepareUrlForRequest($uri)
    {
        //确定给定的子字符串是否属于给定的字符串
        if (Str::startsWith($uri, '/')) {
            $uri = substr($uri, 1);
        }
        //确定给定的子字符串是否属于给定的字符串
        if (! Str::startsWith($uri, 'http')) {
            $uri = config('app.url').'/'.$uri;
        }

        return trim($uri, '/');
    }

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     *
     * 转换头数组,数组$ _SERVER var的HTTP_ *格式
     *
     * @param  array  $headers
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        //                          回调应该返回一个具有单个 键/值 对的关联数组
        return collect($headers)->mapWithKeys(function ($value, $name) {
            $name = strtr(strtoupper($name), '-', '_');
            //                 格式化服务器阵列的头名
            return [$this->formatServerHeaderKey($name) => $value];
        })->all();//获取集合中的所有项目
    }

    /**
     * Format the header name for the server array.
     *
     * 格式化服务器阵列的头名
     *
     * @param  string  $name
     * @return string
     */
    protected function formatServerHeaderKey($name)
    {
        //确定给定的子字符串是否属于给定的字符串
        if (! Str::startsWith($name, 'HTTP_') && $name != 'CONTENT_TYPE') {
            return 'HTTP_'.$name;
        }

        return $name;
    }

    /**
     * Extract the file uploads from the given data array.
     *
     * 从给定的数据数组中提取文件上传
     *
     * @param  array  $data
     * @return array
     */
    protected function extractFilesFromDataArray(&$data)
    {
        $files = [];

        foreach ($data as $key => $value) {
            if ($value instanceof SymfonyUploadedFile) {
                $files[$key] = $value;

                unset($data[$key]);
            }
        }

        return $files;
    }

    /**
     * Create the test response instance from the given response.
     *
     * 从给定的响应中创建测试响应实例
     *
     * @param  \Illuminate\Http\Response  $response
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function createTestResponse($response)
    {
        //             从另一个响应创建一个新的TestResponse
        return TestResponse::fromBaseResponse($response);
    }
}
