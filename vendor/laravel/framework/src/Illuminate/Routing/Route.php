<?php

namespace Illuminate\Routing;

use Closure;
use LogicException;
use ReflectionFunction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Illuminate\Routing\Matching\UriValidator;
use Illuminate\Routing\Matching\HostValidator;
use Illuminate\Routing\Matching\MethodValidator;
use Illuminate\Routing\Matching\SchemeValidator;
use Illuminate\Http\Exceptions\HttpResponseException;

class Route
{
    use RouteDependencyResolverTrait;

    /**
     * The URI pattern the route responds to.
     *
     * 路由响应的URI模式
     *
     * @var string
     */
    public $uri;

    /**
     * The HTTP methods the route responds to.
     *
     * 路由响应的HTTP方法
     *
     * @var array
     */
    public $methods;

    /**
     * The route action array.
     *
     * 路由动作数组
     *
     * @var array
     */
    public $action;

    /**
     * The controller instance.
     *
     * 控制器实例
     *
     * @var mixed
     */
    public $controller;

    /**
     * The default values for the route.
     *
     * 路由的默认值
     *
     * @var array
     */
    public $defaults = [];

    /**
     * The regular expression requirements.
     *
     * 所需的正则表达式
     *
     * @var array
     */
    public $wheres = [];

    /**
     * The array of matched parameters.
     *
     * 匹配参数数组
     *
     * @var array
     */
    public $parameters;

    /**
     * The parameter names for the route.
     *
     * 路由的参数名称
     *
     * @var array|null
     */
    public $parameterNames;

    /**
     * The computed gathered middleware.
     *
     * 计算聚集中间件
     *
     * @var array|null
     */
    public $computedMiddleware;

    /**
     * The compiled version of the route.
     *
     * 路由的编译版本
     *
     * @var \Symfony\Component\Routing\CompiledRoute
     */
    public $compiled;

    /**
     * The router instance used by the route.
     *
     * 路由器使用的路由实例
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The container instance used by the route.
     *
     * 路由使用的容器实例
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The validators used by the routes.
     *
     * 路由使用的验证器
     *
     * @var array
     */
    public static $validators;

    /**
     * Create a new Route instance.
     *
     * 创建一个新的路由实例
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  \Closure|array  $action
     * @return void
     */
    public function __construct($methods, $uri, $action)
    {
        $this->uri = $uri;
        $this->methods = (array) $methods;
        $this->action = $this->parseAction($action); // 将路由动作解析为标准数组

        if (in_array('GET', $this->methods) && ! in_array('HEAD', $this->methods)) {
            $this->methods[] = 'HEAD';
        }
        //如果路由操作有前缀
        if (isset($this->action['prefix'])) {
            $this->prefix($this->action['prefix']);  // 向路由URI添加前缀
        }
    }

    /**
     * Parse the route action into a standard array.
     *
     * 将路由动作解析为标准数组
     *
     * @param  callable|array|null  $action
     * @return array
     *
     * @throws \UnexpectedValueException
     */
    protected function parseAction($action)
    {
        return RouteAction::parse($this->uri, $action); // 将给定操作解析为数组
    }

    /**
     * Run the route action and return the response.
     *
     * 运行路由操作并返回响应
     *
     * @return mixed
     */
    public function run()
    {
        $this->container = $this->container ?: new Container;  //获取容器

        try {
            if ($this->isControllerAction()) {  // 检查路由的动作是否为控制器
                return $this->runController();  // 运行路由操作并返回响应，控制器方式
            }

            return $this->runCallable(); // 运行路由操作并返回响应，闭包方式
        } catch (HttpResponseException $e) { // HttpResponseException错误捕获
            return $e->getResponse();
        }
    }

    /**
     * Checks whether the route's action is a controller.
     *
     * 检查路由的动作是否为控制器
     *
     * @return bool
     */
    protected function isControllerAction()
    {
        return is_string($this->action['uses']);
    }

    /**
     * Run the route action and return the response.
     *
     * 运行路由操作并返回响应，闭包方式
     *
     * @return mixed
     */
    protected function runCallable()
    {
        $callable = $this->action['uses'];

        return $callable(...array_values($this->resolveMethodDependencies(
            $this->parametersWithoutNulls(), new ReflectionFunction($this->action['uses'])
        )));
    }

    /**
     * Run the route action and return the response.
     *
     * 运行路由操作并返回响应，控制器方式
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function runController()
    {
        return (new ControllerDispatcher($this->container))->dispatch(
            $this, $this->getController(), $this->getControllerMethod()
        );
    }

    /**
     * Get the controller instance for the route.
     *
     * 获取路由使用的控制器实例
     *
     * @return mixed
     */
    public function getController()
    {
        $class = $this->parseControllerCallback()[0];

        if (! $this->controller) {
            $this->controller = $this->container->make($class);
        }

        return $this->controller;
    }

