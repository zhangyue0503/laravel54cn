<?php

namespace Illuminate\Routing;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;

//URL生成器
class UrlGenerator implements UrlGeneratorContract
{
    use Macroable;

    /**
     * The route collection.
     *
     * 路由集合
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routes;

    /**
     * The request instance.
     *
     * 请求实例
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The forced URL root.
     *
     * 强制的根URL
     *
     * @var string
     */
    protected $forcedRoot;

    /**
     * The forced schema for URLs.
     *
     * 强制的URL协议
     *
     * @var string
     */
    protected $forceScheme;

    /**
     * A cached copy of the URL root for the current request.
     *
     * 当前请求的URL根目录的缓存副本
     *
     * @var string|null
     */
    protected $cachedRoot;

    /**
     * A cached copy of the URL schema for the current request.
     *
     * 当前请求的URL架构的缓存副本
     *
     * @var string|null
     */
    protected $cachedSchema;

    /**
     * The root namespace being applied to controller actions.
     *
     * 将根命名空间应用于控制器操作
     *
     * @var string
     */
    protected $rootNamespace;

    /**
     * The session resolver callable.
     *
     * 会话回调解析器
     *
     * @var callable
     */
    protected $sessionResolver;

    /**
     * The callback to use to format hosts.
     *
     * 用于格式化主机的回调
     *
     * @var \Closure
     */
    protected $formatHostUsing;

    /**
     * The callback to use to format paths.
     *
     * 用于格式化路径的回调
     *
     * @var \Closure
     */
    protected $formatPathUsing;

    /**
     * The route URL generator instance.
     *
     * 路由URL生成器实例
     *
     * @var \Illuminate\Routing\RouteUrlGenerator
     */
    protected $routeGenerator;

    /**
     * Create a new URL Generator instance.
     *
     * 创建一个新的URL生成器实例
     *
     * @param  \Illuminate\Routing\RouteCollection  $routes
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(RouteCollection $routes, Request $request)
    {
        $this->routes = $routes;

        $this->setRequest($request); //设置当前请求实例
    }

    /**
     * Get the full URL for the current request.
     *
     * 获取当前请求的完整URL
     *
     * @return string
     */
    public function full()
    {
        return $this->request->fullUrl(); //从请求获取完整的URL
    }

    /**
     * Get the current URL for the request.
     *
     * 获取请求的当前URL
     *
     * @return string
     */
    public function current()
    {
        //       生成给定路径的绝对URL(返回与被执行脚本相对应的路径)
        return $this->to($this->request->getPathInfo());
    }

    /**
     * Get the URL for the previous request.
     *
     * 获取之前请求的URL
     *
     * @param  mixed  $fallback
     * @return string
     */
    public function previous($fallback = false)
    {
        $referrer = $this->request->headers->get('referer');// 根据名称获取标头中的值
        //          生成给定路径的绝对URL()  如果可能的话，从会话中获取前面的URL
        $url = $referrer ? $this->to($referrer) : $this->getPreviousUrlFromSession();

        if ($url) {
            return $url;
        } elseif ($fallback) {
            return $this->to($fallback); //生成给定路径的绝对URL()
        } else {
            return $this->to('/');//生成给定路径的绝对URL()
        }
    }

    /**
     * Get the previous URL from the session if possible.
     *
     * 如果可能的话，从会话中获取前面的URL
     *
     * @return string|null
     */
    protected function getPreviousUrlFromSession()
    {
        $session = $this->getSession(); //从解析器获得会话实现
        //                 从会话中获取以前的URL
        return $session ? $session->previousUrl() : null;
    }

