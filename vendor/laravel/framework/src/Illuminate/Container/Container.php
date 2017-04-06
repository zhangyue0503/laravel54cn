<?php

namespace Illuminate\Container;

use Closure;
use ArrayAccess;
use LogicException;
use ReflectionClass;
use ReflectionParameter;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as ContainerContract;

//ArrayAccess类可数组式访问，相关方法 offsetExists、offsetGet、offsetSet、offsetUnset
class Container implements ArrayAccess, ContainerContract
{
    /**
     * The current globally available container (if any).
     *
     * 当前全局可用的容器（如果有的话）
     *
     * @var static
     */
    protected static $instance;

    /**
     * An array of the types that have been resolved.
     *
     * 已解析的类型数组
     *
     * @var array
     */
    protected $resolved = [];

    /**
     * The container's bindings.
     *
     * 容器的绑定
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * The container's method bindings.
     *
     * 容器的方法绑定
     *
     * @var array
     */
    protected $methodBindings = [];

    /**
     * The container's shared instances.
     *
     * 容器的共享实例
     *
     * @var array
     */
    protected $instances = [];

    /**
     * The registered type aliases.
     *
     * 注册的类型别名
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * The registered aliases keyed by the abstract name.
     *
     * 以抽象名称命名的已注册别名
     *
     * @var array
     */
    protected $abstractAliases = [];

    /**
     * The extension closures for services.
     *
     * 服务的扩展闭包
     *
     * @var array
     */
    protected $extenders = [];

    /**
     * All of the registered tags.
     *
     * 所有注册标签
     *
     * @var array
     */
    protected $tags = [];

    /**
     * The stack of concretions currently being built.
     *
     * 目前正在构建的栈
     *
     * @var array
     */
    protected $buildStack = [];

    /**
     * The contextual binding map.
     *
     * 上下文绑定映射
     *
     * @var array
     */
    public $contextual = [];

    /**
     * All of the registered rebound callbacks.
     *
     * 所有注册的反弹回调
     *
     * @var array
     */
    protected $reboundCallbacks = [];

    /**
     * All of the global resolving callbacks.
     *
     * 所有的全局解析回调
     *
     * @var array
     */
    protected $globalResolvingCallbacks = [];

    /**
     * All of the global after resolving callbacks.
     *
     * 所有的全局解析后回调
     *
     * @var array
     */
    protected $globalAfterResolvingCallbacks = [];

    /**
     * All of the resolving callbacks by class type.
     *
     * 所有的解析类型的回调函数
     *
     * @var array
     */
    protected $resolvingCallbacks = [];

    /**
     * All of the after resolving callbacks by class type.
     *
     * 所有的解析后类型的回调函数
     *
     * @var array
     */
    protected $afterResolvingCallbacks = [];

    /**
     * Define a contextual binding.
     *
     * 定义上下文绑定
     *
     * @param  string $concrete
     * @return \Illuminate\Contracts\Container\ContextualBindingBuilder
     */
    public function when($concrete)
    {
        //创建上下文绑定生成器
        return new ContextualBindingBuilder($this, $this->getAlias($concrete));
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * 确定给定的抽象类型是否已绑定
     *
     * @param  string $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        //容器的绑定数组、容器的共享实例数组或是否在别名数组中
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            $this->isAlias($abstract);
    }

    /**
     * Determine if the given abstract type has been resolved.
     *
     * 确定给定的抽象类型是否已被解析
     *
     * @param  string $abstract 抽象类型
     * @return bool
     */
    public function resolved($abstract)
    {
        //确定给定的字符串是否为别名
        if ($this->isAlias($abstract)) {
            // 获取一个可用抽象的别名
            $abstract = $this->getAlias($abstract);
        }
        //已解析的类型数组或容器的共享实例包含有这个抽象类型即可返回true
        return isset($this->resolved[$abstract]) ||
            isset($this->instances[$abstract]);
    }

