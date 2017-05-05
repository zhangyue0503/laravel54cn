<?php

namespace Illuminate\Auth\Access;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class Gate implements GateContract
{
    use HandlesAuthorization;

    /**
     * The container instance.
     *
     * 窗口实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The user resolver callable.
     *
     * 用户解析回调
     *
     * @var callable
     */
    protected $userResolver;

    /**
     * All of the defined abilities.
     *
     * 所有已定义的能力
     *
     * @var array
     */
    protected $abilities = [];

    /**
     * All of the defined policies.
     *
     * 所有已定义的策略
     *
     * @var array
     */
    protected $policies = [];

    /**
     * All of the registered before callbacks.
     *
     * 所有在回调之前注册的
     *
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * All of the registered after callbacks.
     *
     * 所有在回调之后注册的
     *
     * @var array
     */
    protected $afterCallbacks = [];

    /**
     * Create a new gate instance.
     *
     * 创建一个新的gate实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  callable  $userResolver
     * @param  array  $abilities
     * @param  array  $policies
     * @param  array  $beforeCallbacks
     * @param  array  $afterCallbacks
     * @return void
     */
    public function __construct(Container $container, callable $userResolver, array $abilities = [],
                                array $policies = [], array $beforeCallbacks = [], array $afterCallbacks = [])
    {
        $this->policies = $policies;
        $this->container = $container;
        $this->abilities = $abilities;
        $this->userResolver = $userResolver;
        $this->afterCallbacks = $afterCallbacks;
        $this->beforeCallbacks = $beforeCallbacks;
    }

    /**
     * Determine if a given ability has been defined.
     *
     * 确定是否已经定义了给定的能力
     *
     * @param  string  $ability
     * @return bool
     */
    public function has($ability)
    {
        return isset($this->abilities[$ability]);
    }

    /**
     * Define a new ability.
     *
     * 定义一个新的能力
     *
     * @param  string  $ability
     * @param  callable|string  $callback
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function define($ability, $callback)
    {
        if (is_callable($callback)) {
            $this->abilities[$ability] = $callback;
            //                               确定一个给定的字符串包含另一个字符串
        } elseif (is_string($callback) && Str::contains($callback, '@')) {
            //                                为回调字符串创建能力回调
            $this->abilities[$ability] = $this->buildAbilityCallback($callback);
        } else {
            throw new InvalidArgumentException("Callback must be a callable or a 'Class@method' string.");
        }

        return $this;
    }

    /**
     * Create the ability callback for a callback string.
     *
     * 为回调字符串创建能力回调
     *
     * @param  string  $callback
     * @return \Closure
     */
    protected function buildAbilityCallback($callback)
    {
        return function () use ($callback) {
            //                      解析 类@方法 类型回调到类和方法
            list($class, $method) = Str::parseCallback($callback);
            //构建给定类型的策略类实例
            return $this->resolvePolicy($class)->{$method}(...func_get_args());
        };
    }

    /**
     * Define a policy class for a given class type.
     *
     * 为给定类类型定义策略类
     *
     * @param  string  $class
     * @param  string  $policy
     * @return $this
     */
    public function policy($class, $policy)
    {
        $this->policies[$class] = $policy;

        return $this;
    }

