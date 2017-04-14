<?php

namespace Illuminate\Routing;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Routing\Exceptions\UrlGenerationException;
//路由URL生成器
class RouteUrlGenerator
{
    /**
     * The URL generator instance.
     *
     * URL生成器实例
     *
     * @param  \Illuminate\Routing\UrlGenerator
     */
    protected $url;

    /**
     * The request instance.
     *
     * 请求实例
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The named parameter defaults.
     *
     * 命名参数默认值
     *
     * @var array
     */
    public $defaultParameters = [];

    /**
     * Characters that should not be URL encoded.
     *
     * 不应该是URL编码的字符
     *
     * @var array
     */
    public $dontEncode = [
        '%2F' => '/',
        '%40' => '@',
        '%3A' => ':',
        '%3B' => ';',
        '%2C' => ',',
        '%3D' => '=',
        '%2B' => '+',
        '%21' => '!',
        '%2A' => '*',
        '%7C' => '|',
        '%3F' => '?',
        '%26' => '&',
        '%23' => '#',
        '%25' => '%',
    ];

    /**
     * Create a new Route URL generator.
     *
     * 创建一个新的路由URL生成器
     *
     * @param  \Illuminate\Routing\UrlGenerator  $url
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct($url, $request)
    {
        $this->url = $url;
        $this->request = $request;
    }

    /**
     * Generate a URL for the given route.
     *
     * 为给定路由生成URL
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws \Illuminate\Routing\Exceptions\UrlGenerationException
     */
    public function to($route, $parameters = [], $absolute = false)
    {
        $domain = $this->getRouteDomain($route, $parameters); //获取给定路径的格式化域

        // First we will construct the entire URI including the root and query string. Once it
        // has been constructed, we'll make sure we don't have any missing parameters or we
        // will need to throw the exception to let the developers know one was not given.
        //
        // 首先，我们将构建整个URI，包括根和查询字符串
        // 一旦它被构造，我们将确保我们没有任何缺少的参数，否则我们将需要抛出异常，让开发人员知道一个没有给出。
        //
        //         向URI添加查询字符串(将给定的URL段格式化为一个URL(替换根路径上的参数,替换路由路径的所有通配符参数(获取与路由关联的URI,)))
        $uri = $this->addQueryString($this->url->format(
            $root = $this->replaceRootParameters($route, $domain, $parameters),
            $this->replaceRouteParameters($route->uri(), $parameters)
        ), $parameters);

        if (preg_match('/\{.*?\}/', $uri)) {
            throw UrlGenerationException::forMissingParameters($route); // 创建缺少路由参数的新异常
        }

        // Once we have ensured that there are no missing parameters in the URI we will encode
        // the URI and prepare it for returning to the developer. If the URI is supposed to
        // be absolute, we will return it as-is. Otherwise we will remove the URL's root.
        //
        // 一旦我们确保URI中没有缺少参数，我们将对URI进行编码，并准备返回给开发者
        // 如果URI是绝对的，我们将返回它
        // 否则我们将删除URL的根
        //
        $uri = strtr(rawurlencode($uri), $this->dontEncode);

        if (! $absolute) {
            return '/'.ltrim(str_replace($root, '', $uri), '/');
        }

        return $uri;
    }

    /**
     * Get the formatted domain for a given route.
     *
     * 获取给定路径的格式化域
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $parameters
     * @return string
     */
    protected function getRouteDomain($route, &$parameters)
    {
        //        获取路由定义的域          为路由和请求格式化域和端口
        return $route->domain() ? $this->formatDomain($route, $parameters) : null;
    }

    /**
     * Format the domain and port for the route and request.
     *
     * 为路由和请求格式化域和端口
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  array  $parameters
     * @return string
     */
    protected function formatDomain($route, &$parameters)
    {
        return $this->addPortToDomain(  //如有必要，将端口添加到域中
            // 得到给定路径的协议              获取路由定义的域
            $this->getRouteScheme($route).$route->domain()
        );
    }

    /**
     * Get the scheme for the given route.
     *
     * 得到给定路径的协议
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     */
    protected function getRouteScheme($route)
    {
        if ($route->httpOnly()) { //确定路由是否只响应HTTP请求
            return 'http://';
        } elseif ($route->httpsOnly()) { // 确定路由是否只响应HTTPS请求
            return 'https://';
        } else {
            return $this->url->formatScheme(null); //获取原始URL的默认协议
        }
    }

    /**
     * Add the port to the domain if necessary.
     *
     * 如有必要，将端口添加到域中
     *
     * @param  string  $domain
     * @return string
     */
    protected function addPortToDomain($domain)
    {
        $secure = $this->request->isSecure(); // 请求是否是安全的

        $port = (int) $this->request->getPort(); // 返回请求的端口

        return ($secure && $port === 443) || (! $secure && $port === 80)
                    ? $domain : $domain.':'.$port;
    }

