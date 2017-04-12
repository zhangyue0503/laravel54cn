<?php

namespace Illuminate\Routing;

use Illuminate\Support\Str;
//资源型的路由器注册相关
class ResourceRegistrar
{
    /**
     * The router instance.
     *
     * 路由器实例
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The default actions for a resourceful controller.
     *
     * 资源管理器的默认操作
     *
     * @var array
     */
    protected $resourceDefaults = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

    /**
     * The parameters set for this resource instance.
     *
     * 为该资源实例设置的参数
     *
     * @var array|string
     */
    protected $parameters;

    /**
     * The global parameter mapping.
     *
     * 全局参数映射
     *
     * @var array
     */
    protected static $parameterMap = [];

    /**
     * Singular global parameters.
     *
     * 单数形式的全局参数
     *
     * @var bool
     */
    protected static $singularParameters = true;

    /**
     * The verbs used in the resource URIs.
     *
     * 使用的资源的URI的动词
     *
     * @var array
     */
    protected static $verbs = [
        'create' => 'create',
        'edit' => 'edit',
    ];

    /**
     * Create a new resource registrar instance.
     *
     * 创建一个新的资源注册实例
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Route a resource to a controller.
     *
     * 将资源路由到控制器
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array   $options
     * @return void
     */
    public function register($name, $controller, array $options = [])
    {
        if (isset($options['parameters']) && ! isset($this->parameters)) {
            $this->parameters = $options['parameters'];
        }

        // If the resource name contains a slash, we will assume the developer wishes to
        // register these resource routes with a prefix so we will set that up out of
        // the box so they don't have to mess with it. Otherwise, we will continue.
        //
        // 如果资源名称包含斜杠，我们将假定开发人员希望用前缀注册这些资源路由，这样我们就可以将其设置在框外，这样他们就不必弄乱它了
        // 否则，我们将继续
        //
        if (Str::contains($name, '/')) {
            $this->prefixedResource($name, $controller, $options); //建立一套资源路由前缀

            return;
        }

        // We need to extract the base resource from the resource name. Nested resources
        // are supported in the framework, but we need to know what name to use for a
        // place-holder on the route parameters, which should be the base resources.
        //
        // 我们需要从资源名称中提取基本资源
        // 在框架中支持嵌套资源，但我们需要知道在路由参数上使用什么位置的名称，它应该是基础资源
        //
        $base = $this->getResourceWildcard(last(explode('.', $name))); //得到一个英语单词的单数形式

        $defaults = $this->resourceDefaults;
        //                   获取适用的资源方法
        foreach ($this->getResourceMethods($defaults, $options) as $m) {
            $this->{'addResource'.ucfirst($m)}($name, $base, $controller, $options);
        }
    }

    /**
     * Build a set of prefixed resource routes.
     *
     * 建立一套资源路由前缀
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array   $options
     * @return void
     */
    protected function prefixedResource($name, $controller, array $options)
    {
        list($name, $prefix) = $this->getResourcePrefix($name);  //从资源名称中提取资源和前缀

        // We need to extract the base resource from the resource name. Nested resources
        // are supported in the framework, but we need to know what name to use for a
        // place-holder on the route parameters, which should be the base resources.
        //
        // 我们需要从资源名称中提取基本资源
        // 在框架中支持嵌套资源，但我们需要知道在路由参数上使用什么位置的名称，它应该是基础资源
        //
        $callback = function ($me) use ($name, $controller, $options) {
            $me->resource($name, $controller, $options);
        };
        // 创建具有共享属性的路由组
        return $this->router->group(compact('prefix'), $callback);
    }

    /**
     * Extract the resource and prefix from a resource name.
     *
     * 从资源名称中提取资源和前缀
     *
     * @param  string  $name
     * @return array
     */
    protected function getResourcePrefix($name)
    {
        $segments = explode('/', $name);

        // To get the prefix, we will take all of the name segments and implode them on
        // a slash. This will generate a proper URI prefix for us. Then we take this
        // last segment, which will be considered the final resources name we use.
        //
        // 得到前缀，我们将把所有的名和内爆在削减
        // 这将为我们生成一个适当的URI前缀。然后，我们将这最后一段，我们使用这将被视为最终资源名称
        //
        $prefix = implode('/', array_slice($segments, 0, -1));

        return [end($segments), $prefix];
    }