    /**
     * Get the controller method used for the route.
     *
     * 获取路由使用的控制器方法
     *
     * @return string
     */
    protected function getControllerMethod()
    {
        return $this->parseControllerCallback()[1];
    }

    /**
     * Parse the controller.
     *
     * 解析控制器
     *
     * @return array
     */
    protected function parseControllerCallback()
    {
        return Str::parseCallback($this->action['uses']); // 解析 类@方法 类型回调到类和方法
    }

    /**
     * Determine if the route matches given request.
     *
     * 确定路由是否匹配给定的请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $includingMethod
     * @return bool
     */
    public function matches(Request $request, $includingMethod = true)
    {
        $this->compileRoute(); // 编译路由为Symfony CompiledRoute实例

        foreach ($this->getValidators() as $validator) { // 获取该实例的路由验证
            if (! $includingMethod && $validator instanceof MethodValidator) {
                continue;
            }

            if (! $validator->matches($this, $request)) { //匹配请求
                return false;
            }
        }

        return true;
    }

    /**
     * Compile the route into a Symfony CompiledRoute instance.
     *
     * 编译路由为Symfony CompiledRoute实例
     *
     * @return void
     */
    protected function compileRoute()
    {
        if (! $this->compiled) {
            $this->compiled = (new RouteCompiler($this))->compile();  //编译路由
        }

        return $this->compiled; // 返回 路由的编译版本
    }

    /**
     * Bind the route to a given request for execution.
     *
     * 将路由绑定到给定的执行请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function bind(Request $request)
    {
        $this->compileRoute(); // 编译路由为Symfony CompiledRoute实例

        $this->parameters = (new RouteParameterBinder($this)) //创建一个新的路由参数绑定实例
                        ->parameters($request); //获取路由参数
        // 返回自身
        return $this;
    }

    /**
     * Determine if the route has parameters.
     *
     * 确定路由是否有参数
     *
     * @return bool
     */
    public function hasParameters()
    {
        return isset($this->parameters);
    }

    /**
     * Determine a given parameter exists from the route.
     *
     * 确定路由是否存在的给定参数
     *
     * @param  string $name
     * @return bool
     */
    public function hasParameter($name)
    {
        if ($this->hasParameters()) {
            return array_key_exists($name, $this->parameters());
        }

        return false;
    }

    /**
     * Get a given parameter from the route.
     *
     * 从路由中得到给定的参数
     *
     * @param  string  $name
     * @param  mixed   $default
     * @return string|object
     */
    public function parameter($name, $default = null)
    {
        return Arr::get($this->parameters(), $name, $default);
    }

    /**
     * Set a parameter to the given value.
     *
     * 为给定值设置参数
     *
     * @param  string  $name
     * @param  mixed   $value
     * @return void
     */
    public function setParameter($name, $value)
    {
        $this->parameters();

        $this->parameters[$name] = $value;
    }

    /**
     * Unset a parameter on the route if it is set.
     *
     * 取消设置路径上的一个参数，如果该参数已设置
     *
     * @param  string  $name
     * @return void
     */
    public function forgetParameter($name)
    {
        $this->parameters();

        unset($this->parameters[$name]);
    }

    /**
     * Get the key / value list of parameters for the route.
     *
     * 获取路由参数的键/值列表
     *
     * @return array
     *
     * @throws \LogicException
     */
    public function parameters()
    {
        if (isset($this->parameters)) {
            return $this->parameters;
        }

        throw new LogicException('Route is not bound.');
    }

    /**
     * Get the key / value list of parameters without null values.
     *
     * 获取无空值参数的键/值列表。
     *
     * @return array
     */
    public function parametersWithoutNulls()
    {
        return array_filter($this->parameters(), function ($p) {
            return ! is_null($p);
        });
    }

    /**
     * Get all of the parameter names for the route.
     *
     * 获取路由的所有参数名称
     *
     * @return array
     */
    public function parameterNames()
    {
        if (isset($this->parameterNames)) {
            return $this->parameterNames;
        }
        //                              获取路由的参数名称
        return $this->parameterNames = $this->compileParameterNames();
    }

    /**
     * Get the parameter names for the route.
     *
     * 获取路由的参数名称
     *
     * @return array
     */
    protected function compileParameterNames()
    {
        preg_match_all('/\{(.*?)\}/', $this->domain().$this->uri, $matches);

        return array_map(function ($m) {
            return trim($m, '?');
        }, $matches[1]);
    }

