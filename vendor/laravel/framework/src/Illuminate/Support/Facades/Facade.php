<?php

namespace Illuminate\Support\Facades;

use Mockery;
use RuntimeException;
use Mockery\MockInterface;

abstract class Facade
{
    /**
     * The application instance being facaded.
     *
     * facade的应用实例
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected static $app;

    /**
     * The resolved object instances.
     *
     * 解析过的对象实例
     *
     * @var array
     */
    protected static $resolvedInstance;

    /**
     * Convert the facade into a Mockery spy.
     *
     * 转换facade为Mockery spy
     *
     * @return void
     */
    public static function spy()
    {
        if (! static::isMock()) {//确定是否将模拟设置为外观的实例
            $class = static::getMockableClass();//获得绑定的实例mockable类
            //热交换facade底层的实例
            static::swap($class ? Mockery::spy($class) : Mockery::spy());
        }
    }

    /**
     * Initiate a mock expectation on the facade.
     *
     * 开始对facade的模拟预期
     *
     * @return \Mockery\Expectation
     */
    public static function shouldReceive()
    {
        //获取组件的注册名称
        $name = static::getFacadeAccessor();

        $mock = static::isMock()//确定是否将模拟设置为外观的实例
                    ? static::$resolvedInstance[$name]
                    : static::createFreshMockInstance();//为给定类创建新的模拟实例
        //开始对facade的模拟预期
        return $mock->shouldReceive(...func_get_args());
    }

    /**
     * Create a fresh mock instance for the given class.
     *
     * 为给定类创建新的模拟实例
     *
     * @return \Mockery\Expectation
     */
    protected static function createFreshMockInstance()
    {
        //用给定的值调用给定的闭包，然后返回值(为给定类创建新的模拟实例,)
        return tap(static::createMock(), function ($mock) {
            static::swap($mock);//热交换facade底层的实例
            //应该允许模仿保护的方法
            $mock->shouldAllowMockingProtectedMethods();
        });
    }

    /**
     * Create a fresh mock instance for the given class.
     *
     * 为给定类创建新的模拟实例
     *
     * @return \Mockery\MockInterface
     */
    protected static function createMock()
    {
        $class = static::getMockableClass();//获得绑定的实例mockable类

        return $class ? Mockery::mock($class) : Mockery::mock();
    }

    /**
     * Determines whether a mock is set as the instance of the facade.
     *
     * 确定是否将模拟设置为外观的实例
     *
     * @return bool
     */
    protected static function isMock()
    {
        $name = static::getFacadeAccessor();//获取组件的注册名称

        return isset(static::$resolvedInstance[$name]) &&
               static::$resolvedInstance[$name] instanceof MockInterface;
    }

    /**
     * Get the mockable class for the bound instance.
     *
     * 获得绑定的实例mockable类
     *
     * @return string|null
     */
    protected static function getMockableClass()
    {
        //              获取外观后面的根对象
        if ($root = static::getFacadeRoot()) {
            return get_class($root);
        }
    }

    /**
     * Hotswap the underlying instance behind the facade.
     *
     * 热交换facade底层的实例
     *
     * @param  mixed  $instance
     * @return void
     */
    public static function swap($instance)
    {
        //                             获取组件的注册名称
        static::$resolvedInstance[static::getFacadeAccessor()] = $instance;

        if (isset(static::$app)) {
            //      在容器中注册一个已存在的实例     获取组件的注册名称
            static::$app->instance(static::getFacadeAccessor(), $instance);
        }
    }

    /**
     * Get the root object behind the facade.
	 *
	 * 获取外观后面的根对象
     *
     * @return mixed
     */
    public static function getFacadeRoot()
    {
		//         从容器解析外观的根实例          获取组件的注册名称
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Get the registered name of the component.
     *
     * 获取组件的注册名称
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * Resolve the facade root instance from the container.
	 *
	 * 从容器解析外观的根实例
	 * * 通过服务容器解决外观对应的实例
     *
     * @param  string|object  $name
     * @return mixed
     */
    protected static function resolveFacadeInstance($name)
    {
        if (is_object($name)) {
            return $name;
        }

        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        return static::$resolvedInstance[$name] = static::$app[$name];
    }

    /**
     * Clear a resolved facade instance.
     *
     * 清除已经解析的facade实例
     *
     * @param  string  $name
     * @return void
     */
    public static function clearResolvedInstance($name)
    {
        unset(static::$resolvedInstance[$name]);
    }

    /**
     * Clear all of the resolved instances.
	 *
	 * 清除所有已解决的实例
     *
     * @return void
     */
    public static function clearResolvedInstances()
    {
        static::$resolvedInstance = [];
    }

    /**
     * Get the application instance behind the facade.
     *
     * 获取facade的应用实例
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public static function getFacadeApplication()
    {
        return static::$app;
    }

    /**
     * Set the application instance.
	 *
	 * 设置应用实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public static function setFacadeApplication($app)
    {
        static::$app = $app;
    }

    /**
     * Handle dynamic, static calls to the object.
	 *
	 * 处理对象的动态、静态调用
	 * * 处理一个对象的动态或静态方法
     *
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();//获取外观后面的根对象

        if (! $instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}
