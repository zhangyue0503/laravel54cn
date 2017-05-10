<?php

namespace Illuminate\Cookie;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Contracts\Cookie\QueueingFactory as JarContract;

class CookieJar implements JarContract
{
    /**
     * The default path (if specified).
     *
     * 默认路径(如果指定了的话)
     *
     * @var string
     */
    protected $path = '/';

    /**
     * The default domain (if specified).
     *
     * 默认域(如果指定了的话)
     *
     * @var string
     */
    protected $domain;

    /**
     * The default secure setting (defaults to false).
     *
     * 默认的安全设置(默认为false)
     *
     * @var bool
     */
    protected $secure = false;

    /**
     * All of the cookies queued for sending.
     *
     * 所有的cookie都排队等待发送
     *
     * @var array
     */
    protected $queued = [];

    /**
     * Create a new cookie instance.
     *
     * 创建一个新的cookie实例
     *
     * @param  string  $name
     * @param  string  $value
     * @param  int     $minutes
     * @param  string  $path
     * @param  string  $domain
     * @param  bool    $secure
     * @param  bool    $httpOnly
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function make($name, $value, $minutes = 0, $path = null, $domain = null, $secure = false, $httpOnly = true)
    {
        //                              获取路径和域，或者默认值
        list($path, $domain, $secure) = $this->getPathAndDomain($path, $domain, $secure);

        //                                获取当前日期和时间的Carbon实例
        $time = ($minutes == 0) ? 0 : Carbon::now()->getTimestamp() + ($minutes * 60);

        return new Cookie($name, $value, $time, $path, $domain, $secure, $httpOnly);
    }

    /**
     * Create a cookie that lasts "forever" (five years).
     *
     * 创建一个cookie,持续“永远”(五年)
     *
     * @param  string  $name
     * @param  string  $value
     * @param  string  $path
     * @param  string  $domain
     * @param  bool    $secure
     * @param  bool    $httpOnly
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function forever($name, $value, $path = null, $domain = null, $secure = false, $httpOnly = true)
    {
        //创建一个新的cookie实例
        return $this->make($name, $value, 2628000, $path, $domain, $secure, $httpOnly);
    }

    /**
     * Expire the given cookie.
     *
     * 过期的cookie
     *
     * @param  string  $name
     * @param  string  $path
     * @param  string  $domain
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function forget($name, $path = null, $domain = null)
    {
        //创建一个新的cookie实例
        return $this->make($name, null, -2628000, $path, $domain);
    }

    /**
     * Determine if a cookie has been queued.
     *
     * 确定一个cookie是否已被排队
     *
     * @param  string  $key
     * @return bool
     */
    public function hasQueued($key)
    {
        //获取一个排队的cookie实例
        return ! is_null($this->queued($key));
    }

    /**
     * Get a queued cookie instance.
     *
     * 获取一个排队的cookie实例
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function queued($key, $default = null)
    {
        //使用“点”符号从数组中获取一个项
        return Arr::get($this->queued, $key, $default);
    }

    /**
     * Queue a cookie to send with the next response.
     *
     * 用下一个响应发送一个cookie来发送
     *
     * @param  array  $parameters
     * @return void
     */
    public function queue(...$parameters)
    {
        //获取数组的第一个元素
        if (head($parameters) instanceof Cookie) {
            $cookie = head($parameters);
        } else {
            $cookie = call_user_func_array([$this, 'make'], $parameters);
        }

        $this->queued[$cookie->getName()] = $cookie;
    }

    /**
     * Remove a cookie from the queue.
     *
     * 从队列中删除cookie
     *
     * @param  string  $name
     * @return void
     */
    public function unqueue($name)
    {
        unset($this->queued[$name]);
    }

    /**
     * Get the path and domain, or the default values.
     *
     * 获取路径和域，或者默认值
     *
     * @param  string  $path
     * @param  string  $domain
     * @param  bool    $secure
     * @return array
     */
    protected function getPathAndDomain($path, $domain, $secure = false)
    {
        return [$path ?: $this->path, $domain ?: $this->domain, $secure ?: $this->secure];
    }

    /**
     * Set the default path and domain for the jar.
     *
     * 设置jar的默认路径和域
     *
     * @param  string  $path
     * @param  string  $domain
     * @param  bool    $secure
     * @return $this
     */
    public function setDefaultPathAndDomain($path, $domain, $secure = false)
    {
        list($this->path, $this->domain, $this->secure) = [$path, $domain, $secure];

        return $this;
    }

    /**
     * Get the cookies which have been queued for the next request.
     *
     * 获取已为下一个请求排队的cookie
     *
     * @return array
     */
    public function getQueuedCookies()
    {
        return $this->queued;
    }
}