    /**
     * Get the parameters that are listed in the route / controller signature.
     *
     * 获取路由/控制器签名中列出的参数
     *
     * @param  string|null  $subClass
     * @return array
     */
    public function signatureParameters($subClass = null)
    {
        return RouteSignatureParameters::fromAction($this->action, $subClass); // 返回 提取路由动作的签名参数
    }

    /**
     * Set a default value for the route.
     *
     * 设置路由的默认值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function defaults($key, $value)
    {
        $this->defaults[$key] = $value;

        return $this;
    }

    /**
     * Set a regular expression requirement on the route.
     *
     * 设置路由上要求的正则表达式
     *
     * @param  array|string  $name
     * @param  string  $expression
     * @return $this
     */
    public function where($name, $expression = null)
    {
        foreach ($this->parseWhere($name, $expression) as $name => $expression) {
            $this->wheres[$name] = $expression;
        }

        return $this;
    }

    /**
     * Parse arguments to the where method into an array.
     *
     * 将参数解析到数组中的方法
     *
     * @param  array|string  $name
     * @param  string  $expression
     * @return array
     */
    protected function parseWhere($name, $expression)
    {
        return is_array($name) ? $name : [$name => $expression];
    }

    /**
     * Set a list of regular expression requirements on the route.
     *
     * 设置路由所要求的正则表达式
     *
     * @param  array  $wheres
     * @return $this
     */
    protected function whereArray(array $wheres)
    {
        foreach ($wheres as $name => $expression) {
            $this->where($name, $expression);
        }

        return $this;
    }

    /**
     * Get the HTTP verbs the route responds to.
     *
     * 获取路由响应的HTTP请求method
     *
     * @return array
     */
    public function methods()
    {
        return $this->methods;
    }

    /**
     * Determine if the route only responds to HTTP requests.
     *
     * 确定路由是否只响应HTTP请求
     *
     * @return bool
     */
    public function httpOnly()
    {
        return in_array('http', $this->action, true);
    }

    /**
     * Determine if the route only responds to HTTPS requests.
     *
     * 确定路由是否只响应HTTPS请求
     *
     * @return bool
     */
    public function httpsOnly()
    {
        return $this->secure();
    }

    /**
     * Determine if the route only responds to HTTPS requests.
     *
     * 确定路由是否只响应HTTPS请求
     *
     * @return bool
     */
    public function secure()
    {
        return in_array('https', $this->action, true);
    }

    /**
     * Get the domain defined for the route.
     *
     * 获取路由定义的域
     *
     * @return string|null
     */
    public function domain()
    {
        // 如果$action['domain']存在，返回过滤掉协议头的domain，否则返回null
        return isset($this->action['domain'])
                ? str_replace(['http://', 'https://'], '', $this->action['domain']) : null;
    }

    /**
     * Get the prefix of the route instance.
     *
     * 获取路由实例的前缀
     *
     * @return string
     */
    public function getPrefix()
    {
        return isset($this->action['prefix']) ? $this->action['prefix'] : null;
    }

    /**
     * Add a prefix to the route URI.
     *
     * 向路由URI添加前缀
     *
     * @param  string  $prefix
     * @return $this
     */
    public function prefix($prefix)
    {
        $uri = rtrim($prefix, '/').'/'.ltrim($this->uri, '/');

        $this->uri = trim($uri, '/');

        return $this;
    }

    /**
     * Get the URI associated with the route.
     *
     * 获取与路由关联的URI
     *
     * @return string
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * Set the URI that the route responds to.
     *
     * 设置路由响应的URI
     *
     * @param  string  $uri
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Get the name of the route instance.
     *
     * 获取路由实例的名称
     *
     * @return string
     */
    public function getName()
    {
        return isset($this->action['as']) ? $this->action['as'] : null;
    }

    /**
     * Add or change the route name.
     *
     * 添加或修改路由名称
     *
     * @param  string  $name
     * @return $this
     */
    public function name($name)
    {
        $this->action['as'] = isset($this->action['as']) ? $this->action['as'].$name : $name;

        return $this;
    }

    /**
     * Set the handler for the route.
     *
     * 设置路由处理程序
     *
     * @param  \Closure|string  $action
     * @return $this
     */
    public function uses($action)
    {
        //          如果$action是字符串   解析“uses”方法的基于字符串的操作
        $action = is_string($action) ? $this->addGroupNamespaceToStringUses($action) : $action;
        // 返回 设置路由的动作数组 合并数组 $action 将路由动作解析为标准数组的数组
        return $this->setAction(array_merge($this->action, $this->parseAction([
            'uses' => $action,
            'controller' => $action,
        ])));
    }

