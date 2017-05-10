<?php

namespace Illuminate\Contracts\Cookie;

interface Factory
{
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
    public function make($name, $value, $minutes = 0, $path = null, $domain = null, $secure = false, $httpOnly = true);

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
    public function forever($name, $value, $path = null, $domain = null, $secure = false, $httpOnly = true);

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
    public function forget($name, $path = null, $domain = null);
}
