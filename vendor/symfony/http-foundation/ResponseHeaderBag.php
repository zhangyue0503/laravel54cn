<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation;

/**
 * ResponseHeaderBag is a container for Response HTTP headers.
 *
 * ResponseHeaderBag是一个响应的HTTP头的容器
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ResponseHeaderBag extends HeaderBag
{
    const COOKIES_FLAT = 'flat';
    const COOKIES_ARRAY = 'array';

    const DISPOSITION_ATTACHMENT = 'attachment';
    const DISPOSITION_INLINE = 'inline';

    /**
     * @var array
     * 计算的缓存控制
     */
    protected $computedCacheControl = array();

    /**
     * @var array
     */
    protected $cookies = array();

    /**
     * @var array
     */
    protected $headerNames = array();

    /**
     * Constructor.
     * 构造函数
     * @param array $headers An array of HTTP headers
     */
    public function __construct(array $headers = array())
    {
        parent::__construct($headers);

        if (!isset($this->headers['cache-control'])) {
            $this->set('Cache-Control', '');
        }
    }

    /**
     * {@inheritdoc}
     * 字符串形式的所有标头
     */
    public function __toString()
    {
        $cookies = '';
        foreach ($this->getCookies() as $cookie) {
            $cookies .= 'Set-Cookie: '.$cookie."\r\n";
        }

        ksort($this->headerNames);
        // Symfony\Component\HttpFoundation\HeaderBag::__toString()
        return parent::__toString().$cookies;
    }

    /**
     * Returns the headers, with original capitalizations.
     *
     * 返回头，与原有的资本
     *
     * @return array An array of headers
     */
    public function allPreserveCase()
    {
        //通过合并两个数组来创建一个新数组，其中的一个数组元素为键名，另一个数组元素为键值
        return array_combine($this->headerNames, $this->headers);
    }

    /**
     * {@inheritdoc}
     * 用新的头数组替换当前HTTP头
     */
    public function replace(array $headers = array())
    {
        $this->headerNames = array();
        // Symfony\Component\HttpFoundation\HeaderBag::replace()
        parent::replace($headers);

        if (!isset($this->headers['cache-control'])) {
            $this->set('Cache-Control', '');
        }
    }

    /**
     * {@inheritdoc}
     * 根据名称设置头
     */
    public function set($key, $values, $replace = true)
    {
        // Symfony\Component\HttpFoundation\HeaderBag::set()
        parent::set($key, $values, $replace);

        $uniqueKey = str_replace('_', '-', strtolower($key));
        $this->headerNames[$uniqueKey] = $key;

        // ensure the cache-control header has sensible defaults
        // 确保缓存控制头具有合理的默认值
        if (in_array($uniqueKey, array('cache-control', 'etag', 'last-modified', 'expires'))) {
            $computed = $this->computeCacheControlValue(); //返回缓存控制头的计算值
            $this->headers['cache-control'] = array($computed);
            $this->headerNames['cache-control'] = 'Cache-Control';
            $this->computedCacheControl = $this->parseCacheControl($computed); // 解析HTTP缓存控制头
        }
    }

    /**
     * {@inheritdoc}
     * 删除头
     */
    public function remove($key)
    {
        // Symfony\Component\HttpFoundation\HeaderBag::remove()
        parent::remove($key);

        $uniqueKey = str_replace('_', '-', strtolower($key));
        unset($this->headerNames[$uniqueKey]);

        if ('cache-control' === $uniqueKey) {
            $this->computedCacheControl = array();
        }
    }

    /**
     * {@inheritdoc}
     * 添加缓存控制指令
     * Symfony\Component\HttpFoundation\HeaderBag::hasCacheControlDirective()
     */
    public function hasCacheControlDirective($key)
    {
        return array_key_exists($key, $this->computedCacheControl);
    }

    /**
     * {@inheritdoc}
     * 按名称返回缓存控制指令值
     * Symfony\Component\HttpFoundation\HeaderBag::getCacheControlDirective()
     */
    public function getCacheControlDirective($key)
    {
        return array_key_exists($key, $this->computedCacheControl) ? $this->computedCacheControl[$key] : null;
    }

    /**
     * Sets a cookie.
     *
     * 设置cookie
     *
     * @param Cookie $cookie
     */
    public function setCookie(Cookie $cookie)
    {
        //[获取cookie可用的域][获取服务器上可用cookie的路径][获取cookie的名称]
        $this->cookies[$cookie->getDomain()][$cookie->getPath()][$cookie->getName()] = $cookie;
    }

    /**
     * Removes a cookie from the array, but does not unset it in the browser.
     *
     * 删除数组cookie，但不在浏览器取消它
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     */
    public function removeCookie($name, $path = '/', $domain = null)
    {
        if (null === $path) {
            $path = '/';
        }

        unset($this->cookies[$domain][$path][$name]);

        if (empty($this->cookies[$domain][$path])) {
            unset($this->cookies[$domain][$path]);

            if (empty($this->cookies[$domain])) {
                unset($this->cookies[$domain]);
            }
        }
    }

    /**
     * Returns an array with all cookies.
     *
     * 返回所有的cookie数组
     *
     * @param string $format
     *
     * @return array
     *
     * @throws \InvalidArgumentException When the $format is invalid
     */
    public function getCookies($format = self::COOKIES_FLAT)
    {
        if (!in_array($format, array(self::COOKIES_FLAT, self::COOKIES_ARRAY))) {
            throw new \InvalidArgumentException(sprintf('Format "%s" invalid (%s).', $format, implode(', ', array(self::COOKIES_FLAT, self::COOKIES_ARRAY))));
        }

        if (self::COOKIES_ARRAY === $format) {
            return $this->cookies;
        }

        $flattenedCookies = array();
        foreach ($this->cookies as $path) {
            foreach ($path as $cookies) {
                foreach ($cookies as $cookie) {
                    $flattenedCookies[] = $cookie;
                }
            }
        }

        return $flattenedCookies;
    }

    /**
     * Clears a cookie in the browser.
     *
     * 清除浏览器中的cookie
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httpOnly
     */
    public function clearCookie($name, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        //设置cookie
        $this->setCookie(new Cookie($name, null, 1, $path, $domain, $secure, $httpOnly));
    }

    /**
     * Generates a HTTP Content-Disposition field-value.
     *
     * 生成HTTP内容配置字段值
     *
     * @param string $disposition      One of "inline" or "attachment" 一个“inline”或“attachment”
     * @param string $filename         A unicode string Unicode字符串
     * @param string $filenameFallback A string containing only ASCII characters that
     *                                 is semantically equivalent to $filename. If the filename is already ASCII,
     *                                 it can be omitted, or just copied from $filename
     *                                 一个字符串，只包含ASCII字符，语义上等同于$filename。如果文件名已经ASCII，它可以省略，或只是从$filename
     *
     * @return string A string suitable for use as a Content-Disposition field-value 适合用作内容配置字段值的字符串
     *
     * @throws \InvalidArgumentException
     *
     * @see RFC 6266
     */
    public function makeDisposition($disposition, $filename, $filenameFallback = '')
    {
        if (!in_array($disposition, array(self::DISPOSITION_ATTACHMENT, self::DISPOSITION_INLINE))) {
            throw new \InvalidArgumentException(sprintf('The disposition must be either "%s" or "%s".', self::DISPOSITION_ATTACHMENT, self::DISPOSITION_INLINE));
        }

        if ('' == $filenameFallback) {
            $filenameFallback = $filename;
        }

        // filenameFallback is not ASCII.
        if (!preg_match('/^[\x20-\x7e]*$/', $filenameFallback)) {
            throw new \InvalidArgumentException('The filename fallback must only contain ASCII characters.');
        }

        // percent characters aren't safe in fallback.  百分比字符在回退中不安全
        if (false !== strpos($filenameFallback, '%')) {
            throw new \InvalidArgumentException('The filename fallback cannot contain the "%" character.');
        }

        // path separators aren't allowed in either.  路径分隔符是不允许的
        if (false !== strpos($filename, '/') || false !== strpos($filename, '\\') || false !== strpos($filenameFallback, '/') || false !== strpos($filenameFallback, '\\')) {
            throw new \InvalidArgumentException('The filename and the fallback cannot contain the "/" and "\\" characters.');
        }

        $output = sprintf('%s; filename="%s"', $disposition, str_replace('"', '\\"', $filenameFallback));

        if ($filename !== $filenameFallback) {
            $output .= sprintf("; filename*=utf-8''%s", rawurlencode($filename));
        }

        return $output;
    }

    /**
     * Returns the calculated value of the cache-control header.
     *
     * 返回缓存控制头的计算值
     *
     * This considers several other headers and calculates or modifies the
     * cache-control header to a sensible, conservative value.
     *
     * 这考虑了几个其他头文件，并将缓存控制头计算或修改为一个明智的、保守的值
     *
     * @return string
     */
    protected function computeCacheControlValue()
    {
        //                           如果定义了HTTP头，则返回true       如果定义了HTTP头，则返回true     如果定义了HTTP头，则返回true
        if (!$this->cacheControl && !$this->has('ETag') && !$this->has('Last-Modified') && !$this->has('Expires')) {
            return 'no-cache, private';
        }

        if (!$this->cacheControl) {
            // conservative by default 保守的默认
            return 'private, must-revalidate';
        }

        $header = $this->getCacheControlHeader(); // 返回头缓存控制器
        if (isset($this->cacheControl['public']) || isset($this->cacheControl['private'])) {
            return $header;
        }

        // public if s-maxage is defined, private otherwise
        if (!isset($this->cacheControl['s-maxage'])) {
            return $header.', private';
        }

        return $header;
    }
}
