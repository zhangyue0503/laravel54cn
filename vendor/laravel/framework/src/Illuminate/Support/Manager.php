<?php

namespace Illuminate\Support;

use Closure;
use InvalidArgumentException;

abstract class Manager
{
    /**
     * The application instance.
     *
     * 应用实例
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The registered custom driver creators.
     *
     * 注册自定义驱动程序的创建者
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * The array of created "drivers".
     *
     * 创建的“驱动”数组
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * Create a new manager instance.
     *
     * 创建一个新的管理器实例
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get the default driver name.
     *
     * 获取默认的驱动名称
     *
     * @return string
     */
    abstract public function getDefaultDriver();

    /**
     * Get a driver instance.
	 *
	 * 获取驱动实例
     *
     * @param  string  $driver
     * @return mixed
     */
    public function driver($driver = null)
    {
        $driver = $driver ?: $this->getDefaultDriver(); //获取默认会话驱动程序名称

        // If the given driver has not been created before, we will create the instances
        // here and cache it so we can return it next time very quickly. If there is
        // already a driver created by this name, we'll just return that instance.
		//
		// 如果给定的驱动程序没有被创建之前，我们将创建的实例，并缓存它，以便我们可以返回它下一次很快
		// 如果已经有由该名称创建的驱动程序，我们将返回该实例
		//
        if (! isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver); //创建一个新的驱动实例
        }

        return $this->drivers[$driver];
    }

    /**
     * Create a new driver instance.
	 *
	 * 创建一个新的驱动实例
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
		//                将值转换为大驼峰 createFileDriver....
        $method = 'create'.Str::studly($driver).'Driver';

        // We'll check to see if a creator method exists for the given driver. If not we
        // will check for a custom driver creator, which allows developers to create
        // drivers using their own customized driver creator Closure to create it.
		//
		// 我们将检查是否为给定的驱动程序存在创建者方法
		// 如果不是，我们将检查一个自定义驱动程序创建者，它允许开发人员使用自己定制的驱动程序创建者闭包创建驱动程序来创建它
		//
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);//调用自定义驱动程序创建者
        } elseif (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Driver [$driver] not supported.");
    }

    /**
     * Call a custom driver creator.
	 *
	 * 调用自定义驱动程序创建者
     *
     * @param  string  $driver
     * @return mixed
     */
    protected function callCustomCreator($driver)
    {
        return $this->customCreators[$driver]($this->app);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * 注册一个自定义驱动程序创建者的闭包
     *
     * @param  string    $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Get all of the created "drivers".
     *
     * 获取所有创建的“驱动程序”
     *
     * @return array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * 动态调用默认驱动程序实例
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        //        获取驱动实例
        return $this->driver()->$method(...$parameters);
    }
}