    /**
     * Generate an absolute URL to the given path.
     *
     * 生成给定路径的绝对URL
     *
     * @param  string  $path
     * @param  mixed  $extra
     * @param  bool|null  $secure
     * @return string
     */
    public function to($path, $extra = [], $secure = null)
    {
        // First we will check if the URL is already a valid URL. If it is we will not
        // try to generate a new one but will simply return the URL as is, which is
        // convenient since developers do not always have to check if it's valid.
        //
        // 首先，我们将检查URL是否已经是有效URL
        // 如果是，我们不会尝试生成一个新的，但只会返回URL，这是方便的，因为开发人员并不总是要检查，如果它的有效性。
        //
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $tail = implode('/', array_map(
            //                           格式化URL参数的数组
            'rawurlencode', (array) $this->formatParameters($extra))
        );

        // Once we have the scheme we will compile the "tail" by collapsing the values
        // into a single string delimited by slashes. This just makes it convenient
        // for passing the array of parameters to this URL as a list of segments.
        //
        // 一旦我们的方案，我们将编制的“tail”被倒塌的值为一个字符串以斜线分隔
        // 这只是方便了将参数数组传递给这个URL作为段的列表
        //
        //              获取请求的基本URL   获取原始URL的默认方案
        $root = $this->formatRoot($this->formatScheme($secure));
        //                            从给定路径中提取查询字符串
        list($path, $query) = $this->extractQueryString($path);
        //将给定的URL段格式化为一个URL
        return $this->format(
            $root, '/'.trim($path.'/'.$tail, '/')
        ).$query;
    }

    /**
     * Generate a secure, absolute URL to the given path.
     *
     * 为给定路径生成安全的绝对URL
     *
     * @param  string  $path
     * @param  array   $parameters
     * @return string
     */
    public function secure($path, $parameters = [])
    {
        //生成给定路径的绝对URL
        return $this->to($path, $parameters, true);
    }

    /**
     * Generate the URL to an application asset.
     *
     * 生成应用程序asset的URL
     *
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    public function asset($path, $secure = null)
    {
        //确定给定路径是否是有效URL
        if ($this->isValidUrl($path)) {
            return $path;
        }

        // Once we get the root URL, we will check to see if it contains an index.php
        // file in the paths. If it does, we will remove it since it is not needed
        // for asset paths, but only for routes to endpoints in the application.
        //
        // 一旦我们得到的根URL，我们将检查它是否包含一个index.php文件的路径
        // 如果它这样做，我们将删除它，因为它是不需要的asset路径，但仅适用于应用程序中的端点的路由
        //
        //              获取请求的基本URL   获取原始URL的默认方案
        $root = $this->formatRoot($this->formatScheme($secure));
        //         从路径中删除index.php文件
        return $this->removeIndex($root).'/'.trim($path, '/');
    }

    /**
     * Generate the URL to a secure asset.
     *
     * 生成安全asset的URL
     *
     * @param  string  $path
     * @return string
     */
    public function secureAsset($path)
    {
        //生成应用程序asset的URL
        return $this->asset($path, true);
    }

    /**
     * Generate the URL to an asset from a custom root domain such as CDN, etc.
     *
     * 从诸如CDN等自定义根域生成asset的URL
     *
     * @param  string  $root
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    public function assetFrom($root, $path, $secure = null)
    {
        // Once we get the root URL, we will check to see if it contains an index.php
        // file in the paths. If it does, we will remove it since it is not needed
        // for asset paths, but only for routes to endpoints in the application.
        //
        // 一旦我们得到的根URL，我们将检查它是否包含一个index.php文件的路径
        // 如果它这样做，我们将删除它，因为它是不需要的asset路径，但仅适用于应用程序中的端点的路由
        //
        //              获取请求的基本URL   获取原始URL的默认方案
        $root = $this->formatRoot($this->formatScheme($secure), $root);
        //         从路径中删除index.php文件
        return $this->removeIndex($root).'/'.trim($path, '/');
    }

    /**
     * Remove the index.php file from a path.
     *
     * 从路径中删除index.php文件
     *
     * @param  string  $root
     * @return string
     */
    protected function removeIndex($root)
    {
        $i = 'index.php';
        // 确定一个给定的字符串包含另一个字符串
        return Str::contains($root, $i) ? str_replace('/'.$i, '', $root) : $root;
    }

