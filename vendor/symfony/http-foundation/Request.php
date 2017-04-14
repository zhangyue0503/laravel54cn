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

use Symfony\Component\HttpFoundation\Exception\ConflictingHeadersException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Request represents an HTTP request.
 *
 * HTTP请求代表请求
 *
 * The methods dealing with URL accept / return a raw path (% encoded):
 *   * getBasePath
 *   * getBaseUrl
 *   * getPathInfo
 *   * getRequestUri
 *   * getUri
 *   * getUriForPath
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Request
{
    const HEADER_FORWARDED = 'forwarded';
    const HEADER_CLIENT_IP = 'client_ip';
    const HEADER_CLIENT_HOST = 'client_host';
    const HEADER_CLIENT_PROTO = 'client_proto';
    const HEADER_CLIENT_PORT = 'client_port';

    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PURGE = 'PURGE';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_TRACE = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';

    /**
     * @var string[]
     */
    protected static $trustedProxies = array();

    /**
     * @var string[]
     */
    protected static $trustedHostPatterns = array();

    /**
     * @var string[]
     */
    protected static $trustedHosts = array();

    /**
     * Names for headers that can be trusted when
     * using trusted proxies.
     *
     * 使用可信代理时可信任的头的名称
     *
     * The FORWARDED header is the standard as of rfc7239.
     *
     * 转发的头部是标准的rfc7239
     *
     * The other headers are non-standard, but widely used
     * by popular reverse proxies (like Apache mod_proxy or Amazon EC2).
     *
     * 另一头部是不规范的，但广泛使用的流行的反向代理服务器（如Apache mod_proxy或Amazon EC2）
     *
     */
    protected static $trustedHeaders = array(
        self::HEADER_FORWARDED => 'FORWARDED',
        self::HEADER_CLIENT_IP => 'X_FORWARDED_FOR',
        self::HEADER_CLIENT_HOST => 'X_FORWARDED_HOST',
        self::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO',
        self::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT',
    );

    protected static $httpMethodParameterOverride = false;

    /**
     * Custom parameters.
     *
     * 自定义参数
     *
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     */
    public $attributes;

    /**
     * Request body parameters ($_POST).
     *
     * 请求参数（$_POST）
     *
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     */
    public $request;

    /**
     * Query string parameters ($_GET)..
     *
     * 查询字符串参数（$_GET）
     *
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     */
    public $query;

    /**
     * Server and execution environment parameters ($_SERVER).
     *
     * 服务器和执行环境参数（$_SERVER）
     *
     * @var \Symfony\Component\HttpFoundation\ServerBag
     */
    public $server;

    /**
     * Uploaded files ($_FILES).
     *
     * 上传的文件（￥$_FILES）
     *
     * @var \Symfony\Component\HttpFoundation\FileBag
     */
    public $files;

    /**
     * Cookies ($_COOKIE).
     *
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     */
    public $cookies;

    /**
     * Headers (taken from the $_SERVER).
     *
     * Headers (从 $_SERVER)
     *
     * @var \Symfony\Component\HttpFoundation\HeaderBag
     */
    public $headers;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var array
     */
    protected $languages;

    /**
     * @var array
     */
    protected $charsets;

    /**
     * @var array
     */
    protected $encodings;

    /**
     * @var array
     */
    protected $acceptableContentTypes;

    /**
     * @var string
     */
    protected $pathInfo;

    /**
     * @var string
     */
    protected $requestUri;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    protected $session;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $defaultLocale = 'en';

    /**
     * @var array
     */
    protected static $formats;

    protected static $requestFactory;

    /**
     * Constructor.
     *
     * 构造函数
     *
     * @param array           $query      The GET parameters
     * @param array           $request    The POST parameters
     * @param array           $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array           $cookies    The COOKIE parameters
     * @param array           $files      The FILES parameters
     * @param array           $server     The SERVER parameters
     * @param string|resource $content    The raw body data
     */
    public function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {
        $this->initialize($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    /**
     * Sets the parameters for this request.
     *
     * 设置此请求的参数
     *
     * This method also re-initializes all properties.
     *
     * 此方法还重新初始化所有属性
     *
     * @param array           $query      The GET parameters
     * @param array           $request    The POST parameters
     * @param array           $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array           $cookies    The COOKIE parameters
     * @param array           $files      The FILES parameters
     * @param array           $server     The SERVER parameters
     * @param string|resource $content    The raw body data
     */
    public function initialize(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {
        $this->request = new ParameterBag($request);
        $this->query = new ParameterBag($query);
        $this->attributes = new ParameterBag($attributes);
        $this->cookies = new ParameterBag($cookies);
        $this->files = new FileBag($files);
        $this->server = new ServerBag($server);
        $this->headers = new HeaderBag($this->server->getHeaders());

        $this->content = $content;
        $this->languages = null;
        $this->charsets = null;
        $this->encodings = null;
        $this->acceptableContentTypes = null;
        $this->pathInfo = null;
        $this->requestUri = null;
        $this->baseUrl = null;
        $this->basePath = null;
        $this->method = null;
        $this->format = null;
    }

    /**
     * Creates a new request with values from PHP's super globals.
     *
     * 从PHP的超级全局变量创建一个新的请求
     *
     * @return static
     */
    public static function createFromGlobals()
    {
        // With the php's bug #66606, the php's built-in web server
        // stores the Content-Type and Content-Length header values in
        // HTTP_CONTENT_TYPE and HTTP_CONTENT_LENGTH fields.
        //
        // 源于PHP的错误# 66606，PHP的内置网络服务器存储在HTTP_CONTENt-Type and Content-Length 值
        // * PHP的错误# 66606:内容类型标头值应存放在content_type，但内置的Web服务器中存储在$_SERVER['HTTP_CONTT_TYPE and HTTP_CONTENT_LENGTH 字段中的 ContenENT_TYPE']，而没有$_SERVER['CONTENT_TYPE'] （这也可能会影响CONTENT_LENGTH）
        //
        $server = $_SERVER;
        if ('cli-server' === PHP_SAPI) {
            if (array_key_exists('HTTP_CONTENT_LENGTH', $_SERVER)) {
                $server['CONTENT_LENGTH'] = $_SERVER['HTTP_CONTENT_LENGTH'];
            }
            if (array_key_exists('HTTP_CONTENT_TYPE', $_SERVER)) {
                $server['CONTENT_TYPE'] = $_SERVER['HTTP_CONTENT_TYPE'];
            }
        }

        //工厂方式创建request对象
        $request = self::createRequestFromFactory($_GET, $_POST, array(), $_COOKIE, $_FILES, $server);
        // 如果CONTENT_TYPE是application/x-www-form-urlencoded，并且REQUEST_METHOD是put、delete、patch之一
        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), array('PUT', 'DELETE', 'PATCH'))
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data); // 根据参数创建ParameterBag对象赋给$request->request
        }

        return $request;
    }

    /**
     * Creates a Request based on a given URI and configuration.
     *
     * 根据给定的URI和配置创建请求
     *
     * The information contained in the URI always take precedence
     * over the other information (server and parameters).
     *
     * URI中包含的信息总是优先于其他信息（服务器和参数）
     *
     * @param string $uri        The URI
     * @param string $method     The HTTP method
     * @param array  $parameters The query (GET) or request (POST) parameters
     * @param array  $cookies    The request cookies ($_COOKIE)
     * @param array  $files      The request files ($_FILES)
     * @param array  $server     The server parameters ($_SERVER)
     * @param string $content    The raw body data
     *
     * @return static
     */
    public static function create($uri, $method = 'GET', $parameters = array(), $cookies = array(), $files = array(), $server = array(), $content = null)
    {
        $server = array_replace(array(
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Symfony/3.X',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
        ), $server);

        $server['PATH_INFO'] = '';
        $server['REQUEST_METHOD'] = strtoupper($method);

        $components = parse_url($uri);
        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST'] = $components['host'];
        }

        if (isset($components['scheme'])) {
            if ('https' === $components['scheme']) {
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            } else {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }

        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST'] = $server['HTTP_HOST'].':'.$components['port'];
        }

        if (isset($components['user'])) {
            $server['PHP_AUTH_USER'] = $components['user'];
        }

        if (isset($components['pass'])) {
            $server['PHP_AUTH_PW'] = $components['pass'];
        }

        if (!isset($components['path'])) {
            $components['path'] = '/';
        }

        switch (strtoupper($method)) {
            case 'POST':
            case 'PUT':
            case 'DELETE':
                if (!isset($server['CONTENT_TYPE'])) {
                    $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
                }
                // no break
            case 'PATCH':
                $request = $parameters;
                $query = array();
                break;
            default:
                $request = array();
                $query = $parameters;
                break;
        }

        $queryString = '';
        if (isset($components['query'])) {
            parse_str(html_entity_decode($components['query']), $qs);

            if ($query) {
                $query = array_replace($qs, $query);
                $queryString = http_build_query($query, '', '&');
            } else {
                $query = $qs;
                $queryString = $components['query'];
            }
        } elseif ($query) {
            $queryString = http_build_query($query, '', '&');
        }

        $server['REQUEST_URI'] = $components['path'].('' !== $queryString ? '?'.$queryString : '');
        $server['QUERY_STRING'] = $queryString;

        // 从工厂方法创建请求
        return self::createRequestFromFactory($query, $request, array(), $cookies, $files, $server, $content);
    }

    /**
     * Sets a callable able to create a Request instance.
     *
     * 设置可调用的可创建请求实例
     *
     * This is mainly useful when you need to override the Request class
     * to keep BC with an existing system. It should not be used for any
     * other purpose.
     *
     * 当需要重写请求类以使BC与现有系统保持一致时，这是非常有用的。它不应用于任何其他目的。
     *
     * @param callable|null $callable A PHP callable
     */
    public static function setFactory($callable)
    {
        self::$requestFactory = $callable;
    }

    /**
     * Clones a request and overrides some of its parameters.
     *
     * 克隆一个请求并覆盖它的一些参数
     *
     * @param array $query      The GET parameters
     * @param array $request    The POST parameters
     * @param array $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array $cookies    The COOKIE parameters
     * @param array $files      The FILES parameters
     * @param array $server     The SERVER parameters
     *
     * @return static
     */
    public function duplicate(array $query = null, array $request = null, array $attributes = null, array $cookies = null, array $files = null, array $server = null)
    {
        $dup = clone $this;
        if ($query !== null) {
            $dup->query = new ParameterBag($query);
        }
        if ($request !== null) {
            $dup->request = new ParameterBag($request);
        }
        if ($attributes !== null) {
            $dup->attributes = new ParameterBag($attributes);
        }
        if ($cookies !== null) {
            $dup->cookies = new ParameterBag($cookies);
        }
        if ($files !== null) {
            $dup->files = new FileBag($files);
        }
        if ($server !== null) {
            $dup->server = new ServerBag($server);
            $dup->headers = new HeaderBag($dup->server->getHeaders());
        }
        $dup->languages = null;
        $dup->charsets = null;
        $dup->encodings = null;
        $dup->acceptableContentTypes = null;
        $dup->pathInfo = null;
        $dup->requestUri = null;
        $dup->baseUrl = null;
        $dup->basePath = null;
        $dup->method = null;
        $dup->format = null;

        // 获取请求格式
        if (!$dup->get('_format') && $this->get('_format')) {
            $dup->attributes->set('_format', $this->get('_format'));
        }
        if (!$dup->getRequestFormat(null)) {
            //设置请求的format格式
            $dup->setRequestFormat($this->getRequestFormat(null));
        }

        return $dup;
    }

    /**
     * Clones the current request.
     *
     * 当前请求对象复制
     *
     * Note that the session is not cloned as duplicated requests
     * are most of the time sub-requests of the main one.
     *
     * 注意，会话不是被克隆的，因为重复请求大部分是主请求的子请求
     *
     */
    public function __clone()
    {
        $this->query = clone $this->query;
        $this->request = clone $this->request;
        $this->attributes = clone $this->attributes;
        $this->cookies = clone $this->cookies;
        $this->files = clone $this->files;
        $this->server = clone $this->server;
        $this->headers = clone $this->headers;
    }

    /**
     * Returns the request as a string.
     *
     * 将请求返回为字符串
     *
     * @return string The request
     */
    public function __toString()
    {
        try {
            $content = $this->getContent();
        } catch (\LogicException $e) {
            return trigger_error($e, E_USER_ERROR);
        }

        return
            sprintf('%s %s %s', $this->getMethod(), $this->getRequestUri(), $this->server->get('SERVER_PROTOCOL'))."\r\n".
            $this->headers."\r\n".
            $content;
    }

    /**
     * Overrides the PHP global variables according to this request instance.
     *
     * 重写PHP global 变量数组通过reques实例
     *
     *
     * It overrides $_GET, $_POST, $_REQUEST, $_SERVER, $_COOKIE.
     * $_FILES is never overridden, see rfc1867
     */
    public function overrideGlobals()
    {
        //                                  正常的查询字符串
        $this->server->set('QUERY_STRING', static::normalizeQueryString(http_build_query($this->query->all(), null, '&')));

        $_GET = $this->query->all();
        $_POST = $this->request->all();
        $_SERVER = $this->server->all();
        $_COOKIE = $this->cookies->all();

        foreach ($this->headers->all() as $key => $value) {
            $key = strtoupper(str_replace('-', '_', $key));
            if (in_array($key, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
                $_SERVER[$key] = implode(', ', $value);
            } else {
                $_SERVER['HTTP_'.$key] = implode(', ', $value);
            }
        }

        $request = array('g' => $_GET, 'p' => $_POST, 'c' => $_COOKIE);

        $requestOrder = ini_get('request_order') ?: ini_get('variables_order');
        $requestOrder = preg_replace('#[^cgp]#', '', strtolower($requestOrder)) ?: 'gp';

        $_REQUEST = array();
        foreach (str_split($requestOrder) as $order) {
            $_REQUEST = array_merge($_REQUEST, $request[$order]);
        }
    }

    /**
     * Sets a list of trusted proxies.
     *
     * 设置受信任代理的列表
     *
     * You should only list the reverse proxies that you manage directly.
     *
     * 您只需列出您直接管理的反向代理
     *
     * @param array $proxies A list of trusted proxies
     */
    public static function setTrustedProxies(array $proxies)
    {
        self::$trustedProxies = $proxies;
    }

    /**
     * Gets the list of trusted proxies.
     *
     * 获取受信任代理的列表
     *
     * @return array An array of trusted proxies
     */
    public static function getTrustedProxies()
    {
        return self::$trustedProxies;
    }

    /**
     * Sets a list of trusted host patterns.
     *
     * 设置受信任宿主模式的列表
     *
     * You should only list the hosts you manage using regexs.
     *
     * 你应该只列出主机你管理使用正则表达式
     *
     * @param array $hostPatterns A list of trusted host patterns
     */
    public static function setTrustedHosts(array $hostPatterns)
    {
        self::$trustedHostPatterns = array_map(function ($hostPattern) {
            return sprintf('#%s#i', $hostPattern);
        }, $hostPatterns);
        // we need to reset trusted hosts on trusted host patterns change
        // 我们需要重置受信任主机模式的信任主机
        self::$trustedHosts = array();
    }

    /**
     * Gets the list of trusted host patterns.
     *
     * 获取受信任宿主模式的列表
     *
     * @return array An array of trusted host patterns
     */
    public static function getTrustedHosts()
    {
        return self::$trustedHostPatterns;
    }

    /**
     * Sets the name for trusted headers.
     *
     * 设置可信头的名称
     *
     * The following header keys are supported:
     * 支持下列头键：
     *
     *  * Request::HEADER_CLIENT_IP:    defaults to X-Forwarded-For   (see getClientIp())
     *  * Request::HEADER_CLIENT_HOST:  defaults to X-Forwarded-Host  (see getHost())
     *  * Request::HEADER_CLIENT_PORT:  defaults to X-Forwarded-Port  (see getPort())
     *  * Request::HEADER_CLIENT_PROTO: defaults to X-Forwarded-Proto (see getScheme() and isSecure())
     *  * Request::HEADER_FORWARDED:    defaults to Forwarded         (see RFC 7239)
     *
     * Setting an empty value allows to disable the trusted header for the given key.
     * 设置空值允许为给定键禁用受信任的标头
     *
     * @param string $key   The header key
     * @param string $value The header name
     *
     * @throws \InvalidArgumentException
     */
    public static function setTrustedHeaderName($key, $value)
    {
        if (!array_key_exists($key, self::$trustedHeaders)) {
            throw new \InvalidArgumentException(sprintf('Unable to set the trusted header name for key "%s".', $key));
        }

        self::$trustedHeaders[$key] = $value;
    }

    /**
     * Gets the trusted proxy header name.
     *
     * 获取受信任的代理头名称
     *
     * @param string $key The header key
     *
     * @return string The header name
     *
     * @throws \InvalidArgumentException
     */
    public static function getTrustedHeaderName($key)
    {
        if (!array_key_exists($key, self::$trustedHeaders)) {
            throw new \InvalidArgumentException(sprintf('Unable to get the trusted header name for key "%s".', $key));
        }

        return self::$trustedHeaders[$key];
    }

    /**
     * Normalizes a query string.
     *
     * 正常的查询字符串
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized,
     * have consistent escaping and unneeded delimiters are removed.
     *
     * 它建立了一个标准的查询字符串，其中的键/值对的按字母顺序排列，有一致的escaping和不必要的分隔符被删除
     *
     * @param string $qs Query string
     *
     * @return string A normalized query string for the Request
     */
    public static function normalizeQueryString($qs)
    {
        if ('' == $qs) {
            return '';
        }

        $parts = array();
        $order = array();

        foreach (explode('&', $qs) as $param) {
            if ('' === $param || '=' === $param[0]) {
                // Ignore useless delimiters, e.g. "x=y&".
                // Also ignore pairs with empty key, even if there was a value, e.g. "=value", as such nameless values cannot be retrieved anyway.
                // PHP also does not include them when building _GET.
                // 忽略无用的分隔符，如"x=y&"
                // 也忽略对用空键，即使有一个值，如“=值”，因为这样的无名值不能检索
                // PHP也不在$_GET中包括它们
                continue;
            }

            $keyValuePair = explode('=', $param, 2);

            // GET parameters, that are submitted from a HTML form, encode spaces as "+" by default (as defined in enctype application/x-www-form-urlencoded).
            // PHP also converts "+" to spaces when filling the global _GET or when using the function parse_str. This is why we use urldecode and then normalize to
            // RFC 3986 with rawurlencode.
            // 得到的参数，这是从一个HTML表单提交，编码空格为“+”默认情况下（如定义enctype application/x-www-form-urlencoded）。
            // PHP在全局$_GET中也将“+”转换为空格。这是为什么我们使用urldecode以及rawurlencode RFC 3986规范。
            //
            $parts[] = isset($keyValuePair[1]) ?
                rawurlencode(urldecode($keyValuePair[0])).'='.rawurlencode(urldecode($keyValuePair[1])) :
                rawurlencode(urldecode($keyValuePair[0]));
            $order[] = urldecode($keyValuePair[0]);
        }

        array_multisort($order, SORT_ASC, $parts);

        return implode('&', $parts);
    }

    /**
     * Enables support for the _method request parameter to determine the intended HTTP method.
     *
     * 可实现对_method请求参数来确定预期的HTTP方法的支持
     *
     * Be warned that enabling this feature might lead to CSRF issues in your code.
     * 注意，启用此功能可能会导致CSRF问题代码。
     * Check that you are using CSRF tokens when required.
     * 检查是否正在使用CSRF令牌。
     * If the HTTP method parameter override is enabled, an html-form with method "POST" can be altered
     * and used to send a "PUT" or "DELETE" request via the _method request parameter.
     * 如果HTTP方法参数重写启用HTML形式，方法的“post”是可以被改变的，用来让“put”或“delete”通过_method请求参数的要求。
     *
     * If these methods are not protected against CSRF, this presents a possible vulnerability.
     * 如果这些方法都不免受CSRF，这提出了一个可能的漏洞。
     *
     * The HTTP method can only be overridden when the real HTTP method is POST.
     * HTTP方法只能在实际HTTP方法POST后被重写。
     */
    public static function enableHttpMethodParameterOverride()
    {
        self::$httpMethodParameterOverride = true;
    }

    /**
     * Checks whether support for the _method request parameter is enabled.
     *
     * 检查是否为_method请求参数支持
     *
     * @return bool True when the _method request parameter is enabled, false otherwise
     */
    public static function getHttpMethodParameterOverride()
    {
        return self::$httpMethodParameterOverride;
    }

    /**
     * Gets a "parameter" value from any bag.
     *
     * 从任何包获取“参数”值
     *
     * This method is mainly useful for libraries that want to provide some flexibility. If you don't need the
     * flexibility in controllers, it is better to explicitly get request parameters from the appropriate
     * public property instead (attributes, query, request).
     *
     * 此方法主要用于希望提供一些灵活性的库
     * 如果不需要控制器的灵活性，最好从适当的公共属性（属性、查询、请求）显式地获取请求参数
     *
     * Order of precedence: PATH (routing placeholders or custom attributes), GET, BODY
     * 优先顺序：路由占位符或自定义属性，GET,POST
     *
     * @param string $key     the key
     * @param mixed  $default the default value if the parameter key does not exist
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this !== $result = $this->attributes->get($key, $this)) {
            return $result;
        }

        if ($this !== $result = $this->query->get($key, $this)) {
            return $result;
        }

        if ($this !== $result = $this->request->get($key, $this)) {
            return $result;
        }

        return $default;
    }

    /**
     * Gets the Session.
     *
     * 获取session
     *
     * @return SessionInterface|null The session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Whether the request contains a Session which was started in one of the
     * previous requests.
     *
     * 请求是否包含在以前的请求中启动的会话
     *
     * @return bool
     */
    public function hasPreviousSession()
    {
        // the check for $this->session avoids malicious users trying to fake a session cookie with proper name
        // 检查避免恶意用户试图伪造会话cookie与适当的名称
        return $this->hasSession() && $this->cookies->has($this->session->getName());
    }

    /**
     * Whether the request contains a Session object.
     *
     * 请求包含session对象
     *
     * This method does not give any information about the state of the session object,
     * like whether the session is started or not. It is just a way to check if this Request
     * is associated with a Session instance.
     *
     * 此方法不提供有关会话对象状态的任何信息，例如会话是否启动。它只是检查这个请求是否与会话实例关联的一种方法。
     *
     * @return bool true when the Request contains a Session object, false otherwise
     */
    public function hasSession()
    {
        return null !== $this->session;
    }

    /**
     * Sets the Session.
     *
     * 设置Session
     *
     * @param SessionInterface $session The Session
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Returns the client IP addresses.
     *
     * 返回客户端IP地址集合
     *
     * In the returned array the most trusted IP address is first, and the
     * least trusted one last. The "real" client IP address is the last one,
     * but this is also the least trusted one. Trusted proxies are stripped.
     *
     * 在返回的数组中，最受信任的IP地址是第一个，最不可信的是最后一个
     * “真正的”客户端IP地址是最后一个，但这也是最不可信的
     * 信任代理被剥离
     *
     * Use this method carefully; you should use getClientIp() instead.
     *
     * 小心地使用这个方法；你应该使用getClientIp()代替。
     *
     * @return array The client IP addresses
     *
     * @see getClientIp()
     */
    public function getClientIps()
    {
        $clientIps = array();
        $ip = $this->server->get('REMOTE_ADDR');

        if (!$this->isFromTrustedProxy()) { // 指示此请求是否源于受信任的代理
            return array($ip);
        }

        $hasTrustedForwardedHeader = self::$trustedHeaders[self::HEADER_FORWARDED] && $this->headers->has(self::$trustedHeaders[self::HEADER_FORWARDED]);
        $hasTrustedClientIpHeader = self::$trustedHeaders[self::HEADER_CLIENT_IP] && $this->headers->has(self::$trustedHeaders[self::HEADER_CLIENT_IP]);

        if ($hasTrustedForwardedHeader) {
            $forwardedHeader = $this->headers->get(self::$trustedHeaders[self::HEADER_FORWARDED]);
            preg_match_all('{(for)=("?\[?)([a-z0-9\.:_\-/]*)}', $forwardedHeader, $matches);
            $forwardedClientIps = $matches[3];

            $forwardedClientIps = $this->normalizeAndFilterClientIps($forwardedClientIps, $ip); // 标准化和过滤客户端IP
            $clientIps = $forwardedClientIps;
        }

        if ($hasTrustedClientIpHeader) {
            $xForwardedForClientIps = array_map('trim', explode(',', $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_IP])));

            $xForwardedForClientIps = $this->normalizeAndFilterClientIps($xForwardedForClientIps, $ip); // 标准化和过滤客户端IP
            $clientIps = $xForwardedForClientIps;
        }

        if ($hasTrustedForwardedHeader && $hasTrustedClientIpHeader && $forwardedClientIps !== $xForwardedForClientIps) {
            throw new ConflictingHeadersException('The request has both a trusted Forwarded header and a trusted Client IP header, conflicting with each other with regards to the originating IP addresses of the request. This is the result of a misconfiguration. You should either configure your proxy only to send one of these headers, or configure Symfony to distrust one of them.');
        }

        if (!$hasTrustedForwardedHeader && !$hasTrustedClientIpHeader) {
            return $this->normalizeAndFilterClientIps(array(), $ip); // 标准化和过滤客户端IP
        }

        return $clientIps;
    }

    /**
     * Returns the client IP address.
     *
     * 返回客户端IP地址
     *
     * This method can read the client IP address from the "X-Forwarded-For" header
     * when trusted proxies were set via "setTrustedProxies()". The "X-Forwarded-For"
     * header value is a comma+space separated list of IP addresses, the left-most
     * being the original client, and each successive proxy that passed the request
     * adding the IP address where it received the request from.
     *
     * 当可信代理通过“setTrustedProxies()”这种方法可以读取客户端的IP地址从“X-Forwarded-For”头。
     * “X-Forwarded-For”头的值是一个逗号+空格分隔的IP地址列表中，最左边的是原来的客户，每一个连续的代理，通过添加IP地址那里收到的请求从请求
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-For",
     * ("Client-Ip" for instance), configure it via "setTrustedHeaderName()" with
     * the "client-ip" key.
     *
     * 如果你的反向代理使用不同的标题名称比“X-Forwarded-For”（“Client-Ip”为例），配置它通过“setTrustedHeaderName()”与“client-ip”键
     *
     * @return string The client IP address
     *
     * @see getClientIps()
     * @see http://en.wikipedia.org/wiki/X-Forwarded-For
     */
    public function getClientIp()
    {
        $ipAddresses = $this->getClientIps();

        return $ipAddresses[0];
    }

    /**
     * Returns current script name.
     *
     * 返回当前脚本名称
     *
     * @return string
     */
    public function getScriptName()
    {
        return $this->server->get('SCRIPT_NAME', $this->server->get('ORIG_SCRIPT_NAME', ''));
    }

    /**
     * Returns the path being requested relative to the executed script.
     *
     * 返回与被执行脚本相对应的路径
     *
     * The path info always starts with a /.
     *
     * 路径信息总是以/开始
     *
     * Suppose this request is instantiated from /mysite on localhost:
     * 假设localhost这个请求实例化 / mysite
     *
     *  * http://localhost/mysite              returns an empty string 返回空字符串
     *  * http://localhost/mysite/about        returns '/about' 返回/about
     *  * http://localhost/mysite/enco%20ded   returns '/enco%20ded' 返回/enco%20ded
     *  * http://localhost/mysite/about?var=1  returns '/about' 返回/about
     *
     * @return string The raw path (i.e. not urldecoded)
     */
    public function getPathInfo()
    {
        if (null === $this->pathInfo) {
            $this->pathInfo = $this->preparePathInfo(); //准备路径信息
        }

        return $this->pathInfo;
    }

    /**
     * Returns the root path from which this request is executed.
     *
     * 返回执行此请求的根路径
     *
     * Suppose that an index.php file instantiates this request object:
     *
     * 假设一个index.php文件实例化该请求对象
     *
     *  * http://localhost/index.php         returns an empty string 返回空字符串
     *  * http://localhost/index.php/page    returns an empty string 返回空字符串
     *  * http://localhost/web/index.php     returns '/web' 返回/web
     *  * http://localhost/we%20b/index.php  returns '/we%20b' 返回空字符串/we%20b
     *
     * @return string The raw path (i.e. not urldecoded)
     */
    public function getBasePath()
    {
        if (null === $this->basePath) {
            $this->basePath = $this->prepareBasePath(); //准备根路径信息
        }

        return $this->basePath;
    }

    /**
     * Returns the root URL from which this request is executed.
     *
     * 返回执行此请求的根URL
     *
     * The base URL never ends with a /.
     *
     * 基础URL永远不会以/结束
     *
     * This is similar to getBasePath(), except that it also includes the
     * script filename (e.g. index.php) if one exists.
     *
     * 这是类似于getBasePath()，除非它也包含脚本文件名（例如index.php）。
     *
     * @return string The raw URL (i.e. not urldecoded)
     */
    public function getBaseUrl()
    {
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->prepareBaseUrl();
        }

        return $this->baseUrl;
    }

    /**
     * Gets the request's scheme.
     *
     * 获取请求的协议
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Returns the port on which the request is made.
     *
     * 返回请求的端口
     *
     * This method can read the client port from the "X-Forwarded-Port" header
     * when trusted proxies were set via "setTrustedProxies()".
     *
     * 当可信代理通过“setTrustedProxies()” 这种方法可以读取客户端“X-Forwarded-Port”头。
     *
     * The "X-Forwarded-Port" header must contain the client port.
     *
     * “X-Forwarded-Port”头必须包含客户端口
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-Port",
     * configure it via "setTrustedHeaderName()" with the "client-port" key.
     *
     * 如果你的反向代理使用不同的头名称“X-Forwarded-Port”，通过配置“setTrustedHeaderName()”和“客户端”的key。
     *
     * @return string
     */
    public function getPort()
    {
        if ($this->isFromTrustedProxy()) {
            if (self::$trustedHeaders[self::HEADER_CLIENT_PORT] && $port = $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_PORT])) {
                return $port;
            }

            if (self::$trustedHeaders[self::HEADER_CLIENT_PROTO] && 'https' === $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_PROTO], 'http')) {
                return 443;
            }
        }

        if ($host = $this->headers->get('HOST')) {
            if ($host[0] === '[') {
                $pos = strpos($host, ':', strrpos($host, ']'));
            } else {
                $pos = strrpos($host, ':');
            }

            if (false !== $pos) {
                return (int) substr($host, $pos + 1);
            }

            return 'https' === $this->getScheme() ? 443 : 80;
        }

        return $this->server->get('SERVER_PORT');
    }

    /**
     * Returns the user.
     *
     * 返回用户
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->headers->get('PHP_AUTH_USER');
    }

    /**
     * Returns the password.
     *
     * 返回密码
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->headers->get('PHP_AUTH_PW');
    }

    /**
     * Gets the user info.
     *
     * 获取用户信息
     *
     * @return string A user name and, optionally, scheme-specific information about how to gain authorization to access the server
     */
    public function getUserInfo()
    {
        $userinfo = $this->getUser(); // 返回用户

        $pass = $this->getPassword(); // 返回密码
        if ('' != $pass) {
            $userinfo .= ":$pass";
        }

        return $userinfo;
    }

    /**
     * Returns the HTTP host being requested.
     *
     * 返回被请求的HTTP主机
     *
     * The port name will be appended to the host if it's non-standard.
     *
     * 如果非标准的端口名称将被附加到主机
     *
     * @return string
     */
    public function getHttpHost()
    {
        $scheme = $this->getScheme(); //获取请求的协议
        $port = $this->getPort(); // 返回请求的端口

        if (('http' == $scheme && $port == 80) || ('https' == $scheme && $port == 443)) {
            return $this->getHost(); //返回主机名
        }

        return $this->getHost().':'.$port; // 返回主机名:port
    }

    /**
     * Returns the requested URI (path and query string).
     *
     * 返回请求的URI（路径和查询字符串）
     *
     * @return string The raw URI (i.e. not URI decoded)
     */
    public function getRequestUri()
    {
        if (null === $this->requestUri) {
            $this->requestUri = $this->prepareRequestUri();
        }

        return $this->requestUri;
    }

    /**
     * Gets the scheme and HTTP host.
     *
     * 获取协议和HTTP主机
     *
     * If the URL was called with basic authentication, the user
     * and the password are not added to the generated string.
     *
     * 如果用基本身份验证来调用URL，则不会将用户和密码添加到生成的字符串中
     *
     * @return string The scheme and HTTP host
     */
    public function getSchemeAndHttpHost()
    {
        return $this->getScheme().'://'.$this->getHttpHost();
    }

    /**
     * Generates a normalized URI (URL) for the Request.
     *
     * 为请求生成一个标准化URI（URL）
     *
     * @return string A normalized URI (URL) for the Request
     *
     * @see getQueryString()
     */
    public function getUri()
    {
        if (null !== $qs = $this->getQueryString()) {   // 为请求生成标准化查询字符串
            $qs = '?'.$qs;
        }
        //           获取协议和HTTP主机        . 返回执行此请求的根URL . 返回与被执行脚本相对应的路径 .查询字符串
        return $this->getSchemeAndHttpHost().$this->getBaseUrl().$this->getPathInfo().$qs;
    }

    /**
     * Generates a normalized URI for the given path.
     *
     * 为给定路径生成标准化URI
     *
     * @param string $path A path to use instead of the current one 使用的路径，而不是当前的
     *
     * @return string The normalized URI for the path
     */
    public function getUriForPath($path)
    {
        //               获取协议和HTTP主机 . 返回执行此请求的根URL . $path
        return $this->getSchemeAndHttpHost().$this->getBaseUrl().$path;
    }

    /**
     * Returns the path as relative reference from the current Request path.
     *
     * 将路径作为当前请求路径的相对引用返回
     *
     * Only the URIs path component (no schema, host etc.) is relevant and must be given.
     * Both paths must be absolute and not contain relative parts.
     * Relative URLs from one resource to another are useful when generating self-contained downloadable document archives.
     * Furthermore, they can be used to reduce the link size in documents.
     *
     * 肯定的是，我们只必须处理相关的URI路径组件（无模式、主机等）
     * 这两个路径必须是绝对的，而不是包含相关部分
     * 从一个资源到另一个资源的相对URL在生成独立的可下载文档库时非常有用此外
     * 他们可以用来降低documents与绝对路径链接尺寸
     *
     * Example target paths, given a base path of "/a/b/c/d":
     * 例如目标由径，给定一个基本路由
     *
     * - "/a/b/c/d"     -> ""
     * - "/a/b/c/"      -> "./"
     * - "/a/b/"        -> "../"
     * - "/a/b/c/other" -> "other"
     * - "/a/x/y"       -> "../../x/y"
     *
     * @param string $path The target path
     *
     * @return string The relative target path
     */
    public function getRelativeUriForPath($path)
    {
        // be sure that we are dealing with an absolute path
        // 确保我们正在处理一条绝对路径
        if (!isset($path[0]) || '/' !== $path[0]) {
            return $path;
        }

        if ($path === $basePath = $this->getPathInfo()) { //返回与被执行脚本相对应的路径
            return '';
        }

        $sourceDirs = explode('/', isset($basePath[0]) && '/' === $basePath[0] ? substr($basePath, 1) : $basePath);
        $targetDirs = explode('/', isset($path[0]) && '/' === $path[0] ? substr($path, 1) : $path);
        array_pop($sourceDirs);
        $targetFile = array_pop($targetDirs);

        foreach ($sourceDirs as $i => $dir) {
            if (isset($targetDirs[$i]) && $dir === $targetDirs[$i]) {
                unset($sourceDirs[$i], $targetDirs[$i]);
            } else {
                break;
            }
        }

        $targetDirs[] = $targetFile;
        $path = str_repeat('../', count($sourceDirs)).implode('/', $targetDirs);

        // A reference to the same base directory or an empty subdirectory must be prefixed with "./".
        // This also applies to a segment with a colon character (e.g., "file:colon") that cannot be used
        // as the first segment of a relative-path reference, as it would be mistaken for a scheme name
        // (see http://tools.ietf.org/html/rfc3986#section-4.2).
        //
        // 确保我们正在处理一条绝对路径
        //
        return !isset($path[0]) || '/' === $path[0]
            || false !== ($colonPos = strpos($path, ':')) && ($colonPos < ($slashPos = strpos($path, '/')) || false === $slashPos)
            ? "./$path" : $path;
    }

    /**
     * Generates the normalized query string for the Request.
     *
     * 为请求生成标准化查询字符串
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized
     * and have consistent escaping.
     *
     * 它建立了一个标准的查询字符串，其中的键/值对是按字母顺序排列的和一致的escaping
     *
     * @return string|null A normalized query string for the Request
     */
    public function getQueryString()
    {
        $qs = static::normalizeQueryString($this->server->get('QUERY_STRING')); // 正常的查询字符串

        return '' === $qs ? null : $qs;
    }

    /**
     * Checks whether the request is secure or not.
     *
     * 检查请求是否安全
     *
     * This method can read the client protocol from the "X-Forwarded-Proto" header
     * 这种方法可以读取客户端协议从“x-forwarded-proto”头
     * when trusted proxies were set via "setTrustedProxies()".
     * 当信任代理是通过"setTrustedProxies()"
     *
     * The "X-Forwarded-Proto" header must contain the protocol: "https" or "http".
     * "X-Forwarded-Proto"头部必须包含的协议：“https”或“http”
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-Proto"
     * ("SSL_HTTPS" for instance), configure it via "setTrustedHeaderName()" with
     * the "client-proto" key.
     *
     * 如果你的反向代理使用不同的标题名称“X-Forwarded-Proto”，（“SSL_HTTPS”为例），通过“setTrustedHeaderName()”配置“client-proto”
     *
     * @return bool
     */
    public function isSecure()
    {
        //           指示此请求是否源于受信任的代理
        if ($this->isFromTrustedProxy() && self::$trustedHeaders[self::HEADER_CLIENT_PROTO] && $proto = $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_PROTO])) {
            return in_array(strtolower(current(explode(',', $proto))), array('https', 'on', 'ssl', '1'));
        }

        $https = $this->server->get('HTTPS');

        return !empty($https) && 'off' !== strtolower($https);
    }

    /**
     * Returns the host name.
     *
     * 返回主机名
     *
     * This method can read the client host name from the "X-Forwarded-Host" header
     * when trusted proxies were set via "setTrustedProxies()".
     *
     * 当可信代理通过“settrustedproxies()”这种方法可以读取客户端主机名称从“x-forwarded-host”头。
     *
     * The "X-Forwarded-Host" header must contain the client host name.
     * “X-Forwarded-Host”标题必须包含客户端的主机名
     *
     * If your reverse proxy uses a different header name than "X-Forwarded-Host",
     * configure it via "setTrustedHeaderName()" with the "client-host" key.
     *
     * 如果你的反向代理使用不同的标题名称“X-Forwarded-Host”配置，通过“setTrustedHeaderName()”和“客户端”配置key。
     *
     * @return string
     *
     * @throws \UnexpectedValueException when the host name is invalid
     */
    public function getHost()
    {
        // 指示此请求是否源于受信任的代理
        if ($this->isFromTrustedProxy() && self::$trustedHeaders[self::HEADER_CLIENT_HOST] && $host = $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_HOST])) {
            $elements = explode(',', $host);

            $host = $elements[count($elements) - 1];
        } elseif (!$host = $this->headers->get('HOST')) {
            if (!$host = $this->server->get('SERVER_NAME')) {
                $host = $this->server->get('SERVER_ADDR', '');
            }
        }

        // trim and remove port number from host
        // host is lowercase as per RFC 952/2181
        // 从主机主机修剪和删除端口号是根据RFC 952 / 2181
        //
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

        // as the host can come from the user (HTTP_HOST and depending on the configuration, SERVER_NAME too can come from the user)
        // check that it does not contain forbidden characters (see RFC 952 and RFC 2181)
        // use preg_replace() instead of preg_match() to prevent DoS attacks with long host names
        //
        // 作为东道主可以来自用户（http_host和依赖根据配置，SERVER_NAME也可以来自用户）
        // 检查它不包含禁用的字符（参见RFC 952和RFC 2181）
        // 使用preg_replace()代替preg_match()防止DoS攻击长的主机名
        //
        if ($host && '' !== preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $host)) {
            throw new \UnexpectedValueException(sprintf('Invalid Host "%s"', $host));
        }

        if (count(self::$trustedHostPatterns) > 0) {
            // to avoid host header injection attacks, you should provide a list of trusted host patterns
            // 为了避免主机头注入攻击，应提供受信任宿主模式的列表

            if (in_array($host, self::$trustedHosts)) {
                return $host;
            }

            foreach (self::$trustedHostPatterns as $pattern) {
                if (preg_match($pattern, $host)) {
                    self::$trustedHosts[] = $host;

                    return $host;
                }
            }

            throw new \UnexpectedValueException(sprintf('Untrusted Host "%s"', $host));
        }

        return $host;
    }

    /**
     * Sets the request method.
     *
     * 设置请求方法
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = null;
        $this->server->set('REQUEST_METHOD', $method);
    }

    /**
     * Gets the request "intended" method.
     *
     * 获取请求的“预期”方法
     *
     * If the X-HTTP-Method-Override header is set, and if the method is a POST,
     * then it is used to determine the "real" intended HTTP method.
     *
     * 如果X-HTTP-Method-Override头部设置过，并且方法是POST，就用它来确定“真正的”预期的HTTP方法。
     *
     * The _method request parameter can also be used to determine the HTTP method,
     * but only if enableHttpMethodParameterOverride() has been called.
     *
     * _method请求的参数也可以用来确定HTTP方法，但只有enableHttpMethodParameterOverride()被调用。
     *
     * The method is always an uppercased string.
     *
     * 方法始终是一个大写的字符串
     *
     * @return string The request method
     *
     * @see getRealMethod()
     */
    public function getMethod()
    {
        if (null === $this->method) {
            $this->method = strtoupper($this->server->get('REQUEST_METHOD', 'GET'));

            if ('POST' === $this->method) {
                if ($method = $this->headers->get('X-HTTP-METHOD-OVERRIDE')) {
                    $this->method = strtoupper($method);
                } elseif (self::$httpMethodParameterOverride) {
                    $this->method = strtoupper($this->request->get('_method', $this->query->get('_method', 'POST')));
                }
            }
        }

        return $this->method;
    }

    /**
     * Gets the "real" request method.
     *
     * 获取“真正的”请求方法
     *
     * @return string The request method
     *
     * @see getMethod()
     */
    public function getRealMethod()
    {
        return strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
    }

    /**
     * Gets the mime type associated with the format.
     *
     * 获取与格式关联的MIME类型
     *
     * @param string $format The format
     *
     * @return string The associated mime type (null if not found)
     */
    public function getMimeType($format)
    {
        if (null === static::$formats) {
            static::initializeFormats(); // 初始化HTTP请求格式
        }

        return isset(static::$formats[$format]) ? static::$formats[$format][0] : null;
    }

    /**
     * Gets the mime types associated with the format.
     *
     * 获取与格式关联的MIME类型
     *
     * @param string $format The format
     *
     * @return array The associated mime types
     */
    public static function getMimeTypes($format)
    {
        if (null === static::$formats) {
            static::initializeFormats(); // 初始化HTTP请求格式
        }

        return isset(static::$formats[$format]) ? static::$formats[$format] : array();
    }

    /**
     * Gets the format associated with the mime type.
     *
     * 获取与MIME类型关联的格式
     *
     * @param string $mimeType The associated mime type
     *
     * @return string|null The format (null if not found)
     */
    public function getFormat($mimeType)
    {
        $canonicalMimeType = null;
        if (false !== $pos = strpos($mimeType, ';')) {
            $canonicalMimeType = substr($mimeType, 0, $pos);
        }

        if (null === static::$formats) {
            static::initializeFormats();// 初始化HTTP请求格式
        }

        foreach (static::$formats as $format => $mimeTypes) {
            if (in_array($mimeType, (array) $mimeTypes)) {
                return $format;
            }
            if (null !== $canonicalMimeType && in_array($canonicalMimeType, (array) $mimeTypes)) {
                return $format;
            }
        }
    }

    /**
     * Associates a format with mime types.
     *
     * 将格式与MIME类型关联
     *
     * @param string       $format    The format
     * @param string|array $mimeTypes The associated mime types (the preferred one must be the first as it will be used as the content type) 相关的MIME类型（首选的必须是第一个，因为它将被用作内容类型）
     */
    public function setFormat($format, $mimeTypes)
    {
        if (null === static::$formats) {
            static::initializeFormats();// 初始化HTTP请求格式
        }

        static::$formats[$format] = is_array($mimeTypes) ? $mimeTypes : array($mimeTypes);
    }

    /**
     * Gets the request format.
     *
     * 获取请求格式
     *
     * Here is the process to determine the format:
     *
     * 这里是确定格式的过程：
     *
     *  * format defined by the user (with setRequestFormat()) 格式由用户定义的
     *  * _format request attribute _format请求属性
     *  * $default 默认参数
     *
     * @param string $default The default format
     *
     * @return string The request format
     */
    public function getRequestFormat($default = 'html')
    {
        if (null === $this->format) {
            $this->format = $this->attributes->get('_format');
        }

        return null === $this->format ? $default : $this->format;
    }

    /**
     * Sets the request format.
     *
     * 设置请求格式
     *
     * @param string $format The request format
     */
    public function setRequestFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Gets the format associated with the request.
     *
     * 获取与请求关联的格式
     *
     * @return string|null The format (null if no content type is present)
     */
    public function getContentType()
    {
        //          获取与MIME类型关联的格式
        return $this->getFormat($this->headers->get('CONTENT_TYPE'));
    }

    /**
     * Sets the default locale.
     *
     * 设置默认的区域设置
     *
     * @param string $locale
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;

        if (null === $this->locale) {
            $this->setPhpDefaultLocale($locale);
        }
    }

    /**
     * Get the default locale.
     *
     * 获取默认的区域设置
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Sets the locale.
     *
     * 设置区域设置
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->setPhpDefaultLocale($this->locale = $locale);
    }

    /**
     * Get the locale.
     *
     * 获取区域设置
     *
     * @return string
     */
    public function getLocale()
    {
        return null === $this->locale ? $this->defaultLocale : $this->locale;
    }

    /**
     * Checks if the request method is of specified type.
     *
     * 检查请求方法是否为指定类型
     *
     * @param string $method Uppercase request method (GET, POST etc) 大写的请求方法（GET，POST等）
     *
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * Checks whether or not the method is safe.
     *
     * 检查是否是一个安全的方法
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.2.1
     *
     * @param bool $andCacheable Adds the additional condition that the method should be cacheable. True by default. 添加方法应该是可缓存的附加条件。默认为真。
     *
     * @return bool
     */
    public function isMethodSafe(/* $andCacheable = true */)
    {
        if (!func_num_args() || func_get_arg(0)) {
            // This deprecation should be turned into a BadMethodCallException in 4.0 (without adding the argument in the signature)
            // then setting $andCacheable to false should be deprecated in 4.1
            // 在4.0中这个已弃用 应该是变成 BadMethodCallException 异常（不加在签名中的参数）
            // 4.1弃用设置$andCacheable为false
            @trigger_error('Checking only for cacheable HTTP methods with Symfony\Component\HttpFoundation\Request::isMethodSafe() is deprecated since version 3.2 and will throw an exception in 4.0. Disable checking only for cacheable methods by calling the method with `false` as first argument or use the Request::isMethodCacheable() instead.', E_USER_DEPRECATED);

            return in_array($this->getMethod(), array('GET', 'HEAD'));
        }

        return in_array($this->getMethod(), array('GET', 'HEAD', 'OPTIONS', 'TRACE'));
    }

    /**
     * Checks whether or not the method is idempotent.
     *
     * 检查是否是一个幂等的方法
     *
     * @return bool
     */
    public function isMethodIdempotent()
    {
        return in_array($this->getMethod(), array('HEAD', 'GET', 'PUT', 'DELETE', 'TRACE', 'OPTIONS', 'PURGE'));
    }

    /**
     * Checks whether the method is cacheable or not.
     *
     * 检查方法是否可缓存
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.2.3
     *
     * @return bool
     */
    public function isMethodCacheable()
    {
        return in_array($this->getMethod(), array('GET', 'HEAD'));
    }

    /**
     * Returns the request body content.
     *
     * 返回请求正文内容
     *
     * @param bool $asResource If true, a resource will be returned 如果是true，资源将被返回
     *
     * @return string|resource The request body content or a resource to read the body stream 请求正文内容或读取body流的资源
     *
     * @throws \LogicException
     */
    public function getContent($asResource = false)
    {
        $currentContentIsResource = is_resource($this->content);
        if (PHP_VERSION_ID < 50600 && false === $this->content) {
            //                                        当PHP低于5.6时使用资源返回类型只能调用一次
            throw new \LogicException('getContent() can only be called once when using the resource return type and PHP below 5.6.');
        }

        if (true === $asResource) {
            if ($currentContentIsResource) {
                rewind($this->content);

                return $this->content;
            }

            // Content passed in parameter (test)
            // 参数传递的内容（测试）
            if (is_string($this->content)) {
                $resource = fopen('php://temp', 'r+');
                fwrite($resource, $this->content);
                rewind($resource);

                return $resource;
            }

            $this->content = false;

            return fopen('php://input', 'rb');
        }

        if ($currentContentIsResource) {
            rewind($this->content); //重置指针
            // 将流读入字符串
            return stream_get_contents($this->content);
        }

        if (null === $this->content || false === $this->content) {
            $this->content = file_get_contents('php://input');
        }

        return $this->content;
    }

    /**
     * Gets the Etags.
     *
     * 获取Etags
     *
     * @return array The entity tags
     */
    public function getETags()
    {
        return preg_split('/\s*,\s*/', $this->headers->get('if_none_match'), null, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * 是否有缓存
     * @return bool
     */
    public function isNoCache()
    {
        //                        如果定义了缓存控制指令，则返回true
        return $this->headers->hasCacheControlDirective('no-cache') || 'no-cache' == $this->headers->get('Pragma');
    }

    /**
     * Returns the preferred language.
     *
     * 返回首选语言
     *
     * @param array $locales An array of ordered available locales 有序可用区域的数组
     *
     * @return string|null The preferred locale 首选语言
     */
    public function getPreferredLanguage(array $locales = null)
    {
        $preferredLanguages = $this->getLanguages();

        if (empty($locales)) {
            return isset($preferredLanguages[0]) ? $preferredLanguages[0] : null;
        }

        if (!$preferredLanguages) {
            return $locales[0];
        }

        $extendedPreferredLanguages = array();
        foreach ($preferredLanguages as $language) {
            $extendedPreferredLanguages[] = $language;
            if (false !== $position = strpos($language, '_')) {
                $superLanguage = substr($language, 0, $position);
                if (!in_array($superLanguage, $preferredLanguages)) {
                    $extendedPreferredLanguages[] = $superLanguage;
                }
            }
        }

        $preferredLanguages = array_values(array_intersect($extendedPreferredLanguages, $locales));

        return isset($preferredLanguages[0]) ? $preferredLanguages[0] : $locales[0];
    }

    /**
     * Gets a list of languages acceptable by the client browser.
     *
     * 获取客户端浏览器可接受的语言列表
     *
     * @return array Languages ordered in the user browser preferences
     */
    public function getLanguages()
    {
        if (null !== $this->languages) {
            return $this->languages;
        }

        $languages = AcceptHeader::fromString($this->headers->get('Accept-Language'))->all();
        $this->languages = array();
        foreach ($languages as $lang => $acceptHeaderItem) {
            if (false !== strpos($lang, '-')) {
                $codes = explode('-', $lang);
                if ('i' === $codes[0]) {
                    // Language not listed in ISO 639 that are not variants
                    // of any listed language, which can be registered with the
                    // i-prefix, such as i-cherokee
                    //
                    // 语言没有列在ISO 639，没有任何上市的语言变体，它可以与i-prefix注册，如i-cherokee
                    //
                    if (count($codes) > 1) {
                        $lang = $codes[1];
                    }
                } else {
                    for ($i = 0, $max = count($codes); $i < $max; ++$i) {
                        if ($i === 0) {
                            $lang = strtolower($codes[0]);
                        } else {
                            $lang .= '_'.strtoupper($codes[$i]);
                        }
                    }
                }
            }

            $this->languages[] = $lang;
        }

        return $this->languages;
    }

    /**
     * Gets a list of charsets acceptable by the client browser.
     *
     * 可以通过客户端浏览器获取一组字符集
     *
     * @return array List of charsets in preferable order 优选的顺序字符集列表
     */
    public function getCharsets()
    {
        if (null !== $this->charsets) {
            return $this->charsets;
        }
        //                                         建立一个字符串的AcceptHeader实例            获取charset      所有的key
        return $this->charsets = array_keys(AcceptHeader::fromString($this->headers->get('Accept-Charset'))->all());
    }

    /**
     * Gets a list of encodings acceptable by the client browser.
     *
     * 通过客户端浏览器获取编码
     *
     * @return array List of encodings in preferable order
     */
    public function getEncodings()
    {
        if (null !== $this->encodings) {
            return $this->encodings;
        }
        //                                         建立一个字符串的AcceptHeader实例            获取encoding      所有的key
        return $this->encodings = array_keys(AcceptHeader::fromString($this->headers->get('Accept-Encoding'))->all());
    }

    /**
     * Gets a list of content types acceptable by the client browser.
     *
     * 获取客户端浏览器可接受的内容类型列表
     *
     * @return array List of content types in preferable order
     */
    public function getAcceptableContentTypes()
    {
        if (null !== $this->acceptableContentTypes) {
            return $this->acceptableContentTypes;
        }
        //                                         建立一个字符串的AcceptHeader实例            获取Accept      所有的key
        return $this->acceptableContentTypes = array_keys(AcceptHeader::fromString($this->headers->get('Accept'))->all());
    }

    /**
     * Returns true if the request is a XMLHttpRequest.
     *
     * 如果请求是一个XMLHttpRequest返回true
     *
     * It works if your JavaScript library sets an X-Requested-With HTTP header.
     * 工作于你的JavaScript库设置一个x-requested-with HTTP头
     * It is known to work with common JavaScript frameworks:
     * 这是众所周知的工作与常见的JavaScript框架：
     *
     * @see http://en.wikipedia.org/wiki/List_of_Ajax_frameworks#JavaScript
     *
     * @return bool true if the request is an XMLHttpRequest, false otherwise
     */
    public function isXmlHttpRequest()
    {
        return 'XMLHttpRequest' == $this->headers->get('X-Requested-With');
    }

    /*
     * The following methods are derived from code of the Zend Framework (1.10dev - 2010-01-24)
     *
     * 以下的方法是Zend框架代码
     *
     * Code subject to the new BSD license (http://framework.zend.com/license/new-bsd).
     *
     * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
     */

    protected function prepareRequestUri()
    {
        $requestUri = '';

        if ($this->headers->has('X_ORIGINAL_URL')) {
            // IIS with Microsoft Rewrite Module
            // 微软IIS的重写模块
            $requestUri = $this->headers->get('X_ORIGINAL_URL');
            $this->headers->remove('X_ORIGINAL_URL');
            $this->server->remove('HTTP_X_ORIGINAL_URL');
            $this->server->remove('UNENCODED_URL');
            $this->server->remove('IIS_WasUrlRewritten');
        } elseif ($this->headers->has('X_REWRITE_URL')) {
            // IIS with ISAPI_Rewrite
            // 微软IIS的ISAPI_Rewrite
            $requestUri = $this->headers->get('X_REWRITE_URL');
            $this->headers->remove('X_REWRITE_URL');
        } elseif ($this->server->get('IIS_WasUrlRewritten') == '1' && $this->server->get('UNENCODED_URL') != '') {
            // IIS7 with URL Rewrite: make sure we get the unencoded URL (double slash problem)
            // IIS7 URL 重写：确保我们得到的未编码的URL（双斜线的问题）
            $requestUri = $this->server->get('UNENCODED_URL');
            $this->server->remove('UNENCODED_URL');
            $this->server->remove('IIS_WasUrlRewritten');
        } elseif ($this->server->has('REQUEST_URI')) {
            $requestUri = $this->server->get('REQUEST_URI');
            // HTTP proxy reqs setup request URI with scheme and host [and port] + the URL path, only use URL path
            // HTTP代理的请求设置请求的URI的协议和host[端口] + URL路径，只使用URL路径
            $schemeAndHttpHost = $this->getSchemeAndHttpHost();
            if (strpos($requestUri, $schemeAndHttpHost) === 0) {
                $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
            }
        } elseif ($this->server->has('ORIG_PATH_INFO')) {
            // IIS 5.0, PHP as CGI
            $requestUri = $this->server->get('ORIG_PATH_INFO');
            if ('' != $this->server->get('QUERY_STRING')) {
                $requestUri .= '?'.$this->server->get('QUERY_STRING');
            }
            $this->server->remove('ORIG_PATH_INFO');
        }

        // normalize the request URI to ease creating sub-requests from this request
        // 使请求URI正常化，以便于从该请求中创建子请求
        $this->server->set('REQUEST_URI', $requestUri);

        return $requestUri;
    }

    /**
     * Prepares the base URL.
     *
     * 准备基础URL
     *
     * @return string
     */
    protected function prepareBaseUrl()
    {
        $filename = basename($this->server->get('SCRIPT_FILENAME'));

        if (basename($this->server->get('SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->server->get('SCRIPT_NAME');
        } elseif (basename($this->server->get('PHP_SELF')) === $filename) {
            $baseUrl = $this->server->get('PHP_SELF');
        } elseif (basename($this->server->get('ORIG_SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->server->get('ORIG_SCRIPT_NAME'); // 1and1 shared hosting compatibility  1and1 共享主机的兼容性
        } else {
            // Backtrack up the script_filename to find the portion matching
            // 回溯到 script_filename 查找部分匹配
            // php_self
            $path = $this->server->get('PHP_SELF', '');
            $file = $this->server->get('SCRIPT_FILENAME', '');
            $segs = explode('/', trim($file, '/'));
            $segs = array_reverse($segs);
            $index = 0;
            $last = count($segs);
            $baseUrl = '';
            do {
                $seg = $segs[$index];
                $baseUrl = '/'.$seg.$baseUrl;
                ++$index;
            } while ($last > $index && (false !== $pos = strpos($path, $baseUrl)) && 0 != $pos);
        }

        // Does the baseUrl have anything in common with the request_uri?
        // baseUrl和request_uri有什么共同之处？
        $requestUri = $this->getRequestUri();

        if ($baseUrl && false !== $prefix = $this->getUrlencodedPrefix($requestUri, $baseUrl)) { // 返回字符串中所编码的前缀
            // full $baseUrl matches
            return $prefix;
        }
        //                                           返回字符串中所编码的前缀
        if ($baseUrl && false !== $prefix = $this->getUrlencodedPrefix($requestUri, rtrim(dirname($baseUrl), '/'.DIRECTORY_SEPARATOR).'/')) {
            // directory portion of $baseUrl matches
            // 地址目录部分匹配
            return rtrim($prefix, '/'.DIRECTORY_SEPARATOR);
        }

        $truncatedRequestUri = $requestUri;
        if (false !== $pos = strpos($requestUri, '?')) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);
        if (empty($basename) || !strpos(rawurldecode($truncatedRequestUri), $basename)) {
            // no match whatsoever; set it blank
            // 不匹配；设置为空白
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of baseUrl. $pos !== 0 makes sure it is not matching a value
        // from PATH_INFO or QUERY_STRING
        //
        // 如果使用mod_rewrite或isapi_rewrite从baseUrl剥离脚本文件名。$pos!==0确保它不匹配QUERY_STRING或PATH_INFO
        //
        if (strlen($requestUri) >= strlen($baseUrl) && (false !== $pos = strpos($requestUri, $baseUrl)) && $pos !== 0) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return rtrim($baseUrl, '/'.DIRECTORY_SEPARATOR);
    }

    /**
     * Prepares the base path.
     *
     * 准备根路径信息
     *
     * @return string base path
     */
    protected function prepareBasePath()
    {
        $filename = basename($this->server->get('SCRIPT_FILENAME'));
        $baseUrl = $this->getBaseUrl();
        if (empty($baseUrl)) {
            return '';
        }

        if (basename($baseUrl) === $filename) {
            $basePath = dirname($baseUrl);
        } else {
            $basePath = $baseUrl;
        }

        if ('\\' === DIRECTORY_SEPARATOR) {
            $basePath = str_replace('\\', '/', $basePath);
        }

        return rtrim($basePath, '/');
    }

    /**
     * Prepares the path info.
     *
     * 准备路径信息
     *
     * @return string path info
     */
    protected function preparePathInfo()
    {
        $baseUrl = $this->getBaseUrl();
        //                           返回请求的URI（路径和查询字符串）
        if (null === ($requestUri = $this->getRequestUri())) {
            return '/';
        }

        // Remove the query string from REQUEST_URI
        // 从REQUEST_URI中删除查询字符串
        if ($pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        $pathInfo = substr($requestUri, strlen($baseUrl));
        if (null !== $baseUrl && (false === $pathInfo || '' === $pathInfo)) {
            // If substr() returns false then PATH_INFO is set to an empty string
            // 如果substr()返回false，PATH_INFO设置为空的字符串
            return '/';
        } elseif (null === $baseUrl) {
            return $requestUri;
        }

        return (string) $pathInfo;
    }

    /**
     * Initializes HTTP request formats.
     * 初始化HTTP请求格式
     */
    protected static function initializeFormats()
    {
        static::$formats = array(
            'html' => array('text/html', 'application/xhtml+xml'),
            'txt' => array('text/plain'),
            'js' => array('application/javascript', 'application/x-javascript', 'text/javascript'),
            'css' => array('text/css'),
            'json' => array('application/json', 'application/x-json'),
            'xml' => array('text/xml', 'application/xml', 'application/x-xml'),
            'rdf' => array('application/rdf+xml'),
            'atom' => array('application/atom+xml'),
            'rss' => array('application/rss+xml'),
            'form' => array('application/x-www-form-urlencoded'),
        );
    }

    /**
     * Sets the default PHP locale.
     *
     * 设置默认的PHP区域设置
     *
     * @param string $locale
     */
    private function setPhpDefaultLocale($locale)
    {
        // if either the class Locale doesn't exist, or an exception is thrown when
        // setting the default locale, the intl module is not installed, and
        // the call can be ignored:
        //
        // 如果类区域设置不存在，或者在设置默认区域时抛出异常，则没有安装国际模块，并且调用可以忽略：
        //
        try {
            if (class_exists('Locale', false)) {
                \Locale::setDefault($locale);
            }
        } catch (\Exception $e) {
        }
    }

    /*
     * Returns the prefix as encoded in the string when the string starts with
     * the given prefix, false otherwise.
     *
     * 当字符串以给定前缀开头时返回字符串中所编码的前缀，否则为false
     *
     * @param string $string The urlencoded string url编码的字符串
     * @param string $prefix The prefix not encoded 前缀未编码
     *
     * @return string|false The prefix as it is encoded in $string, or false
     */
    private function getUrlencodedPrefix($string, $prefix)
    {
        if (0 !== strpos(rawurldecode($string), $prefix)) {
            return false;
        }

        $len = strlen($prefix);

        if (preg_match(sprintf('#^(%%[[:xdigit:]]{2}|.){%d}#', $len), $string, $match)) {
            return $match[0];
        }

        return false;
    }
    //从工厂创建请求
    private static function createRequestFromFactory(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {
        // 如果请求工厂已存在
        if (self::$requestFactory) {
            $request = call_user_func(self::$requestFactory, $query, $request, $attributes, $cookies, $files, $server, $content);

            if (!$request instanceof self) {
                throw new \LogicException('The Request factory must return an instance of Symfony\Component\HttpFoundation\Request.');
            }

            return $request;
        }
        //返回当前实例__construct
        return new static($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    /**
     * Indicates whether this request originated from a trusted proxy.
     *
     * 指示此请求是否源于受信任的代理
     *
     * This can be useful to determine whether or not to trust the
     * contents of a proxy-specific header.
     *
     * 这可以用来确定是否信任特定代理头的内容
     *
     * @return bool true if the request came from a trusted proxy, false otherwise
     */
    public function isFromTrustedProxy()
    {
        return self::$trustedProxies && IpUtils::checkIp($this->server->get('REMOTE_ADDR'), self::$trustedProxies);
    }
    // 标准化和过滤客户端IP
    private function normalizeAndFilterClientIps(array $clientIps, $ip)
    {
        $clientIps[] = $ip; // Complete the IP chain with the IP the request actually came from 用实际上来自请求的IP完成IP链
        $firstTrustedIp = null;

        foreach ($clientIps as $key => $clientIp) {
            // Remove port (unfortunately, it does happen) 删除端口（不幸的是，它确实发生）
            if (preg_match('{((?:\d+\.){3}\d+)\:\d+}', $clientIp, $match)) {
                $clientIps[$key] = $clientIp = $match[1];
            }

            if (!filter_var($clientIp, FILTER_VALIDATE_IP)) {
                unset($clientIps[$key]);

                continue;
            }

            if (IpUtils::checkIp($clientIp, self::$trustedProxies)) {
                unset($clientIps[$key]);

                // Fallback to this when the client IP falls into the range of trusted proxies 当客户端IP落入受信任代理的范围时返回到此
                if (null === $firstTrustedIp) {
                    $firstTrustedIp = $clientIp;
                }
            }
        }

        // Now the IP chain contains only untrusted proxies and the client IP 现在，IP链只包含不受信任的代理和客户端IP
        return $clientIps ? array_reverse($clientIps) : array($firstTrustedIp);
    }
}