    /**
     * Register a callback to run before all Gate checks.
     *
     * 在所有的Gate检查之前注册一个回调来运行
     *
     * @param  callable  $callback
     * @return $this
     */
    public function before(callable $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to run after all Gate checks.
     *
     * 在所有的Gate检查之后注册一个回调来运行
     *
     * @param  callable  $callback
     * @return $this
     */
    public function after(callable $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * 确定当前用户是否应该授予给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function allows($ability, $arguments = [])
    {
        //确定当前用户是否应该授予给定的能力
        return $this->check($ability, $arguments);
    }

    /**
     * Determine if the given ability should be denied for the current user.
     *
     * 确定当前用户是否应该拒绝给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function denies($ability, $arguments = [])
    {
        //确定当前用户是否应该授予给定的能力
        return ! $this->allows($ability, $arguments);
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * 确定当前用户是否应该授予给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function check($ability, $arguments = [])
    {
        try {
            //              从授权回调获取原始结果
            return (bool) $this->raw($ability, $arguments);
        } catch (AuthorizationException $e) {
            return false;
        }
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * 确定当前用户是否应该授予给定的能力
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return \Illuminate\Auth\Access\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorize($ability, $arguments = [])
    {
        //             从授权回调获取原始结果
        $result = $this->raw($ability, $arguments);

        if ($result instanceof Response) {
            return $result;
        }
        //               创建一个新的访问响应         抛出未授权的异常
        return $result ? $this->allow() : $this->deny();
    }

    /**
     * Get the raw result from the authorization callback.
     *
     * 从授权回调获取原始结果
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return mixed
     */
    protected function raw($ability, $arguments = [])
    {
        //                从用户解析器中解析用户
        if (! $user = $this->resolveUser()) {
            return false;
        }
        //           如果给定值不是数组，请将其包在一个数组中
        $arguments = array_wrap($arguments);

        // First we will call the "before" callbacks for the Gate. If any of these give
        // back a non-null response, we will immediately return that result in order
        // to let the developers override all checks for some authorization cases.
        //
        // 首先，我们将调用Gate的“before”回调
        // 如果其中任何一个返回非空响应，我们将立即返回该结果，以便让开发人员覆盖所有检查以获得某些授权情况
        //
        //              调用所有之前的回调函数，并返回结果
        $result = $this->callBeforeCallbacks(
            $user, $ability, $arguments
        );

        if (is_null($result)) {
            //              解析并调用适当的授权回调
            $result = $this->callAuthCallback($user, $ability, $arguments);
        }

        // After calling the authorization callback, we will call the "after" callbacks
        // that are registered with the Gate, which allows a developer to do logging
        // if that is required for this application. Then we'll return the result.
        //
        // 调用了授权回调之后，我们将调用在Gate注册的“After”回调，这允许开发人员在该应用程序需要的情况下进行日志记录
        // 然后我们会返回结果
        //
        //        用检查结果调用所有的回调函数
        $this->callAfterCallbacks(
            $user, $ability, $arguments, $result
        );

        return $result;
    }

    /**
     * Resolve and call the appropriate authorization callback.
     *
     * 解析并调用适当的授权回调
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return bool
     */
    protected function callAuthCallback($user, $ability, array $arguments)
    {
        //              解决给定能力和参数的可调用性
        $callback = $this->resolveAuthCallback($user, $ability, $arguments);

        return $callback($user, ...$arguments);
    }

    /**
     * Call all of the before callbacks and return if a result is given.
     *
     * 调用所有之前的回调函数，并返回结果
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return bool|null
     */
    protected function callBeforeCallbacks($user, $ability, array $arguments)
    {
        $arguments = array_merge([$user, $ability], [$arguments]);

        foreach ($this->beforeCallbacks as $before) {
            if (! is_null($result = $before(...$arguments))) {
                return $result;
            }
        }
    }

    /**
     * Call all of the after callbacks with check result.
     *
     * 用检查结果调用所有的回调函数
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @param  bool  $result
     * @return void
     */
    protected function callAfterCallbacks($user, $ability, array $arguments, $result)
    {
        $arguments = array_merge([$user, $ability, $result], [$arguments]);

        foreach ($this->afterCallbacks as $after) {
            $after(...$arguments);
        }
    }

    /**
     * Resolve the callable for the given ability and arguments.
     *
     * 解决给定能力和参数的可调用性
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return callable
     */
    protected function resolveAuthCallback($user, $ability, array $arguments)
    {
        if (isset($arguments[0])) {
            //                      获取给定类的策略实例
            if (! is_null($policy = $this->getPolicyFor($arguments[0]))) {
                //           为策略检查解析回调
                return $this->resolvePolicyCallback($user, $ability, $arguments, $policy);
            }
        }

        if (isset($this->abilities[$ability])) {
            return $this->abilities[$ability];
        } else {
            return function () {
                return false;
            };
        }
    }

    /**
     * Get a policy instance for a given class.
     *
     * 获取给定类的策略实例
     *
     * @param  object|string  $class
     * @return mixed
     */
    public function getPolicyFor($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (! is_string($class)) {
            return null;
        }

        if (isset($this->policies[$class])) {
            //构建给定类型的策略类实例
            return $this->resolvePolicy($this->policies[$class]);
        }

        foreach ($this->policies as $expected => $policy) {
            if (is_subclass_of($class, $expected)) {
                //构建给定类型的策略类实例
                return $this->resolvePolicy($policy);
            }
        }
    }

    /**
     * Build a policy class instance of the given type.
     *
     * 构建给定类型的策略类实例
     *
     * @param  object|string  $class
     * @return mixed
     */
    public function resolvePolicy($class)
    {
        //                  从容器中解析给定类型
        return $this->container->make($class);
    }

    /**
     * Resolve the callback for a policy check.
     *
     * 为策略检查解析回调
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @param  mixed  $policy
     * @return callable
     */
    protected function resolvePolicyCallback($user, $ability, array $arguments, $policy)
    {
        return function () use ($user, $ability, $arguments, $policy) {
            // This callback will be responsible for calling the policy's before method and
            // running this policy method if necessary. This is used to when objects are
            // mapped to policy objects in the user's configurations or on this class.
            //
            // 这个回调将负责调用策略之前的方法，并在必要时运行此策略方法
            // 当对象被映射到用户配置或该类中的策略对象时，就会使用这种方式
            //
            //             在给定的策略上调用“before”方法，如果适用的话
            $result = $this->callPolicyBefore(
                $policy, $user, $ability, $arguments
            );

            // When we receive a non-null result from this before method, we will return it
            // as the "final" results. This will allow developers to override the checks
            // in this policy to return the result for all rules defined in the class.
            //
            // 当我们收到一个非空的结果从这个方法之前,我们将返回它的“最终”的结果
            // 这将允许开发人员在该策略中覆盖检查，以返回在类中定义的所有规则的结果
            //
            if (! is_null($result)) {
                return $result;
            }
            //              将策略能力格式化为一个方法名
            $ability = $this->formatAbilityToMethod($ability);

            // If this first argument is a string, that means they are passing a class name
            // to the policy. We will remove the first argument from this argument array
            // because this policy already knows what type of models it can authorize.
            //
            // 如果第一个参数是字符串，那就意味着它们将一个类名传递给策略
            // 我们将从这个参数数组中删除第一个参数，因为这个策略已经知道它可以授权什么类型的模型
            //
            if (isset($arguments[0]) && is_string($arguments[0])) {
                array_shift($arguments);
            }

            return is_callable([$policy, $ability])
                        ? $policy->{$ability}($user, ...$arguments)
                        : false;
        };
    }

    /**
     * Call the "before" method on the given policy, if applicable.
     *
     * 在给定的策略上调用“before”方法，如果适用的话
     *
     * @param  mixed  $policy
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return mixed
     */
    protected function callPolicyBefore($policy, $user, $ability, $arguments)
    {
        if (method_exists($policy, 'before')) {
            return $policy->before($user, $ability, ...$arguments);
        }
    }

    /**
     * Format the policy ability into a method name.
     *
     * 将策略能力格式化为一个方法名
     *
     * @param  string  $ability
     * @return string
     */
    protected function formatAbilityToMethod($ability)
    {
        //                                           转换值为驼峰命名
        return strpos($ability, '-') !== false ? Str::camel($ability) : $ability;
    }

    /**
     * Get a gate instance for the given user.
     *
     * 为给定的用户获取一个gate实例
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @return static
     */
    public function forUser($user)
    {
        $callback = function () use ($user) {
            return $user;
        };

        return new static(
            $this->container, $callback, $this->abilities,
            $this->policies, $this->beforeCallbacks, $this->afterCallbacks
        );
    }

    /**
     * Resolve the user from the user resolver.
     *
     * 从用户解析器中解析用户
     *
     * @return mixed
     */
    protected function resolveUser()
    {
        return call_user_func($this->userResolver);
    }
}