    /**
     * Determine if a given type is shared.
     *
     * 确定给定类型是否共享
     *
     * @param  string $abstract
     * @return bool
     */
    public function isShared($abstract)
    {
        //容器的共享实例数组、容器的绑定数组中shared是否存在并且为true
        return isset($this->instances[$abstract]) ||
            (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * Determine if a given string is an alias.
     *
     * 确定给定的字符串是否为别名
     *
     * @param  string $name
     * @return bool
     */
    public function isAlias($name)
    {
        //在别名数组中是否存在
        return isset($this->aliases[$name]);
    }

    /**
     * Register a binding with the container.
     *
     * 与容器注册绑定
     *
     * @param  string|array $abstract 抽象类型
     * @param  \Closure|string|null $concrete 具体对象
     * @param  bool $shared 是否共享
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type. After that, the concrete type to be registered as shared
        // without being forced to state their classes in both of the parameters.
        //
        // 如果没有给予具体的类型，我们将简单地将具体类型设置为抽象类型。
        // 之后，将具体类型注册为共享的而不必强制在两个参数中声明它们的类型。
        //
        $this->dropStaleInstances($abstract); //删除所有旧的实例和别名

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // If the factory is not a Closure, it means it is just a class name which is
        // bound into this container to the abstract type and we will just wrap it
        // up inside its own Closure to give us more convenience when extending.
        //
        // 如果具体类型（$concrete）不是闭包，则意味着它是一个类名，将这个容器绑定到抽象类型，我们只需要将其封装在它自己的闭包中，这样就可以在扩展时给我们带来更多的便利。
        //
        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);   //返回闭包$concrete(具体实现，参数)
        }

        //创建一个包含变量名和它们的值的数组赋值给容器的绑定数组，键名为抽象类型
        //        Array
        //        (
        //            [concrete] => Closure Object
        //            (
        //               ……
        //            )
        //
        //            [shared] => 1
        //        )
        $this->bindings[$abstract] = compact('concrete', 'shared');

        // If the abstract type was already resolved in this container we'll fire the
        // rebound listener so that any objects which have already gotten resolved
        // can have their copy of the object updated via the listener callbacks.
        //
        // 如果抽象类型已经在这个容器解析，我们将发送回弹监听，所有已经解析的对象都可以通过监听回调得到对象的副本更新。
        //

        //已解析的类型数组或容器的共享实例包含有这个抽象类型即可返回true
        if ($this->resolved($abstract)) {
            //为给定的抽象类型发送回弹
            $this->rebound($abstract);
        }
    }

    /**
     * Get the Closure to be used when building a type.
     *
     * 在构建类型时使用闭包
     *
     * @param  string $abstract 抽象类型
     * @param  string $concrete 具体类型
     * @return \Closure 闭包（容器，参数）
     */
    protected function getClosure($abstract, $concrete)
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            $method = ($abstract == $concrete) ? 'build' : 'make'; //抽象类型与具体实现相等，使用build，否则make

            return $container->$method($concrete, $parameters);
        };
    }

    /**
     * Determine if the container has a method binding.
     *
     * 确定容器是否有方法绑定
     *
     * @param  string $method
     * @return bool
     */
    public function hasMethodBinding($method)
    {
        //容器的方法绑定数组中是否存在
        return isset($this->methodBindings[$method]);
    }

    /**
     * Bind a callback to resolve with Container::call.
     *
     * 用容器绑定调用回调
     *
     * @param  string $method
     * @param  \Closure $callback
     * @return void
     */
    public function bindMethod($method, $callback)
    {
        //容器的方法绑定数组添加新的内容
        $this->methodBindings[$method] = $callback;
    }

    /**
     * Get the method binding for the given method.
     *
     * 获取给定方法的方法绑定
     *
     * @param  string $method
     * @param  mixed $instance
     * @return mixed
     */
    public function callMethodBinding($method, $instance)
    {
        //执行容器的方法绑定数组中$method为key的方法，参数为传递来的实例和当前容器实例
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }

