<?php

namespace Illuminate\Queue;

use Closure;
use InvalidArgumentException;
use Illuminate\Contracts\Queue\Factory as FactoryContract;
use Illuminate\Contracts\Queue\Monitor as MonitorContract;

class QueueManager implements FactoryContract, MonitorContract
{
    /**
     * The application instance.
     *
     * 应用程序实例
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved queue connections.
     *
     * 已解析队列连接的数组
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The array of resolved queue connectors.
     *
     * 解析队列连接器的数组
     *
     * @var array
     */
    protected $connectors = [];

    /**
     * Create a new queue manager instance.
	 *
	 * 创建一个新的队列管理器实例
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Register an event listener for the before job event.
     *
     * 为之前的作业事件注册一个事件侦听器
     *
     * @param  mixed  $callback
     * @return void
     */
    public function before($callback)
    {
        //              用分配器注册事件监听器
        $this->app['events']->listen(Events\JobProcessing::class, $callback);
    }

    /**
     * Register an event listener for the after job event.
     *
     * 为后面的作业事件注册一个事件侦听器
     *
     * @param  mixed  $callback
     * @return void
     */
    public function after($callback)
    {
        //              用分配器注册事件监听器
        $this->app['events']->listen(Events\JobProcessed::class, $callback);
    }

    /**
     * Register an event listener for the exception occurred job event.
     *
     * 为异常发生的作业事件注册一个事件侦听器
     *
     * @param  mixed  $callback
     * @return void
     */
    public function exceptionOccurred($callback)
    {
        //              用分配器注册事件监听器
        $this->app['events']->listen(Events\JobExceptionOccurred::class, $callback);
    }

    /**
     * Register an event listener for the daemon queue loop.
     *
     * 为守护进程队列循环注册一个事件侦听器
     *
     * @param  mixed  $callback
     * @return void
     */
    public function looping($callback)
    {
        //              用分配器注册事件监听器
        $this->app['events']->listen(Events\Looping::class, $callback);
    }

    /**
     * Register an event listener for the failed job event.
     *
     * 为失败的作业事件注册一个事件侦听器
     *
     * @param  mixed  $callback
     * @return void
     */
    public function failing($callback)
    {
        //              用分配器注册事件监听器
        $this->app['events']->listen(Events\JobFailed::class, $callback);
    }

    /**
     * Register an event listener for the daemon queue stopping.
     *
     * 为守护队列停止注册一个事件侦听器
     *
     * @param  mixed  $callback
     * @return void
     */
    public function stopping($callback)
    {
        //              用分配器注册事件监听器
        $this->app['events']->listen(Events\WorkerStopping::class, $callback);
    }

    /**
     * Determine if the driver is connected.
     *
     * 确定驱动程序是否连接
     *
     * @param  string  $name
     * @return bool
     */
    public function connected($name = null)
    {
        //                                      获取默认队列连接的名称
        return isset($this->connections[$name ?: $this->getDefaultDriver()]);
    }

    /**
     * Resolve a queue connection instance.
     *
     * 解析队列连接实例
	 * * 获取一个消息队列连接器实例
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connection($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();//获取默认队列连接的名称

        // If the connection has not been resolved yet we will resolve it now as all
        // of the connections are resolved when they are actually needed so we do
        // not make any unnecessary connection to the various queue end-points.
		//
		// 如果连接尚未解决，我们将解决它，因为所有的连接解决时，他们实际上需要，所以我们不做任何不必要的连接到不同的队列端点
		//
        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);//解决一个消息队列连接

            $this->connections[$name]->setContainer($this->app);//设置IoC容器实例
        }

        return $this->connections[$name];
    }

    /**
     * Resolve a queue connection.
	 *
	 * 解决队列连接
	 * * 解决一个消息队列连接
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);//获取队列连接配置

        return $this->getConnector($config['driver'])//获取给定驱动器的连接器
                        ->connect($config)//建立队列连接
                        ->setConnectionName($name);//设置队列的连接名称
    }

    /**
     * Get the connector for a given driver.
	 *
	 * 获取给定驱动器的连接器
     *
     * @param  string  $driver
     * @return \Illuminate\Queue\Connectors\ConnectorInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function getConnector($driver)
    {
        if (! isset($this->connectors[$driver])) {
            throw new InvalidArgumentException("No connector for [$driver]");
        }

        return call_user_func($this->connectors[$driver]);
    }

    /**
     * Add a queue connection resolver.
     *
     * 添加队列连接解析器
     *
     * @param  string    $driver
     * @param  \Closure  $resolver
     * @return void
     */
    public function extend($driver, Closure $resolver)
    {
        return $this->addConnector($driver, $resolver);//添加队列连接解析器
    }

    /**
     * Add a queue connection resolver.
	 *
	 * 添加队列连接解析器
     *
     * @param  string    $driver
     * @param  \Closure  $resolver
     * @return void
     */
    public function addConnector($driver, Closure $resolver)
    {
        $this->connectors[$driver] = $resolver;
    }

    /**
     * Get the queue connection configuration.
	 *
	 * 获取队列连接配置
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        if (! is_null($name) && $name !== 'null') {
            return $this->app['config']["queue.connections.{$name}"];
        }

        return ['driver' => 'null'];
    }

    /**
     * Get the name of the default queue connection.
	 *
	 * 获取默认队列连接的名称
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['queue.default'];
    }

    /**
     * Set the name of the default queue connection.
     *
     * 设置缺省队列连接的名称
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['queue.default'] = $name;
    }

    /**
     * Get the full name for the given connection.
     *
     * 获取给定连接的完整名称
     *
     * @param  string  $connection
     * @return string
     */
    public function getName($connection = null)
    {
        return $connection ?: $this->getDefaultDriver();//获取默认队列连接的名称
    }

    /**
     * Determine if the application is in maintenance mode.
     *
     * 确定应用程序是否处于维护模式
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return $this->app->isDownForMaintenance();//确定当前应用程序是否正在维护
    }

    /**
     * Dynamically pass calls to the default connection.
     *
     * 将调用动态地传递给默认连接
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        //         解析队列连接实例
        return $this->connection()->$method(...$parameters);
    }
}