    /**
     * Get the default scheme for a raw URL.
     *
     * 获取原始URL的默认协议
     *
     * @param  bool|null  $secure
     * @return string
     */
    public function formatScheme($secure)
    {
        if (! is_null($secure)) {
            return $secure ? 'https://' : 'http://';
        }

        if (is_null($this->cachedSchema)) {
            //                                              获取请求的协议
            $this->cachedSchema = $this->forceScheme ?: $this->request->getScheme().'://';
        }

        return $this->cachedSchema;
    }

    /**
     * Get the URL to a named route.
     *
     * 获取指定路由的URL
     *
     * @param  string  $name
     * @param  mixed   $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function route($name, $parameters = [], $absolute = true)
    {
        //                          按名称获取路由实例
        if (! is_null($route = $this->routes->getByName($name))) {
            return $this->toRoute($route, $parameters, $absolute); //获取给定路由实例的URL
        }

        throw new InvalidArgumentException("Route [{$name}] not defined.");
    }

    /**
     * Get the URL for a given route instance.
     *
     * 获取给定路由实例的URL
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $parameters
     * @param  bool   $absolute
     * @return string
     *
     * @throws \Illuminate\Routing\Exceptions\UrlGenerationException
     */
    protected function toRoute($route, $parameters, $absolute)
    {
        //    获取路由URL生成器实例->为给定路由生成URL(格式化URL参数的数组)
        return $this->routeUrl()->to(
            $route, $this->formatParameters($parameters), $absolute
        );
    }

    /**
     * Get the URL to a controller action.
     *
     * 获取控制器动作的URL
     *
     * @param  string  $action
     * @param  mixed   $parameters
     * @param  bool    $absolute
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function action($action, $parameters = [], $absolute = true)
    {
        //                             通过控制器动作获取路由实例           格式化给定控制器动作
        if (is_null($route = $this->routes->getByAction($action = $this->formatAction($action)))) {
            throw new InvalidArgumentException("Action {$action} not defined.");
        }
        //         获取给定路由实例的URL
        return $this->toRoute($route, $parameters, $absolute);
    }

    /**
     * Format the given controller action.
     *
     * 格式化给定控制器动作
     *
     * @param  string  $action
     * @return string
     */
    protected function formatAction($action)
    {
        if ($this->rootNamespace && ! (strpos($action, '\\') === 0)) {
            return $this->rootNamespace.'\\'.$action;
        } else {
            return trim($action, '\\');
        }
    }

    /**
     * Format the array of URL parameters.
     *
     * 格式化URL参数的数组
     *
     * @param  mixed|array  $parameters
     * @return array
     */
    public function formatParameters($parameters)
    {
        $parameters = array_wrap($parameters);

        foreach ($parameters as $key => $parameter) {
            if ($parameter instanceof UrlRoutable) {
                $parameters[$key] = $parameter->getRouteKey();    //获取模型路由键的值
            }
        }

        return $parameters;
    }

    /**
     * Extract the query string from the given path.
     *
     * 从给定路径中提取查询字符串
     *
     * @param  string  $path
     * @return array
     */
    protected function extractQueryString($path)
    {
        if (($queryPosition = strpos($path, '?')) !== false) {
            return [
                substr($path, 0, $queryPosition),
                substr($path, $queryPosition),
            ];
        }

        return [$path, ''];
    }

    /**
     * Get the base URL for the request.
     *
     * 获取请求的基本URL
     *
     * @param  string  $scheme
     * @param  string  $root
     * @return string
     */
    public function formatRoot($scheme, $root = null)
    {
        if (is_null($root)) {
            if (is_null($this->cachedRoot)) {
                //                                         获取应用程序的根URL
                $this->cachedRoot = $this->forcedRoot ?: $this->request->root();
            }

            $root = $this->cachedRoot;
        }
        //确定给定的子字符串是否属于给定的字符串
        $start = Str::startsWith($root, 'http://') ? 'http://' : 'https://';

        return preg_replace('~'.$start.'~', $scheme, $root, 1);
    }

    /**
     * Format the given URL segments into a single URL.
     *
     * 将给定的URL段格式化为一个URL
     *
     * @param  string  $root
     * @param  string  $path
     * @return string
     */
    public function format($root, $path)
    {
        $path = '/'.trim($path, '/');

        if ($this->formatHostUsing) {
            $root = call_user_func($this->formatHostUsing, $root);
        }

        if ($this->formatPathUsing) {
            $path = call_user_func($this->formatPathUsing, $path);
        }

        return trim($root.$path, '/');
    }