    /**
     * Replace the parameters on the root path.
     *
     * 替换根路径上的参数
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $domain
     * @param  array  $parameters
     * @return string
     */
    protected function replaceRootParameters($route, $domain, &$parameters)
    {
        $scheme = $this->getRouteScheme($route); // 得到给定路径的协议

        return $this->replaceRouteParameters( // 替换路由路径的所有通配符参数
            //         获取请求的基本URL
            $this->url->formatRoot($scheme, $domain), $parameters
        );
    }

    /**
     * Replace all of the wildcard parameters for a route path.
     *
     * 替换路由路径的所有通配符参数
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return string
     */
    protected function replaceRouteParameters($path, array &$parameters)
    {
        $path = $this->replaceNamedParameters($path, $parameters); //替换路径中的所有命名参数

        $path = preg_replace_callback('/\{.*?\}/', function ($match) use (&$parameters) {
            return (empty($parameters) && ! Str::endsWith($match[0], '?}'))
                        ? $match[0]
                        : array_shift($parameters);
        }, $path);

        return trim(preg_replace('/\{.*?\?\}/', '', $path), '/');
    }

    /**
     * Replace all of the named parameters in the path.
     *
     * 替换路径中的所有命名参数
     *
     * @param  string  $path
     * @param  array  $parameters
     * @return string
     */
    protected function replaceNamedParameters($path, &$parameters)
    {
        return preg_replace_callback('/\{(.*?)\??\}/', function ($m) use (&$parameters) {
            if (isset($parameters[$m[1]])) {
                return Arr::pull($parameters, $m[1]);
            } elseif (isset($this->defaultParameters[$m[1]])) {
                return $this->defaultParameters[$m[1]];
            } else {
                return $m[0];
            }
        }, $path);
    }

    /**
     * Add a query string to the URI.
     *
     * 向URI添加查询字符串
     *
     * @param  string  $uri
     * @param  array  $parameters
     * @return mixed|string
     */
    protected function addQueryString($uri, array $parameters)
    {
        // If the URI has a fragment we will move it to the end of this URI since it will
        // need to come after any query string that may be added to the URL else it is
        // not going to be available. We will remove it then append it back on here.
        //
        // 如果URI有一个片段，我们将把它移到这个URI的结尾，因为它需要在任何可能被添加到其他URL的查询字符串之后，它将不可用
        // 我们将删除它，然后追加回这里
        //
        if (! is_null($fragment = parse_url($uri, PHP_URL_FRAGMENT))) {
            $uri = preg_replace('/#.*/', '', $uri);
        }

        $uri .= $this->getRouteQueryString($parameters); // 获取给定路径的查询字符串

        return is_null($fragment) ? $uri : $uri."#{$fragment}";
    }

    /**
     * Get the query string for a given route.
     *
     * 获取给定路径的查询字符串
     *
     * @param  array  $parameters
     * @return string
     */
    protected function getRouteQueryString(array $parameters)
    {
        // First we will get all of the string parameters that are remaining after we
        // have replaced the route wildcards. We'll then build a query string from
        // these string parameters then use it as a starting point for the rest.
        //
        // 首先我们将获得所有其余的通配符的字符串参数之后我们更换了路由
        // 然后，我们将从这些字符串参数中生成一个查询字符串，然后使用它作为其余的一个起点。
        //
        if (count($parameters) == 0) {
            return '';
        }

        $query = http_build_query(
            $keyed = $this->getStringParameters($parameters) //从给定列表中获取字符串参数
        );

        // Lastly, if there are still parameters remaining, we will fetch the numeric
        // parameters that are in the array and add them to the query string or we
        // will make the initial query string if it wasn't started with strings.
        //
        // 最后，如果仍有参数剩余，我们将提取数组中的数值参数并将它们添加到查询字符串中，否则，如果字符串未开始，我们将生成初始查询字符串。
        //
        if (count($keyed) < count($parameters)) {
            $query .= '&'.implode(
                '&', $this->getNumericParameters($parameters) //从给定列表中获取数值参数
            );
        }

        return '?'.trim($query, '&');
    }

    /**
     * Get the string parameters from a given list.
     *
     * 从给定列表中获取字符串参数
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getStringParameters(array $parameters)
    {
        return array_filter($parameters, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get the numeric parameters from a given list.
     *
     * 从给定列表中获取数值参数
     *
     * @param  array  $parameters
     * @return array
     */
    protected function getNumericParameters(array $parameters)
    {
        return array_filter($parameters, 'is_numeric', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Set the default named parameters used by the URL generator.
     *
     * 设置URL生成器使用的默认命名参数
     *
     * @param  array  $defaults
     * @return void
     */
    public function defaults(array $defaults)
    {
        $this->defaultParameters = array_merge(
            $this->defaultParameters, $defaults
        );
    }
}