    /**
     * Get the applicable resource methods.
     *
     * 获取适用的资源方法
     *
     * @param  array  $defaults
     * @param  array  $options
     * @return array
     */
    protected function getResourceMethods($defaults, $options)
    {
        if (isset($options['only'])) {
            return array_intersect($defaults, (array) $options['only']);
        } elseif (isset($options['except'])) {
            return array_diff($defaults, (array) $options['except']);
        }

        return $defaults;
    }

    /**
     * Add the index method for a resourceful route.
     *
     * 为资源化路由添加Index方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceIndex($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name); //获取给定资源的基本资源URI

        $action = $this->getResourceAction($name, $controller, 'index', $options); //获取资源路由的操作数组

        return $this->router->get($uri, $action); //用路由器注册新的GET路由
    }

    /**
     * Add the create method for a resourceful route.
     *
     * 为资源化路由添加create方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceCreate($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/'.static::$verbs['create']; //获取给定资源的基本资源URI

        $action = $this->getResourceAction($name, $controller, 'create', $options); //获取资源路由的操作数组

        return $this->router->get($uri, $action); //用路由器注册新的GET路由
    }

    /**
     * Add the store method for a resourceful route.
     *
     * 为资源化路由添加store方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceStore($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name);//获取给定资源的基本资源URI

        $action = $this->getResourceAction($name, $controller, 'store', $options);//获取资源路由的操作数组

        return $this->router->post($uri, $action);//用路由器注册新的POST路由
    }

    /**
     * Add the show method for a resourceful route.
     *
     * 为资源化路由添加show方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceShow($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}';//获取给定资源的基本资源URI

        $action = $this->getResourceAction($name, $controller, 'show', $options);//获取资源路由的操作数组

        return $this->router->get($uri, $action);//用路由器注册新的GET路由
    }

    /**
     * Add the edit method for a resourceful route.
     *
     * 为资源化路由添加edit方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceEdit($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}/'.static::$verbs['edit'];//获取给定资源的基本资源URI

        $action = $this->getResourceAction($name, $controller, 'edit', $options);//获取资源路由的操作数组

        return $this->router->get($uri, $action);//用路由器注册新的GET路由
    }

    /**
     * Add the update method for a resourceful route.
     *
     * 为资源化路由添加update方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceUpdate($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}';//获取给定资源的基本资源URI

        $action = $this->getResourceAction($name, $controller, 'update', $options);//获取资源路由的操作数组

        return $this->router->match(['PUT', 'PATCH'], $uri, $action);//用路由器注册新的PUT或PATCH路由
    }

    /**
     * Add the destroy method for a resourceful route.
     *
     * 为资源化路由添加destroy方法
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceDestroy($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}';//获取给定资源的基本资源URI

        $action = $this->getResourceAction($name, $controller, 'destroy', $options);//获取资源路由的操作数组

        return $this->router->delete($uri, $action);//用路由器注册新的DELETE路由
    }

    /**
     * Get the base resource URI for a given resource.
     *
     * 获取给定资源的基本资源URI
     *
     * @param  string  $resource
     * @return string
     */
    public function getResourceUri($resource)
    {
        if (! Str::contains($resource, '.')) {
            return $resource;
        }

        // Once we have built the base URI, we'll remove the parameter holder for this
        // base resource name so that the individual route adders can suffix these
        // paths however they need to, as some do not have any parameters at all.
        //
        // 一旦我们建立了基础URI，我们将删除这个基础资源名称的参数持有人，以便个别路由加法器可以后缀这些路径，但他们需要，因为一些没有任何参数在所有。
        //
        $segments = explode('.', $resource);

        $uri = $this->getNestedResourceUri($segments); // 获取嵌套的资源段数组的URI
        //                            格式化资源参数以供使用
        return str_replace('/{'.$this->getResourceWildcard(end($segments)).'}', '', $uri);
    }

