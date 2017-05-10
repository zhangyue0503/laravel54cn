<?php

namespace Illuminate\Cookie\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class EncryptCookies
{
    /**
     * The encrypter instance.
     *
     * 加密实例
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The names of the cookies that should not be encrypted.
     *
     * 不应该加密的cookie的名称
     *
     * @var array
     */
    protected $except = [];

    /**
     * Create a new CookieGuard instance.
     *
     * 创建一个新的CookieGuard实例
     *
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @return void
     */
    public function __construct(EncrypterContract $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    /**
     * Disable encryption for the given cookie name(s).
     *
     * 禁用给定cookie名称(s)的加密
     *
     * @param  string|array  $cookieName
     * @return void
     */
    public function disableFor($cookieName)
    {
        $this->except = array_merge($this->except, (array) $cookieName);
    }

    /**
     * Handle an incoming request.
     *
     * 处理传入请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //在输出响应上对cookie进行加密(在请求上解密cookie)
        return $this->encrypt($next($this->decrypt($request)));
    }

    /**
     * Decrypt the cookies on the request.
     *
     * 在请求上解密cookie
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function decrypt(Request $request)
    {
        foreach ($request->cookies as $key => $c) {
            //确定是否为给定的cookie禁用了加密
            if ($this->isDisabled($key)) {
                continue;
            }

            try {
                //设置参数通过key名(对给定的cookie进行解密并返回值)
                $request->cookies->set($key, $this->decryptCookie($c));
            } catch (DecryptException $e) {
                //设置参数通过key名
                $request->cookies->set($key, null);
            }
        }

        return $request;
    }

    /**
     * Decrypt the given cookie and return the value.
     *
     * 对给定的cookie进行解密并返回值
     *
     * @param  string|array  $cookie
     * @return string|array
     */
    protected function decryptCookie($cookie)
    {
        return is_array($cookie)
                        ? $this->decryptArray($cookie)
                        : $this->encrypter->decrypt($cookie);
    }

    /**
     * Decrypt an array based cookie.
     *
     * 解密基于数组的cookie
     *
     * @param  array  $cookie
     * @return array
     */
    protected function decryptArray(array $cookie)
    {
        $decrypted = [];

        foreach ($cookie as $key => $value) {
            if (is_string($value)) {
                //                             对给定值进行解密
                $decrypted[$key] = $this->encrypter->decrypt($value);
            }
        }

        return $decrypted;
    }

    /**
     * Encrypt the cookies on an outgoing response.
     *
     * 在输出响应上对cookie进行加密
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function encrypt(Response $response)
    {
        //返回所有的cookie数组
        foreach ($response->headers->getCookies() as $cookie) {
            //确定是否为给定的cookie禁用了加密
            if ($this->isDisabled($cookie->getName())) {
                continue;
            }

            //设置cookie(复制带有新值的cookie)
            $response->headers->setCookie($this->duplicate(
                //对给定值进行加密
                $cookie, $this->encrypter->encrypt($cookie->getValue())
            ));
        }

        return $response;
    }

    /**
     * Duplicate a cookie with a new value.
     *
     * 复制带有新值的cookie
     *
     * @param  \Symfony\Component\HttpFoundation\Cookie  $c
     * @param  mixed  $value
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function duplicate(Cookie $c, $value)
    {
        return new Cookie(
            //获取cookie的名称             获取cookie过期的时间     获取服务器上可用cookie的路径
            $c->getName(), $value, $c->getExpiresTime(), $c->getPath(),
            //获取cookie可用的域    检查cookie从客户端的HTTPS链接是否应该被安全发送  检查是否可以只通过HTTP协议获得访问
            $c->getDomain(), $c->isSecure(), $c->isHttpOnly()
        );
    }

    /**
     * Determine whether encryption has been disabled for the given cookie.
     *
     * 确定是否为给定的cookie禁用了加密
     *
     * @param  string $name
     * @return bool
     */
    public function isDisabled($name)
    {
        return in_array($name, $this->except);
    }
}