    /**
     * Add a contextual binding to the container.
     *
     * 向容器添加上下文绑定
     *
     * @param  string $concrete
     * @param  string $abstract
     * @param  \Closure|string $implementation
     * @return void
     */
    public function addContextualBinding($concrete, $abstract, $implementation)
    {
        //上下文绑定映射数组添加【具体实现】【抽象别名】= 实施内容
        $this->contextual[$concrete][$this->getAlias($abstract)] = $implementation;
    }

    /**
     * Register a binding if it hasn't already been registered.
     *
     * 注册绑定，如果它还没有注册
     *
     * @param  string $abstract
     * @param  \Closure|string|null $concrete
     * @param  bool $shared
     * @return void
     */
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        //如果给定的抽象类型没有绑定
        if (!$this->bound($abstract)) {
            //与容器注册绑定
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Register a shared binding in the container.
     *
     * 在容器中注册共享绑定
     *
     * @param  string|array $abstract 抽象类型
     * @param  \Closure|string|null $concrete 具体对象
     * @return void
     */
    public function singleton($abstract, $concrete = null)
    {
        //与容器注册绑定
        $this->bind($abstract, $concrete, true);
    }

    /**
     * "Extend" an abstract type in the container.
     *
     * “扩展”容器中的抽象类型
     *
     * @param  string $abstract
     * @param  \Closure $closure
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function extend($abstract, Closure $closure)
    {
        $abstract = $this->getAlias($abstract); //获取抽象类型别名

        if (isset($this->instances[$abstract])) { //如果实例数组中存在抽象别名的内容
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this); //指定实例数组中的【抽象别名】= 传递来的闭包（实例别名，当前容器）

            $this->rebound($abstract); //为给定的抽象类型发送回弹创建实例
        } else {
            $this->extenders[$abstract][] = $closure; //扩展闭包数组中添加【抽象别名】 = 传递来的闭包
        }
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * 在容器中注册一个已存在的实例
     *
     * @param  string $abstract
     * @param  mixed $instance
     * @return void
     */
    public function instance($abstract, $instance)
    {
        //从上下文绑定别名的缓存中移除别名
        $this->removeAbstractAlias($abstract);
        //删除注册的类型别名
        unset($this->aliases[$abstract]);

        // We'll check to determine if this type has been bound before, and if it has
        // we will fire the rebound callbacks registered with the container and it
        // can be updated with consuming classes that have gotten resolved here.
        //
        // 我们将检查确定这种类型已是否在之前就被绑定，如果它有
        // 我们将发送反弹回调注册与容器和这个类型
        // 可以在这里解析消费类更新
        //
        $this->instances[$abstract] = $instance;

        if ($this->bound($abstract)) { //与容器注册绑定
            $this->rebound($abstract); //为给定的抽象类型发送回弹创建实例
        }
    }

    /**
     * Remove an alias from the contextual binding alias cache.
     *
     * 从上下文绑定别名的缓存中移除别名
     *
     * @param  string $searched
     * @return void
     */
    protected function removeAbstractAlias($searched)
    {
        //如果注册的类型别名中不存在
        if (!isset($this->aliases[$searched])) {
            return;
        }
        //循环以抽象名称命名的已注册别名数组并删除
        foreach ($this->abstractAliases as $abstract => $aliases) {
            foreach ($aliases as $index => $alias) {
                if ($alias == $searched) {
                    unset($this->abstractAliases[$abstract][$index]);
                }
            }
        }
    }

    /**
     * Assign a set of tags to a given binding.
     *
     * 指定给定绑定的一组标记
     *
     * @param  array|string $abstracts
     * @param  array|mixed ...$tags
     * @return void
     */
    public function tag($abstracts, $tags)
    {
        $tags = is_array($tags) ? $tags : array_slice(func_get_args(), 1); //如果标记数组是数组，否则取方法参数第二个内容

        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) { // 如果tag在所有注册标签数组中不存在
                $this->tags[$tag] = []; // 添加到所有注册标签数组中
            }

            // 循环抽象类型数组，添加到所有注册标签数组中
            foreach ((array)$abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }

    /**
     * Resolve all of the bindings for a given tag.
     *
     * 解析给定标签的所有绑定
     *
     * @param  string $tag
     * @return array
     */
    public function tagged($tag)
    {
        $results = [];

        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $abstract) {
                $results[] = $this->make($abstract); // 从容器中解析给定类型，获得实例
            }
        }