    /**
     * Get the URI for a nested resource segment array.
     *
     * 获取嵌套的资源段数组的URI
     *
     * @param  array   $segments
     * @return string
     */
    protected function getNestedResourceUri(array $segments)
    {
        // We will spin through the segments and create a place-holder for each of the
        // resource segments, as well as the resource itself. Then we should get an
        // entire string for the resource URI that contains all nested resources.
        //
        // 我们将通过段，创建一个地方持有人的每个资源段，以及资源本身
        // 然后我们应该得到包含所有嵌套资源的资源URI的整个字符串
        //
        return implode('/', array_map(function ($s) {
            return $s.'/{'.$this->getResourceWildcard($s).'}';   //格式化资源参数以供使用
        }, $segments));
    }

    /**
     * Format a resource parameter for usage.
     *
     * 格式化资源参数以供使用
     *
     * @param  string  $value
     * @return string
     */
    public function getResourceWildcard($value)
    {
        if (isset($this->parameters[$value])) {
            $value = $this->parameters[$value];
        } elseif (isset(static::$parameterMap[$value])) {
            $value = static::$parameterMap[$value];
        } elseif ($this->parameters === 'singular' || static::$singularParameters) {
            $value = Str::singular($value); //得到一个英语单词的单数形式
        }

        return str_replace('-', '_', $value);
    }

    /**
     * Get the action array for a resource route.
     *
     * 获取资源路由的操作数组
     *
     * @param  string  $resource
     * @param  string  $controller
     * @param  string  $method
     * @param  array   $options
     * @return array
     */
    protected function getResourceAction($resource, $controller, $method, $options)
    {
        $name = $this->getResourceRouteName($resource, $method, $options); //获取给定资源的名称

        $action = ['as' => $name, 'uses' => $controller.'@'.$method];

        if (isset($options['middleware'])) {
            $action['middleware'] = $options['middleware'];
        }

        return $action;
    }

    /**
     * Get the name for a given resource.
     *
     * 获取给定资源的名称
     *
     * @param  string  $resource
     * @param  string  $method
     * @param  array   $options
     * @return string
     */
    protected function getResourceRouteName($resource, $method, $options)
    {
        $name = $resource;

        // If the names array has been provided to us we will check for an entry in the
        // array first. We will also check for the specific method within this array
        // so the names may be specified on a more "granular" level using methods.
        //
        // 如果已提供给我们的名称数组，我们将首先检查数组中的条目
        // 我们还将检查此数组中的特定方法，以便在使用方法的“granular”级别上指定名称
        //
        if (isset($options['names'])) {
            if (is_string($options['names'])) {
                $name = $options['names'];
            } elseif (isset($options['names'][$method])) {
                return $options['names'][$method];
            }
        }

        // If a global prefix has been assigned to all names for this resource, we will
        // grab that so we can prepend it onto the name when we create this name for
        // the resource action. Otherwise we'll just use an empty string for here.
        //
        // 如果一个全局前缀已分配给该资源的所有名字，我们会抢可以在它身上的名字，当我们创建这个名称的资源行动
        // 否则我们只使用一个空字符串
        //
        $prefix = isset($options['as']) ? $options['as'].'.' : '';

        return trim(sprintf('%s%s.%s', $prefix, $name, $method), '.');
    }

    /**
     * Set or unset the unmapped global parameters to singular.
     *
     * 设置或取消映射单数的全局参数
     *
     * @param  bool  $singular
     * @return void
     */
    public static function singularParameters($singular = true)
    {
        static::$singularParameters = (bool) $singular;
    }

    /**
     * Get the global parameter map.
     *
     * 获取全局的参数map
     *
     * @return array
     */
    public static function getParameters()
    {
        return static::$parameterMap;
    }

    /**
     * Set the global parameter mapping.
     *
     * 设置全局参数映射
     *
     * @param  array $parameters
     * @return void
     */
    public static function setParameters(array $parameters = [])
    {
        static::$parameterMap = $parameters;
    }

    /**
     * Get or set the action verbs used in the resource URIs.
     *
     * 获取或设置用于资源的URI动作动词
     *
     * @param  array  $verbs
     * @return array
     */
    public static function verbs(array $verbs = [])
    {
        if (empty($verbs)) {
            return static::$verbs;
        } else {
            static::$verbs = array_merge(static::$verbs, $verbs);
        }
    }
}
