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
 * Response represents an HTTP response.
 *
 * 响应表示HTTP响应
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Response
{
    const HTTP_CONTINUE = 100;
    const HTTP_SWITCHING_PROTOCOLS = 101;
    const HTTP_PROCESSING = 102;            // RFC2518
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    const HTTP_NO_CONTENT = 204;
    const HTTP_RESET_CONTENT = 205;
    const HTTP_PARTIAL_CONTENT = 206;
    const HTTP_MULTI_STATUS = 207;          // RFC4918
    const HTTP_ALREADY_REPORTED = 208;      // RFC5842
    const HTTP_IM_USED = 226;               // RFC3229
    const HTTP_MULTIPLE_CHOICES = 300;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_USE_PROXY = 305;
    const HTTP_RESERVED = 306;
    const HTTP_TEMPORARY_REDIRECT = 307;
    const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    const HTTP_REQUEST_TIMEOUT = 408;
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_LENGTH_REQUIRED = 411;
    const HTTP_PRECONDITION_FAILED = 412;
    const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    const HTTP_REQUEST_URI_TOO_LONG = 414;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const HTTP_EXPECTATION_FAILED = 417;
    const HTTP_I_AM_A_TEAPOT = 418;                                               // RFC2324
    const HTTP_MISDIRECTED_REQUEST = 421;                                         // RFC7540
    const HTTP_UNPROCESSABLE_ENTITY = 422;                                        // RFC4918
    const HTTP_LOCKED = 423;                                                      // RFC4918
    const HTTP_FAILED_DEPENDENCY = 424;                                           // RFC4918
    const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425;   // RFC2817
    const HTTP_UPGRADE_REQUIRED = 426;                                            // RFC2817
    const HTTP_PRECONDITION_REQUIRED = 428;                                       // RFC6585
    const HTTP_TOO_MANY_REQUESTS = 429;                                           // RFC6585
    const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                             // RFC6585
    const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_BAD_GATEWAY = 502;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    const HTTP_GATEWAY_TIMEOUT = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;
    const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;                        // RFC2295
    const HTTP_INSUFFICIENT_STORAGE = 507;                                        // RFC4918
    const HTTP_LOOP_DETECTED = 508;                                               // RFC5842
    const HTTP_NOT_EXTENDED = 510;                                                // RFC2774
    const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;                             // RFC6585

    /**
     * @var \Symfony\Component\HttpFoundation\ResponseHeaderBag
     */
    public $headers;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $statusText;

    /**
     * @var string
     */
    protected $charset;

    /**
     * Status codes translation table.
     *
     * 翻译状态代码表
     *
     * The list of codes is complete according to the 代码的列表是完整的，根据
     * {@link http://www.iana.org/assignments/http-status-codes/ Hypertext Transfer Protocol (HTTP) Status Code Registry}
     * (last updated 2016-03-01).
     *
     * Unless otherwise noted, the status code is defined in RFC2616.
     * 除非另有说明，状态码定义RFC2616
     *
     * @var array
     */
    public static $statusTexts = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        421 => 'Misdirected Request',                                         // RFC7540
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        451 => 'Unavailable For Legal Reasons',                               // RFC7725
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',                      // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    );

    private static $deprecatedMethods = array(
        'setDate', 'getDate',
        'setExpires', 'getExpires',
        'setLastModified', 'getLastModified',
        'setProtocolVersion', 'getProtocolVersion',
        'setStatusCode', 'getStatusCode',
        'setCharset', 'getCharset',
        'setPrivate', 'setPublic',
        'getAge', 'getMaxAge', 'setMaxAge', 'setSharedMaxAge',
        'getTtl', 'setTtl', 'setClientTtl',
        'getEtag', 'setEtag',
        'hasVary', 'getVary', 'setVary',
        'isInvalid', 'isSuccessful', 'isRedirection',
        'isClientError', 'isOk', 'isForbidden',
        'isNotFound', 'isRedirect', 'isEmpty',
    );
    private static $deprecationsTriggered = array(
        __CLASS__ => true,
        BinaryFileResponse::class => true,
        JsonResponse::class => true,
        RedirectResponse::class => true,
        StreamedResponse::class => true,
    );

    /**
     * Constructor.
     *
     * 构造函数
     *
     * @param mixed $content The response content, see setContent() 响应内容，查看setContent()方法
     * @param int   $status  The response status code 响应状态码
     * @param array $headers An array of response headers 响应头数组
     *
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function __construct($content = '', $status = 200, $headers = array())
    {
        $this->headers = new ResponseHeaderBag($headers);
        $this->setContent($content); //设置响应的内容
        $this->setStatusCode($status);//设置响应状态代码
        $this->setProtocolVersion('1.0'); //设置协议版本

        // Deprecations
        $class = get_class($this);
        if ($this instanceof \PHPUnit_Framework_MockObject_MockObject || $this instanceof \Prophecy\Doubler\DoubleInterface) {
            $class = get_parent_class($class);
        }
        if (isset(self::$deprecationsTriggered[$class])) {
            return;
        }

        self::$deprecationsTriggered[$class] = true;
        foreach (self::$deprecatedMethods as $method) {
            $r = new \ReflectionMethod($class, $method);
            if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                @trigger_error(sprintf('Extending %s::%s() in %s is deprecated since version 3.2 and won\'t be supported anymore in 4.0 as it will be final.', __CLASS__, $method, $class), E_USER_DEPRECATED);
            }
        }
    }

    /**
     * Factory method for chainability.
     *
     * 链式调用的工厂方法
     *
     * Example:
     *
     *     return Response::create($body, 200)
     *         ->setSharedMaxAge(300);
     *
     * @param mixed $content The response content, see setContent()
     * @param int   $status  The response status code
     * @param array $headers An array of response headers
     *
     * @return static
     */
    public static function create($content = '', $status = 200, $headers = array())
    {
        return new static($content, $status, $headers);
    }

    /**
     * Returns the Response as an HTTP string.
     *
     * 将响应返回为http字符串
     *
     * The string representation of the Response is the same as the
     * one that will be sent to the client only if the prepare() method
     * has been called before.
     *
     * 响应的字符串表示形式为一个将被发送到客户端只有prepare()方法被调用之前一样。
     *
     * @return string The Response as an HTTP string 作为HTTP字符串的响应
     *
     * @see prepare()
     */
    public function __toString()
    {
        return
            sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText)."\r\n".
            $this->headers."\r\n".
            $this->getContent(); //当前的响应内容
    }

    /**
     * Clones the current Response instance.
     *
     * 复制当前的响应实例
     *
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
    }

    /**
     * Prepares the Response before it is sent to the client.
     *
     * 在发送给客户端之前准备响应
     *
     * This method tweaks the Response to ensure that it is
     * compliant with RFC 2616. Most of the changes are based on
     * the Request that is "associated" with this Response.
     *
     * 此方法调整响应，以确保它符合RFC 2616。
     * 大多数的变化都是基于与此响应相关联的请求
     *
     * @param Request $request A Request instance
     *
     * @return $this
     */
    public function prepare(Request $request)
    {
        $headers = $this->headers;
        //       响应信息                 响应是空的
        if ($this->isInformational() || $this->isEmpty()) {
            $this->setContent(null); // 设置响应的内容为null
            $headers->remove('Content-Type');
            $headers->remove('Content-Length');
        } else {
            // Content-type based on the Request
            // 基于请求的内容类型
            // 头包含Content-Type
            if (!$headers->has('Content-Type')) {
                $format = $request->getRequestFormat(); // 获取请求格式
                //                                          获取与格式关联的MIME类型
                if (null !== $format && $mimeType = $request->getMimeType($format)) {
                    $headers->set('Content-Type', $mimeType);
                }
            }

            // Fix Content-Type 修改Content-Type
            $charset = $this->charset ?: 'UTF-8';
            if (!$headers->has('Content-Type')) {
                $headers->set('Content-Type', 'text/html; charset='.$charset);
            } elseif (0 === stripos($headers->get('Content-Type'), 'text/') && false === stripos($headers->get('Content-Type'), 'charset')) {
                // add the charset 添加charset
                $headers->set('Content-Type', $headers->get('Content-Type').'; charset='.$charset);
            }

            // Fix Content-Length 修复 Content-Length
            if ($headers->has('Transfer-Encoding')) {
                $headers->remove('Content-Length');
            }
            // 检查请求方法是否为指定类型
            if ($request->isMethod('HEAD')) {
                // cf. RFC2616 14.13
                $length = $headers->get('Content-Length');
                $this->setContent(null);
                if ($length) {
                    $headers->set('Content-Length', $length);
                }
            }
        }

        // Fix protocol 修复协议
        if ('HTTP/1.0' != $request->server->get('SERVER_PROTOCOL')) {
            $this->setProtocolVersion('1.1');
        }

        // Check if we need to send extra expire info headers
        // 检查是否需要发送额外过期信息头
        //                    获取http协议版本
        if ('1.0' == $this->getProtocolVersion() && false !== strpos($this->headers->get('Cache-Control'), 'no-cache')) {
            $this->headers->set('pragma', 'no-cache');
            $this->headers->set('expires', -1);
        }

        $this->ensureIEOverSSLCompatibility($request);

        return $this;
    }

    /**
     * Sends HTTP headers.
     *
     * @return $this
     */
    public function sendHeaders()
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return $this;
        }

        if (!$this->headers->has('Date')) {
            $this->setDate(\DateTime::createFromFormat('U', time()));
        }

        // headers
        foreach ($this->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                header($name.': '.$value, false, $this->statusCode);
            }
        }

        // status
        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText), true, $this->statusCode);

        // cookies
        foreach ($this->headers->getCookies() as $cookie) {
            if ($cookie->isRaw()) {
                setrawcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
            } else {
                setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
            }
        }

        return $this;
    }

    /**
     * Sends content for the current web response.
     *
     * @return $this
     */
    public function sendContent()
    {
        echo $this->content;

        return $this;
    }

    /**
     * Sends HTTP headers and content.
     *
     * 发送HTTP头和内容
     *
     * @return $this
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif ('cli' !== PHP_SAPI) {
            static::closeOutputBuffers(0, true);
        }

        return $this;
    }

    /**
     * Sets the response content.
     *
     * 设置响应的内容
     *
     * Valid types are strings, numbers, null, and objects that implement a __toString() method.
     *
     * 有效的类型是字符串，数字，null，并实现__tostring()方法的对象
     *
     * @param mixed $content Content that can be cast to string 可以被转换为字符串的内容
     *
     * @return $this
     *
     * @throws \UnexpectedValueException
     */
    public function setContent($content)
    {
        if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable(array($content, '__toString'))) {
            //                                                   响应内容必须是字符串或者实现了__toString()方法的对象
            throw new \UnexpectedValueException(sprintf('The Response content must be a string or object implementing __toString(), "%s" given.', gettype($content)));
        }

        $this->content = (string) $content;

        return $this;
    }

    /**
     * Gets the current response content.
     *
     * 获取当前的响应内容
     *
     * @return string Content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets the HTTP protocol version (1.0 or 1.1).
     *
     * 设置HTTP协议版本（1或1.1）
     *
     * @param string $version The HTTP protocol version
     *
     * @return $this
     */
    public function setProtocolVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Gets the HTTP protocol version.
     *
     * 获取http协议版本
     *
     * @return string The HTTP protocol version
     */
    public function getProtocolVersion()
    {
        return $this->version;
    }

    /**
     * Sets the response status code.
     *
     * 设置响应状态代码
     *
     * @param int   $code HTTP status code
     * @param mixed $text HTTP status text
     *
     * If the status text is null it will be automatically populated for the known
     * status codes and left empty otherwise.
     *
     * 如果状态文本为NULL，它将自动填充为已知的状态代码，并留下空
     *
     * @return $this
     *
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function setStatusCode($code, $text = null)
    {
        $this->statusCode = $code = (int) $code;
        if ($this->isInvalid()) { // 无效响应？
            throw new \InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $code));
        }

        if (null === $text) {
            $this->statusText = isset(self::$statusTexts[$code]) ? self::$statusTexts[$code] : 'unknown status';

            return $this;
        }

        if (false === $text) {
            $this->statusText = '';

            return $this;
        }

        $this->statusText = $text;

        return $this;
    }

    /**
     * Retrieves the status code for the current web response.
     *
     * 检索当前web响应的状态代码
     *
     * @return int Status code
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets the response charset.
     *
     * 设置响应的电荷
     *
     * @param string $charset Character set
     *
     * @return $this
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Retrieves the response charset.
     *
     * 检索响应的字符集
     *
     * @return string Character set
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Returns true if the response is worth caching under any circumstance.
     *
     * 如果响应在任何情况下都值得缓存，则返回true
     *
     * Responses marked "private" with an explicit Cache-Control directive are
     * considered uncacheable.
     *
     * 响应标志着“私有的”一个明确的缓存控制指令被认为是不可缓存的
     *
     * Responses with neither a freshness lifetime (Expires, max-age) nor cache
     * validator (Last-Modified, ETag) are considered uncacheable.
     *
     * 响应没有缓存生命周期（Expires, max-age）和缓存验证器（Last-Modified）它被认为是不可缓存。
     *
     * @return bool true if the response is worth caching, false otherwise
     */
    public function isCacheable()
    {
        if (!in_array($this->statusCode, array(200, 203, 300, 301, 302, 404, 410))) {
            return false;
        }
        //                如果定义了缓存控制指令(no-store)                      按名称返回缓存控制指令值(private)
        if ($this->headers->hasCacheControlDirective('no-store') || $this->headers->getCacheControlDirective('private')) {
            return false;
        }
        // 如果响应包括可使用条件接收请求来验证响应的头服务器 || 如果响应是“fresh”
        return $this->isValidateable() || $this->isFresh();
    }

    /**
     * Returns true if the response is "fresh".
     *
     * 如果响应是“fresh”返回true
     *
     * Fresh responses may be served from cache without any interaction with the
     * origin. A response is considered fresh when it includes a Cache-Control/max-age
     * indicator or Expires header and the calculated age is less than the freshness lifetime.
     *
     * 刷新的响应可以从高速缓存没有任何与原产地的互动。
     * 一个响应被认为是刷新的，当它包括一个Cache-Control/max-age指示器或Expires头和计算时间小于刷新寿命。
     *
     * @return bool true if the response is fresh, false otherwise
     */
    public function isFresh()
    {
        return $this->getTtl() > 0;
    }

    /**
     * Returns true if the response includes headers that can be used to validate
     * the response with the origin server using a conditional GET request.
     *
     * 如果响应包括可使用条件接收请求来验证响应的头服务器，则返回true
     *
     * @return bool true if the response is validateable, false otherwise
     */
    public function isValidateable()
    {
        return $this->headers->has('Last-Modified') || $this->headers->has('ETag');
    }

    /**
     * Marks the response as "private".
     *
     * 标记响应为“private”
     *
     * It makes the response ineligible for serving other clients.
     *
     * 它使响应不适合为其他客户服务
     *
     * @return $this
     */
    public function setPrivate()
    {
        $this->headers->removeCacheControlDirective('public'); //移除缓存控制指令(public)
        $this->headers->addCacheControlDirective('private'); //添加缓存控制指令(private)

        return $this;
    }

    /**
     * Marks the response as "public".
     *
     * 标记响应为“public”
     *
     * It makes the response eligible for serving other clients.
     *
     * 它使响应有资格为其他客户服务
     *
     * @return $this
     */
    public function setPublic()
    {
        $this->headers->addCacheControlDirective('public');//添加缓存控制指令(public)
        $this->headers->removeCacheControlDirective('private'); //移除缓存控制指令(private)

        return $this;
    }

    /**
     * Returns true if the response must be revalidated by caches.
     *
     * 如果响应必须重新验证缓存则返回true
     *
     * This method indicates that the response must not be served stale by a
     * cache in any circumstance without first revalidating with the origin.
     * When present, the TTL of the response should not be overridden to be
     * greater than the value provided by the origin.
     *
     * 这种方法表明，响应必须是不过时的缓存在任何情况下不用首先重新验证源。
     * 当存在时，响应的TTL不应被重写为大于原点提供的值。
     *
     * @return bool true if the response must be revalidated by a cache, false otherwise
     */
    public function mustRevalidate()
    {
        //                      如果定义了缓存控制指令(must-revalidate)                          如果定义了缓存控制指令(proxy-revalidate)
        return $this->headers->hasCacheControlDirective('must-revalidate') || $this->headers->hasCacheControlDirective('proxy-revalidate');
    }

    /**
     * Returns the Date header as a DateTime instance.
     *
     * 返回日期头的时间实例
     *
     * @return \DateTime A \DateTime instance
     *
     * @throws \RuntimeException When the header is not parseable
     */
    public function getDate()
    {
        if (!$this->headers->has('Date')) {
            $this->setDate(\DateTime::createFromFormat('U', time()));
        }

        return $this->headers->getDate('Date');
    }

    /**
     * Sets the Date header.
     *
     * 设置日期头
     *
     * @param \DateTime $date A \DateTime instance
     *
     * @return $this
     */
    public function setDate(\DateTime $date)
    {
        $date->setTimezone(new \DateTimeZone('UTC'));
        $this->headers->set('Date', $date->format('D, d M Y H:i:s').' GMT');

        return $this;
    }

    /**
     * Returns the age of the response.
     *
     * 返回响应的age
     *
     * @return int The age of the response in seconds
     */
    public function getAge()
    {
        if (null !== $age = $this->headers->get('Age')) {
            return (int) $age;
        }

        return max(time() - $this->getDate()->format('U'), 0);
    }

    /**
     * Marks the response stale by setting the Age header to be equal to the maximum age of the response.
     *
     * 通过设置age标头等于响应的最大age来标记响应的陈旧性
     *
     * @return $this
     */
    public function expire()
    {
        if ($this->isFresh()) {
            $this->headers->set('Age', $this->getMaxAge());
        }

        return $this;
    }

    /**
     * Returns the value of the Expires header as a DateTime instance.
     *
     * 作为一个DateTime实例返回Expires标头的值
     *
     * @return \DateTime|null A DateTime instance or null if the header does not exist
     */
    public function getExpires()
    {
        try {
            return $this->headers->getDate('Expires');
        } catch (\RuntimeException $e) {
            // according to RFC 2616 invalid date formats (e.g. "0" and "-1") must be treated as in the past
            // 根据RFC 2616无效的日期格式（如“0”和“- 1”）必须被视为过去
            return \DateTime::createFromFormat(DATE_RFC2822, 'Sat, 01 Jan 00 00:00:00 +0000');
        }
    }

    /**
     * Sets the Expires HTTP header with a DateTime instance.
     *
     * 设计一个DateTime实例返回Expires HTTP 标头的值
     *
     * Passing null as value will remove the header.
     *
     * 传递NULL作为值将删除头
     *
     * @param \DateTime|null $date A \DateTime instance or null to remove the header
     *
     * @return $this
     */
    public function setExpires(\DateTime $date = null)
    {
        if (null === $date) {
            $this->headers->remove('Expires');
        } else {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone('UTC'));
            $this->headers->set('Expires', $date->format('D, d M Y H:i:s').' GMT');
        }

        return $this;
    }

    /**
     * Returns the number of seconds after the time specified in the response's Date
     * header when the response should no longer be considered fresh.
     *
     * 返回响应日期标头中指定的时间的秒数，此时响应不再被认为是刷新的。
     *
     * First, it checks for a s-maxage directive, then a max-age directive, and then it falls
     * back on an expires header. It returns null when no maximum age can be established.
     *
     * 首先，它检查一个s-maxage指令，然后 max-age指令，然后它会回到一个Expires头。
     * 它可以返回空时，不能建立最大age。
     *
     * @return int|null Number of seconds
     */
    public function getMaxAge()
    {
        //                      如果定义了缓存控制指令(s-maxage)
        if ($this->headers->hasCacheControlDirective('s-maxage')) {
            return (int) $this->headers->getCacheControlDirective('s-maxage'); // 获取缓存控制指令(s-maxage)
        }
        //                      如果定义了缓存控制指令(max-age)
        if ($this->headers->hasCacheControlDirective('max-age')) {
            return (int) $this->headers->getCacheControlDirective('max-age');// 获取缓存控制指令(max-age)
        }
        //           返回Expires标头的值
        if (null !== $this->getExpires()) {
            return $this->getExpires()->format('U') - $this->getDate()->format('U');
        }
    }

    /**
     * Sets the number of seconds after which the response should no longer be considered fresh.
     *
     * 设置秒数后，响应不再被认为是刷新的
     *
     * This methods sets the Cache-Control max-age directive.
     *
     * 这个方法设置max-age缓存控制指令
     *
     * @param int $value Number of seconds
     *
     * @return $this
     */
    public function setMaxAge($value)
    {
        $this->headers->addCacheControlDirective('max-age', $value); // 添加缓存控制指令(max-age)

        return $this;
    }

    /**
     * Sets the number of seconds after which the response should no longer be considered fresh by shared caches.
     *
     * 设置秒数后，响应不再被认为是刷新的共享缓存
     *
     * This methods sets the Cache-Control s-maxage directive.
     *
     * 这个方法设置s-maxage缓存控制指令
     *
     * @param int $value Number of seconds
     *
     * @return $this
     */
    public function setSharedMaxAge($value)
    {
        $this->setPublic(); // 标记响应为“public”
        $this->headers->addCacheControlDirective('s-maxage', $value); // 添加缓存控制指令(s-maxage)

        return $this;
    }

    /**
     * Returns the response's time-to-live in seconds.
     *
     * 返回响应时间以秒为单位
     *
     * It returns null when no freshness information is present in the response.
     *
     * 它返回NULL时，没有刷新的信息中存在的响应
     *
     * When the responses TTL is <= 0, the response may not be served from cache without first
     * revalidating with the origin.
     *
     * 当响应的TTL是< = 0，反应可能无法从缓存获得revalidating源
     *
     * @return int|null The TTL in seconds
     */
    public function getTtl()
    {
        if (null !== $maxAge = $this->getMaxAge()) {
            return $maxAge - $this->getAge();
        }
    }

    /**
     * Sets the response's time-to-live for shared caches.
     *
     * 设置响应缓存共享缓存的响应时间
     *
     * This method adjusts the Cache-Control/s-maxage directive.
     *
     * 这个方法调整缓存控制s-maxage指令
     *
     * @param int $seconds Number of seconds
     *
     * @return $this
     */
    public function setTtl($seconds)
    {
        $this->setSharedMaxAge($this->getAge() + $seconds);

        return $this;
    }

    /**
     * Sets the response's time-to-live for private/client caches.
     *
     * 设置响应的时间为私有/客户端缓存
     *
     * This method adjusts the Cache-Control/max-age directive.
     *
     * 这个方法调整缓存控制max-age指令
     *
     * @param int $seconds Number of seconds
     *
     * @return $this
     */
    public function setClientTtl($seconds)
    {
        $this->setMaxAge($this->getAge() + $seconds);

        return $this;
    }

    /**
     * Returns the Last-Modified HTTP header as a DateTime instance.
     *
     * 用一个DateTime实例返回的Last-Modified HTTP头
     *
     * @return \DateTime|null A DateTime instance or null if the header does not exist
     *
     * @throws \RuntimeException When the HTTP header is not parseable
     */
    public function getLastModified()
    {
        return $this->headers->getDate('Last-Modified');
    }

    /**
     * Sets the Last-Modified HTTP header with a DateTime instance.
     *
     * 设置一个DateTime作为Last-Modified HTTP头
     *
     * Passing null as value will remove the header.
     *
     * 传递null值将删除这个头
     *
     * @param \DateTime|null $date A \DateTime instance or null to remove the header
     *
     * @return $this
     */
    public function setLastModified(\DateTime $date = null)
    {
        if (null === $date) {
            $this->headers->remove('Last-Modified');
        } else {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone('UTC'));
            $this->headers->set('Last-Modified', $date->format('D, d M Y H:i:s').' GMT');
        }

        return $this;
    }

    /**
     * Returns the literal value of the ETag HTTP header.
     *
     * 返回ETag HTTP标头的文本值
     *
     * @return string|null The ETag HTTP header or null if it does not exist
     */
    public function getEtag()
    {
        return $this->headers->get('ETag');
    }

    /**
     * Sets the ETag value.
     *
     * 设置ETag值
     *
     * @param string|null $etag The ETag unique identifier or null to remove the header
     * @param bool        $weak Whether you want a weak ETag or not
     *
     * @return $this
     */
    public function setEtag($etag = null, $weak = false)
    {
        if (null === $etag) {
            $this->headers->remove('Etag');
        } else {
            if (0 !== strpos($etag, '"')) {
                $etag = '"'.$etag.'"';
            }

            $this->headers->set('ETag', (true === $weak ? 'W/' : '').$etag);
        }

        return $this;
    }

    /**
     * Sets the response's cache headers (validation and/or expiration).
     *
     * 设置响应的缓存头（验证和/或过期）
     *
     * Available options are: etag, last_modified, max_age, s_maxage, private, and public.
     *
     * 可用的选项是：etag, last_modified, max_age, s_maxage, private, and public
     *
     * @param array $options An array of cache options
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setCache(array $options)
    {
        if ($diff = array_diff(array_keys($options), array('etag', 'last_modified', 'max_age', 's_maxage', 'private', 'public'))) {
            throw new \InvalidArgumentException(sprintf('Response does not support the following options: "%s".', implode('", "', array_values($diff))));
        }

        if (isset($options['etag'])) {
            $this->setEtag($options['etag']);
        }

        if (isset($options['last_modified'])) {
            $this->setLastModified($options['last_modified']);
        }

        if (isset($options['max_age'])) {
            $this->setMaxAge($options['max_age']);
        }

        if (isset($options['s_maxage'])) {
            $this->setSharedMaxAge($options['s_maxage']);
        }

        if (isset($options['public'])) {
            if ($options['public']) {
                $this->setPublic();
            } else {
                $this->setPrivate();
            }
        }

        if (isset($options['private'])) {
            if ($options['private']) {
                $this->setPrivate();
            } else {
                $this->setPublic();
            }
        }

        return $this;
    }

    /**
     * Modifies the response so that it conforms to the rules defined for a 304 status code.
     *
     * 修改响应，使其符合定义为304状态代码的规则
     *
     * This sets the status, removes the body, and discards any headers
     * that MUST NOT be included in 304 responses.
     *
     * 这里设置了状态，删除了主体，并丢弃了304个响应中不能包含的任何头文件
     *
     * @return $this
     *
     * @see http://tools.ietf.org/html/rfc2616#section-10.3.5
     */
    public function setNotModified()
    {
        $this->setStatusCode(304);
        $this->setContent(null);

        // remove headers that MUST NOT be included with 304 Not Modified responses
        // 移除不能被包含304未修改响应的标头
        foreach (array('Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Content-Type', 'Last-Modified') as $header) {
            $this->headers->remove($header);
        }

        return $this;
    }

    /**
     * Returns true if the response includes a Vary header.
     *
     * 如果响应包含Vary标头，则返回true
     *
     * @return bool true if the response includes a Vary header, false otherwise
     */
    public function hasVary()
    {
        return null !== $this->headers->get('Vary');
    }

    /**
     * Returns an array of header names given in the Vary header.
     *
     * 返回在Vary标头中给出的标头数组
     *
     * @return array An array of Vary names
     */
    public function getVary()
    {
        if (!$vary = $this->headers->get('Vary', null, false)) {
            return array();
        }

        $ret = array();
        foreach ($vary as $item) {
            $ret = array_merge($ret, preg_split('/[\s,]+/', $item));
        }

        return $ret;
    }

    /**
     * Sets the Vary header.
     *
     * 设置Vary头
     *
     * @param string|array $headers
     * @param bool         $replace Whether to replace the actual value or not (true by default)
     *
     * @return $this
     */
    public function setVary($headers, $replace = true)
    {
        $this->headers->set('Vary', $headers, $replace);

        return $this;
    }

    /**
     * Determines if the Response validators (ETag, Last-Modified) match
     * a conditional value specified in the Request.
     *
     * 决定是否响应验证器（ETag，Last-Modified）匹配请求中指定的条件值
     *
     * If the Response is not modified, it sets the status code to 304 and
     * removes the actual content by calling the setNotModified() method.
     *
     * 如果响应是不modified，设置状态代码304并通过调用setnotmodified()方法移除的实际内容。
     *
     * @param Request $request A Request instance
     *
     * @return bool true if the Response validators match the Request, false otherwise
     */
    public function isNotModified(Request $request)
    {
        if (!$request->isMethodCacheable()) {  // 检查方法是否可缓存
            return false;
        }

        $notModified = false;
        $lastModified = $this->headers->get('Last-Modified');
        $modifiedSince = $request->headers->get('If-Modified-Since');

        if ($etags = $request->getETags()) {
            $notModified = in_array($this->getEtag(), $etags) || in_array('*', $etags);
        }

        if ($modifiedSince && $lastModified) {
            $notModified = strtotime($modifiedSince) >= strtotime($lastModified) && (!$etags || $notModified);
        }

        if ($notModified) {
            $this->setNotModified();
        }

        return $notModified;
    }

    /**
     * Is response invalid?
     *
     * 无效响应？
     *
     * @return bool
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    public function isInvalid()
    {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }

    /**
     * Is response informative?
     *
     * 响应信息？
     *
     * @return bool
     */
    public function isInformational()
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * Is response successful?
     *
     * 响应成功？
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Is the response a redirect?
     *
     * 响应是重定向？
     *
     * @return bool
     */
    public function isRedirection()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Is there a client error?
     *
     * 有客户端错误？
     *
     * @return bool
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Was there a server side error?
     *
     * 是否有服务器端错误？
     *
     * @return bool
     */
    public function isServerError()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Is the response OK?
     *
     * 响应是OK？
     *
     * @return bool
     */
    public function isOk()
    {
        return 200 === $this->statusCode;
    }

    /**
     * Is the response forbidden?
     *
     * 响应是forbidden？
     *
     * @return bool
     */
    public function isForbidden()
    {
        return 403 === $this->statusCode;
    }

    /**
     * Is the response a not found error?
     *
     * 响应是404？
     *
     * @return bool
     */
    public function isNotFound()
    {
        return 404 === $this->statusCode;
    }

    /**
     * Is the response a redirect of some form?
     *
     * 响应是某种形式的重定向吗？
     *
     * @param string $location
     *
     * @return bool
     */
    public function isRedirect($location = null)
    {
        return in_array($this->statusCode, array(201, 301, 302, 303, 307, 308)) && (null === $location ?: $location == $this->headers->get('Location'));
    }

    /**
     * Is the response empty?
     *
     * 响应是空的？
     *
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->statusCode, array(204, 304));
    }

    /**
     * Cleans or flushes output buffers up to target level.
     *
     * 清除或刷新输出缓冲区到目标级别
     *
     * Resulting level can be greater than target level if a non-removable buffer has been encountered.
     *
     * 如果遇到一个非可移动缓冲区，则结果级别可能大于目标级别
     *
     * @param int  $targetLevel The target output buffering level
     * @param bool $flush       Whether to flush or clean the buffers
     */
    public static function closeOutputBuffers($targetLevel, $flush)
    {
        $status = ob_get_status(true);
        $level = count($status);
        // PHP_OUTPUT_HANDLER_* are not defined on HHVM 3.3
        $flags = defined('PHP_OUTPUT_HANDLER_REMOVABLE') ? PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE) : -1;

        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || $flags === ($s['flags'] & $flags) : $s['del'])) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }

    /**
     * Checks if we need to remove Cache-Control for SSL encrypted downloads when using IE < 9.
     *
     * 检查是否需要删除缓存控制的SSL加密下载时使用IE 9
     *
     * @see http://support.microsoft.com/kb/323308
     */
    protected function ensureIEOverSSLCompatibility(Request $request)
    {
        if (false !== stripos($this->headers->get('Content-Disposition'), 'attachment') && preg_match('/MSIE (.*?);/i', $request->server->get('HTTP_USER_AGENT'), $match) == 1 && true === $request->isSecure()) {
            if ((int) preg_replace('/(MSIE )(.*?);/', '$2', $match[0]) < 9) {
                $this->headers->remove('Cache-Control');
            }
        }
    }
}
