<?php

namespace Illuminate\Http;

use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;

trait ResponseTrait
{
    /**
     * The original content of the response.
     *
     * 响应的原始内容
     *
     * @var mixed
     */
    public $original;

    /**
     * The exception that triggered the error response (if applicable).
     *
     * 触发错误响应的异常（如果适用）
     *
     * @var \Exception|null
     */
    public $exception;

    /**
     * Get the status code for the response.
     *
     * 获取响应的状态代码
     *
     * @return int
     */
    public function status()
    {
        return $this->getStatusCode(); //检索当前web响应的状态代码
    }

    /**
     * Get the content of the response.
     *
     * 得到响应的内容
     *
     * @return string
     */
    public function content()
    {
        return $this->getContent(); //获取当前的响应内容
    }

    /**
     * Get the original response content.
     *
     * 获取原始响应内容
     *
     * @return mixed
     */
    public function getOriginalContent()
    {
        return $this->original;
    }

    /**
     * Set a header on the Response.
	 *
	 * 设置响应头
     *
     * @param  string  $key
     * @param  array|string  $values
     * @param  bool    $replace
     * @return $this
     */
    public function header($key, $values, $replace = true)
    {
        $this->headers->set($key, $values, $replace); //根据名称设置头

        return $this;
    }

    /**
     * Add an array of headers to the response.
     *
     * 将标头数组添加到响应
     *
     * @param  array  $headers
     * @return $this
     */
    public function withHeaders(array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->headers->set($key, $value); //根据名称设置头
        }

        return $this;
    }

    /**
     * Add a cookie to the response.
     *
     * 在响应中添加cookie
     *
     * @param  \Symfony\Component\HttpFoundation\Cookie|mixed  $cookie
     * @return $this
     */
    public function cookie($cookie)
    {
        return call_user_func_array([$this, 'withCookie'], func_get_args());
    }

    /**
     * Add a cookie to the response.
     *
     * 在响应中添加cookie
     *
     * @param  \Symfony\Component\HttpFoundation\Cookie|mixed  $cookie
     * @return $this
     */
    public function withCookie($cookie)
    {
        if (is_string($cookie) && function_exists('cookie')) {
            $cookie = call_user_func_array('cookie', func_get_args());
        }

        $this->headers->setCookie($cookie); //设置cookie

        return $this;
    }

    /**
     * Set the exception to attach to the response.
     *
     * 设置附加到响应的异常
     *
     * @param  \Exception  $e
     * @return $this
     */
    public function withException(Exception $e)
    {
        $this->exception = $e;

        return $this;
    }

    /**
     * Throws the response in a HttpResponseException instance.
     *
     * 抛出响应的HttpResponseException实例异常
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public function throwResponse()
    {
        throw new HttpResponseException($this);
    }
}