        return $results;
    }

    /**
     * Alias a type to a different name.
     *
     * 别名为不同名称的类型
     *
     * @param  string $abstract
     * @param  string $alias
     * @return void
     */
    public function alias($abstract, $alias)
    {
        $this->aliases[$alias] = $abstract;

        $this->abstractAliases[$abstract][] = $alias;
    }

    /**
     * Bind a new callback to an abstract's rebind event.
     *
     * 绑定一个新的回调到抽象的绑定事件
     *
     * @param  string $abstract
     * @param  \Closure $callback
     * @return mixed
     */
    public function rebinding($abstract, Closure $callback)
    {
        $this->reboundCallbacks[$abstract = $this->getAlias($abstract)][] = $callback;
        // 确定给定的抽象类型是否已绑定
        if ($this->bound($abstract)) {
            // 从容器中解析给定类型
            return $this->make($abstract);
        }
    }

    /**
     * Refresh an instance on the given target and method.
     *
     * 在给定的目标和方法上刷新实例
     *
     * @param  string $abstract
     * @param  mixed $target
     * @param  string $method
     * @return mixed
     */
    public function refresh($abstract, $target, $method)
    {
        // 绑定一个新的回调到抽象的绑定事件
        return $this->rebinding($abstract, function ($app, $instance) use ($target, $method) {
            $target->{$method}($instance);
        });
    }

    /**
     * Fire the "rebound" callbacks for the given abstract type.
     *
     * 为给定的抽象类型发送回弹
     *
     * @param  string $abstract 抽象类型
     * @return void
     */
    protected function rebound($abstract)
    {
        //通过抽象类型从容器中解析给定实例
        $instance = $this->make($abstract);
        //获得一个给定类型的反弹回调
        foreach ($this->getReboundCallbacks($abstract) as $callback) {
            //执行反弹回调数组中的回调函数，参数为this(容器)和容器返回的抽象类型实例
            call_user_func($callback, $this, $instance);
        }
    }

    /**
     * Get the rebound callbacks for a given type.
     *
     * 获得一个给定类型的反弹回调
     *
     * @param  string $abstract
     * @return array
     */
    protected function getReboundCallbacks($abstract)
    {
        //所有注册的反弹回调数组中是否包含抽象类名
        if (isset($this->reboundCallbacks[$abstract])) {
            return $this->reboundCallbacks[$abstract];
        }

        return [];
    }

    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
     *
     * 将给定的闭包封装起来，以便在执行时将注入其依赖项
     *
     * @param  \Closure $callback
     * @param  array $parameters
     * @return \Closure
     */
    public function wrap(Closure $callback, array $parameters = [])
    {
        return function () use ($callback, $parameters) {
            return $this->call($callback, $parameters); // 调用给定的闭包/类@方法并注入它的依赖项
        };
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * 调用给定的闭包/类@方法并注入它的依赖项
     *
     * @param  callable|string $callback
     * @param  array $parameters
     * @param  string|null $defaultMethod
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        // 返回 用给定的闭包/类@方法并注入它的依赖项
        return BoundMethod::call($this, $callback, $parameters, $defaultMethod);
    }

    /**
     * Get a closure to resolve the given type from the container.
     *
     * 获取闭包以从容器中解析给定类型
     *
     * @param  string $abstract
     * @return \Closure
     */
    public function factory($abstract)
    {
        return function () use ($abstract) {
            return $this->make($abstract); //从容器中解析给定类型
        };
    }

    /**
     * Resolve the given type from the container.
     *
     * 从容器中解析给定类型
     *
     * @param  string $abstract
     * @return mixed
     */
    public function make($abstract)
    {
        $needsContextualBuild = !is_null(
            $this->getContextualConcrete($abstract = $this->getAlias($abstract)) //为给定的抽象得到上下文的具体绑定
        );

        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
        //
        // 如果实例类型是目前正在管理中的单独实例，我们只需要返回存在的实例来代替新的实例，所以开发者可以在任何时候持续使用相同的对象实例
        //
        if (isset($this->instances[$abstract]) && !$needsContextualBuild) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract); //获取给定的抽象的具体类型

        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.
        //
        // 我们已经准备好为绑定注册的具体类型实例化一个实例。这将实例化类型，以及解析任何其“嵌套”的依赖关系递归，直到所有得到解析。
        //
        if ($this->isBuildable($concrete, $abstract)) { // 确定给定的具体类是否构建
            $object = $this->build($concrete); // 实例化给定类型的具体实例
        } else {
            $object = $this->make($concrete); // 从容器中解析给定类型
        }

        // If we defined any extenders for this type, we'll need to spin through them
        // and apply them to the object being built. This allows for the extension
        // of services, such as changing configuration or decorating the object.
        //
        // 如果我们定义了这种类型的任何扩展，我们将需要反转它们并将它们应用到正在构建的对象上。这允许服务的扩展，例如改变配置或装饰对象。
        //
        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }

        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
        //
        // 如果请求的类型被注册为一个单独的类，我们将要缓存“内存”中的实例，这样我们就可以在不在其随后的请求上创建对象的完全新实例的情况下返回它。
        //
        if ($this->isShared($abstract) && !$needsContextualBuild) {
            $this->instances[$abstract] = $object;
        }
        //发送所有的解析回调
        $this->fireResolvingCallbacks($abstract, $object);

        $this->resolved[$abstract] = true;

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * 获取给定的抽象的具体类型
     *
     * @param  string $abstract
     * @return mixed   $concrete
     */
    protected function getConcrete($abstract)
    {
        if (!is_null($concrete = $this->getContextualConcrete($abstract))) { //为给定的抽象得到上下文的具体绑定
            return $concrete;
        }

        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        //
        //
        // 如果我们没有注册解析器或具体的类型，我们只假设每个类型是一个具体的名称，并试图解析它，因为容器应该能够自动具体的类型
        //
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Get the contextual concrete binding for the given abstract.
     *
     * 为给定的抽象得到上下文的具体绑定
     *
     * @param  string $abstract
     * @return string|null
     */
    protected function getContextualConcrete($abstract)
    {
        if (!is_null($binding = $this->findInContextualBindings($abstract))) { // 查找上下文绑定数组中给定的抽象的具体绑定
            return $binding;
        }

        // Next we need to see if a contextual binding might be bound under an alias of the
        // given abstract type. So, we will need to check if any aliases exist with this
        // type and then spin through them and check for contextual bindings on these.
        //
        // 然后，我们需要查看上下文绑定是否绑定在给定抽象类型的别名之下。因此，我们将需要检查此类型是否存在任何别名，然后通过反转它们检查这些上下文绑定。
        //
        if (empty($this->abstractAliases[$abstract])) { // 以抽象名称命名的已注册别名，如果不存在，无返回值
            return;
        }

        foreach ($this->abstractAliases[$abstract] as $alias) {
            if (!is_null($binding = $this->findInContextualBindings($alias))) { // 查找上下文绑定数组中给定的抽象的具体绑定
                return $binding;
            }
        }
    }

    /**
     * Find the concrete binding for the given abstract in the contextual binding array.
     *
     * 查找上下文绑定数组中给定的抽象的具体绑定
     *
     * @param  string $abstract
     * @return string|null
     */
    protected function findInContextualBindings($abstract)
    {
        // 上下文绑定映射数组【最后一个正在构建的栈数组元素】【抽象类型名】是否存在，存在则返回
        if (isset($this->contextual[end($this->buildStack)][$abstract])) {
            return $this->contextual[end($this->buildStack)][$abstract];
        }
    }

    /**
     * Determine if the given concrete is buildable.
     *
     * 确定给定的具体是否构建
     *
     * @param  mixed $concrete
     * @param  string $abstract
     * @return bool
     */
    protected function isBuildable($concrete, $abstract)
    {
        // 返回具体实现和抽象类名是否相等或者具体实现是一个闭包
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * 实例化给定类型的具体实例
     *
     * @param  string $concrete
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function build($concrete)
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        //
        // 如果具体类型实际上是一个闭包，我们只会执行并获取它的结果，使功能更精细的解析方式作为这些对象解析器。
        //
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        $reflector = new ReflectionClass($concrete);  //否则，创建反射类

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface of Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        //
        // 如果类型不可实例化，开发者会尝试使用抽象类型的接口尝试并释放抽象化没有绑定的注册
        //
        if (!$reflector->isInstantiable()) {  //测试该类是否被实例化过
            return $this->notInstantiable($concrete);  //抛出一个异常，具体类型不可实例化
        }

        $this->buildStack[] = $concrete; //将具体实例加入到构建栈数组中

        $constructor = $reflector->getConstructor(); //取得具体实现类的构造函数信息

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        //
        // 如果没有构造函数，意思是没有依赖，我们可以立即解析对象的实例，而不需要解析任何其他类型或依赖关系
        //
        if (is_null($constructor)) { //如果具体实现类的构造函数信息为null
            array_pop($this->buildStack);//将刚刚加入的具体实例弹出，删除数组中的最后一个元素

            return new $concrete;//new 一个具体实现类
        }

        $dependencies = $constructor->getParameters(); //获得具体实现类构造函数的参数

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        //
        // 当我们拥有所有构造函数的参数后，我们可以创建每个依赖实例，然后使用反射实例来创建这个类的新实例，将创建的依赖项注入
        //
        $instances = $this->resolveDependencies(    //获取所有参数，利用反射参数解析所有的参数依赖
            $dependencies
        );

        array_pop($this->buildStack);//将刚刚加入的具体实例弹出，删除数组中的最后一个元素

        return $reflector->newInstanceArgs($instances); //创建具体实现类的实例
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * 利用反射参数解析所有的参数依赖
     *
     * @param  array $dependencies 参数数组
     * @return array
     */
    protected function resolveDependencies(array $dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
            //
            // 如果类是空的，则表示依赖项是一个字符串或其他一些原始类型，而我们不能解析，因为它不是一个类，我们将用抛出错误，因为我们没有地方去
            //
            $results[] = is_null($class = $dependency->getClass()) //若该参数为对象，返回该对象的类名
                ? $this->resolvePrimitive($dependency)  //解析非类的原始依赖
                : $this->resolveClass($dependency); //从容器解析基于类的依赖项
        }

        return $results;
    }

    /**
     * Resolve a non-class hinted primitive dependency.
     *
     * 解析非类暗示的原始依赖
     *
     * @param  \ReflectionParameter $parameter
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if (!is_null($concrete = $this->getContextualConcrete('$' . $parameter->name))) { // 为给定的抽象得到上下文的具体绑定
            return $concrete instanceof Closure ? $concrete($this) : $concrete; //如果是闭包返回执行的闭包，否则返回类型
        }
        //如果是普通类型参数
        if ($parameter->isDefaultValueAvailable()) { ///测试该参数是否为默认参数
            return $parameter->getDefaultValue(); //取得该参数的默认值
        }

        $this->unresolvablePrimitive($parameter); //抛出一个无法解析的原始类型错误
    }

    /**
     * Resolve a class based dependency from the container.
     *
     * 从容器解析基于类的依赖项
     *
     * @param  \ReflectionParameter $parameter
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->make($parameter->getClass()->name); //从容器中解析给定类型
        }

            // If we can not resolve the class instance, we will check to see if the value
            // is optional, and if it is we will return the optional parameter value as
            // the value of the dependency, similarly to how we do this with scalars.
            //
            // 如果我们不能解析该类的实例，我们将看看值是否是可选的，如果它是我们将依赖的回归参数的可选值，类似于我们如何做这个标量
            //
        catch (BindingResolutionException $e) {
            if ($parameter->isOptional()) { ///测试该参数是否为可选的，当有默认参数时可选
                return $parameter->getDefaultValue(); //取得该参数的默认值
            }

            throw $e; //抛出异常
        }
    }

    /**
     * Throw an exception that the concrete is not instantiable.
     *
     * 抛出一个异常，具体类型不可实例化
     *
     * @param  string $concrete
     * @return void
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function notInstantiable($concrete)
    {
        if (!empty($this->buildStack)) {   //如果目前正在构建的栈不为空
            $previous = implode(', ', $this->buildStack);

            $message = "Target [$concrete] is not instantiable while building [$previous].";
        } else {
            $message = "Target [$concrete] is not instantiable.";
        }

        throw new BindingResolutionException($message);
    }

    /**
     * Throw an exception for an unresolvable primitive.
     *
     * 抛出一个无法解析的原始类型错误
     *
     * @param  \ReflectionParameter $parameter
     * @return void
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter)
    {
        // getDeclaringClass() 返回所有声明的类
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    /**
     * Register a new resolving callback.
     *
     * 注册一个新的解析回调
     *
     * @param  string $abstract
     * @param  \Closure|null $callback
     * @return void
     */
    public function resolving($abstract, Closure $callback = null)
    {
        if (is_string($abstract)) { // 如果抽象类型是字符串，获取抽象类型的别名
            $abstract = $this->getAlias($abstract);
        }

        if (is_null($callback) && $abstract instanceof Closure) { // 如果回调是空并且抽象类型是一个闭包
            $this->globalResolvingCallbacks[] = $abstract; //所有的全局解析回调数组添加抽象类型
        } else {
            $this->resolvingCallbacks[$abstract][] = $callback;//否则所有的解析类型的回调函数数组添加回调参数
        }
    }

    /**
     * Register a new after resolving callback for all types.
     *
     * 为所有类型注册一个新的解析后的回调
     *
     * @param  string $abstract
     * @param  \Closure|null $callback
     * @return void
     */
    public function afterResolving($abstract, Closure $callback = null)
    {
        if (is_string($abstract)) { // 如果抽象类型是字符串，获取抽象类型的别名
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) { // 如果回调是空并且抽象类型是一个闭包
            $this->globalAfterResolvingCallbacks[] = $abstract;//所有的全局解析后回调数组添加抽象类型
        } else {
            $this->afterResolvingCallbacks[$abstract][] = $callback;//否则所有的解析后类型的回调函数数组添加回调参数
        }
    }

    /**
     * Fire all of the resolving callbacks.
     *
     * 执行所有的解析回调
     *
     * @param  string $abstract
     * @param  mixed $object
     * @return void
     */
    protected function fireResolvingCallbacks($abstract, $object)
    {
        $this->fireCallbackArray($object, $this->globalResolvingCallbacks); //执行对象的回调数组（对象，所有的全局解析回调）

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks) //得到一个给定类型的所有回调，getCallbacksForType参数（抽象类型，对象，所有的解析类型的回调函数）
        ); // 执行对象的回调数组

        $this->fireAfterResolvingCallbacks($abstract, $object); // 执行所有的解析后回调
    }

    /**
     * Fire all of the after resolving callbacks.
     *
     * 执行所有的解析后回调
     *
     * @param  string $abstract
     * @param  mixed $object
     * @return void
     */
    protected function fireAfterResolvingCallbacks($abstract, $object)
    {
        $this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks); //执行对象的回调数组（对象，所有的全局解析后回调）

        $this->fireCallbackArray(
            $object, $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks) //得到一个给定类型的所有回调，getCallbacksForType参数（抽象类型，对象，所有的解析后类型的回调函数）
        );
    }

    /**
     * Get all callbacks for a given type.
     *
     * 得到一个给定类型的所有回调
     *
     * @param  string $abstract
     * @param  object $object
     * @param  array $callbacksPerType
     *
     * @return array
     */
    protected function getCallbacksForType($abstract, $object, array $callbacksPerType)
    {
        $results = [];

        foreach ($callbacksPerType as $type => $callbacks) {
            if ($type === $abstract || $object instanceof $type) {
                $results = array_merge($results, $callbacks);
            }
        }

        return $results;
    }

    /**
     * Fire an array of callbacks with an object.
     *
     * 执行对象的回调数组
     *
     * @param  mixed $object
     * @param  array $callbacks
     * @return void
     */
    protected function fireCallbackArray($object, array $callbacks)
    {
        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    /**
     * Get the container's bindings.
     *
     * 获取容器的绑定
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Get the alias for an abstract if available.
     *
     * 获取一个可用抽象的别名
     *
     * @param  string $abstract
     * @return string
     *
     * @throws \LogicException
     */
    public function getAlias($abstract)
    {
        if (!isset($this->aliases[$abstract])) {
            return $abstract;
        }

        if ($this->aliases[$abstract] === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }

        return $this->getAlias($this->aliases[$abstract]);
    }

    /**
     * Get the extender callbacks for a given type.
     *
     * 获得一个给定类型的扩展回调
     *
     * @param  string $abstract
     * @return array
     */
    protected function getExtenders($abstract)
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->extenders[$abstract])) {
            return $this->extenders[$abstract];
        }

        return [];
    }

    /**
     * Drop all of the stale instances and aliases.
     *
     * 删除所有旧的实例和别名
     *
     * @param  string $abstract
     * @return void
     */
    protected function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * Remove a resolved instance from the instance cache.
     *
     * 从实例缓存中移除解析实例
     *
     * @param  string $abstract
     * @return void
     */
    public function forgetInstance($abstract)
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Clear all of the instances from the container.
     *
     * 清除容器中的所有实例
     *
     * @return void
     */
    public function forgetInstances()
    {
        $this->instances = [];
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * 刷新所有绑定的容器并解析实例
     *
     * @return void
     */
    public function flush()
    {
        $this->aliases         = [];
        $this->resolved        = [];
        $this->bindings        = [];
        $this->instances       = [];
        $this->abstractAliases = [];
    }

    /**
     * Set the globally available instance of the container.
     *
     * 设置容器的全局可用实例
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * 设置容器的共享实例
     *
     * @param  \Illuminate\Contracts\Container\Container|null $container
     * @return static
     */
    public static function setInstance(ContainerContract $container = null)
    {
        return static::$instance = $container;
    }

    /**
     * Determine if a given offset exists.
     *
     * 确定是否存在给定的偏移量
     *
     * @param  string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        //返回 确定给定的抽象类型是否已绑定
        return $this->bound($key);
    }

    /**
     * Get the value at a given offset.
     *
     * 得到给定偏移量的值
     *
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        // 返回 从容器中解析给定类型
        return $this->make($key);
    }

    /**
     * Set the value at a given offset.
     *
     * 设置给定偏移量的值
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        // 返回 与容器注册绑定
        $this->bind($key, $value instanceof Closure ? $value : function () use ($value) {
            return $value;
        });
    }

    /**
     * Unset the value at a given offset.
     *
     * 删除给定偏移量的值
     *
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
    }

    /**
     * Dynamically access container services.
     *
     * 动态访问容器服务
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     *
     * 动态设置容器服务
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }
}