    /**
     * Determine if the given path is a valid URL.
     *
     * 确定给定路径是否是有效URL
     *
     * @param  string  $path
     * @return bool
     */
    public function isValidUrl($path)
    {
        //确定给定的子字符串是否属于给定的字符串
        if (! Str::startsWith($path, ['#', '//', 'mailto:', 'tel:', 'http://', 'https://'])) {
            return filter_var($path, FILTER_VALIDATE_URL) !== false;
        }

        return true;
    }

    /**
     * Get the Route URL generator instance.
     *
     * 获取路由URL生成器实例
     *
     * @return \Illuminate\Routing\RouteUrlGenerator
     */
    protected function routeUrl()
    {
        if (! $this->routeGenerator) {
            $this->routeGenerator = new RouteUrlGenerator($this, $this->request);
        }

        return $this->routeGenerator;
    }

    /**
     * Set the default named parameters used by the URL generator.
     *
     * URL生成器使用的默认命名参数
     *
     * @param  array  $defaults
     * @return void
     */
    public function defaults(array $defaults)
    {
        //获取路由URL生成器实例->设置URL生成器使用的默认命名参数
        $this->routeUrl()->defaults($defaults);
    }

    /**
     * Force the scheme for URLs.
     *
     * 强制的网址协议
     *
     * @param  string  $schema
     * @return void
     */
    public function forceScheme($schema)
    {
        $this->cachedSchema = null;

        $this->forceScheme = $schema.'://';
    }

    /**
     * Set the forced root URL.
     *
     * 设置强制根网址
     *
     * @param  string  $root
     * @return void
     */
    public function forceRootUrl($root)
    {
        $this->forcedRoot = rtrim($root, '/');

        $this->cachedRoot = null;
    }

    /**
     * Set a callback to be used to format the host of generated URLs.
     *
     * 设置一个回调用来格式化生成URL的主机
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function formatHostUsing(Closure $callback)
    {
        $this->formatHostUsing = $callback;

        return $this;
    }

    /**
     * Set a callback to be used to format the path of generated URLs.
     *
     * 设置一个回调用来格式化生成的URL的路径
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function formatPathUsing(Closure $callback)
    {
        $this->formatPathUsing = $callback;

        return $this;
    }

    /**
     * Get the path formatter being used by the URL generator.
     *
     * 得到路径的URL生成器使用格式化程序
     *
     * @return \Closure
     */
    public function pathFormatter()
    {
        return $this->formatPathUsing ?: function ($path) {
            return $path;
        };
    }

    /**
     * Get the request instance.
     *
     * 获取请求实例
     *
     * @return \Illuminate\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the current request instance.
     *
     * 设置当前请求实例
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        $this->cachedRoot = null;
        $this->cachedSchema = null;
        $this->routeGenerator = null;
    }

    /**
     * Set the route collection.
     *
     * 设置路由集合
     *
     * @param  \Illuminate\Routing\RouteCollection  $routes
     * @return $this
     */
    public function setRoutes(RouteCollection $routes)
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * Get the session implementation from the resolver.
     *
     * 从解析器获得会话实现
     *
     * @return \Illuminate\Session\Store|null
     */
    protected function getSession()
    {
        if ($this->sessionResolver) {
            return call_user_func($this->sessionResolver);
        }
    }

    /**
     * Set the session resolver for the generator.
     *
     * 为生成器设置会话解析器
     *
     * @param  callable  $sessionResolver
     * @return $this
     */
    public function setSessionResolver(callable $sessionResolver)
    {
        $this->sessionResolver = $sessionResolver;

        return $this;
    }

    /**
     * Set the root controller namespace.
     *
     * 设置根控制器命名空间
     *
     * @param  string  $rootNamespace
     * @return $this
     */
    public function setRootControllerNamespace($rootNamespace)
    {
        $this->rootNamespace = $rootNamespace;

        return $this;
    }
}