    /**
     * Parse a string based action for the "uses" fluent method.
     *
     * 解析“uses”方法的基于字符串的操作
     *
     * @param  string  $action
     * @return string
     */
    protected function addGroupNamespaceToStringUses($action)
    {
        $groupStack = last($this->router->getGroupStack()); // 获取路由器的当前组堆栈

        if (isset($groupStack['namespace']) && strpos($action, '\\') !== 0) {
            return $groupStack['namespace'].'\\'.$action;
        }

        return $action;
    }

    /**
     * Get the action name for the route.
     *
     * 获取路由的action名称
     *
     * @return string
     */
    public function getActionName()
    {
        //      如果存在控制器action                  返回控制器action                 闭包
        return isset($this->action['controller']) ? $this->action['controller'] : 'Closure';
    }

    /**
     * Get the method name of the route action.
     *
     * 获取路由action的方法名称
     *
     * @return string
     */
    public function getActionMethod()
    {
        return array_last(explode('@', $this->getActionName()));
    }

    /**
     * Get the action array for the route.
     *
     * 获取路由的动作数组
     *
     * @return array
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set the action array for the route.
     *
     * 设置路由的动作数组
     *
     * @param  array  $action
     * @return $this
     */
    public function setAction(array $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get all middleware, including the ones from the controller.
     *
     * 获取所有的中间件，包括从控制器中定义的
     *
     * @return array
     */
    public function gatherMiddleware()
    {
        if (! is_null($this->computedMiddleware)) {     //计算聚集中间件不为空
            return $this->computedMiddleware;
        }

        $this->computedMiddleware = [];
        //返回 合并且移除重复值的数组，合并路由中间件和控制器中间件
        return $this->computedMiddleware = array_unique(array_merge(
            $this->middleware(), $this->controllerMiddleware()
        ), SORT_REGULAR);
    }

    /**
     * Get or set the middlewares attached to the route.
     *
     * 获取或设置附加到路由的中间件
     *
     * @param  array|string|null $middleware
     * @return $this|array
     */
    public function middleware($middleware = null)
    {
        if (is_null($middleware)) {
            return (array) Arr::get($this->action, 'middleware', []);
        }

        if (is_string($middleware)) {
            $middleware = func_get_args();
        }

        $this->action['middleware'] = array_merge(
            (array) Arr::get($this->action, 'middleware', []), $middleware
        );

        return $this;
    }

    /**
     * Get the middleware for the route's controller.
     *
     * 获取路由控制器的中间件
     *
     * @return array
     */
    public function controllerMiddleware()
    {
        if (! $this->isControllerAction()) { // 检查路由的动作是否为控制器
            return [];
        }

        return ControllerDispatcher::getMiddleware(   // 从控制器实例获取中间件
            $this->getController(), $this->getControllerMethod()     //路由使用的控制器实例,路由使用的控制器方法
        );
    }

    /**
     * Get the route validators for the instance.
     *
     * 获取该实例的路由验证
     *
     * @return array
     */
    public static function getValidators()
    {
        if (isset(static::$validators)) {
            return static::$validators;
        }

        // To match the route, we will use a chain of responsibility pattern with the
        // validator implementations. We will spin through each one making sure it
        // passes and then we will know if the route as a whole matches request.
        //
        // 路由匹配，我们将使用责任链模式实现验证。我们将通过每一个验证确保它们都通过，然后我们会知道整个路路由是否匹配请求。
        //
        return static::$validators = [
            new UriValidator, new MethodValidator,
            new SchemeValidator, new HostValidator,
        ];
    }

    /**
     * Get the compiled version of the route.
     *
     * 获取编译后的路由版本
     *
     * @return \Symfony\Component\Routing\CompiledRoute
     */
    public function getCompiled()
    {
        return $this->compiled;
    }

    /**
     * Set the router instance on the route.
     *
     * 在路由上设置路由器实例
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return $this
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Set the container instance on the route.
     *
     * 在路由上设置容器实例
     *
     * @param  \Illuminate\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Prepare the route instance for serialization.
     *
     * 为序列化准备路由实例
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function prepareForSerialization()
    {
        //如果$action['uses']是闭包
        if ($this->action['uses'] instanceof Closure) {
            throw new LogicException("Unable to prepare route [{$this->uri}] for serialization. Uses Closure.");
        }

        $this->compileRoute();  // 编译路由为Symfony CompiledRoute实例

        unset($this->router, $this->container);
    }

    /**
     * Dynamically access route parameters.
     *
     * 动态访问路由参数
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->parameter($key);   // 从路由中得到给定的参数
    }
}
