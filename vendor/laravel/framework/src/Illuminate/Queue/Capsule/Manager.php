<?php

namespace Illuminate\Queue\Capsule;

use Illuminate\Queue\QueueManager;
use Illuminate\Container\Container;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Support\Traits\CapsuleManagerTrait;

class Manager
{
    use CapsuleManagerTrait;

    /**
     * The queue manager instance.
     *
     * 队列管理器实例
     *
     * @var \Illuminate\Queue\QueueManager
     */
    protected $manager;

    /**
     * Create a new queue capsule manager.
     *
     * 创建一个新的队列压缩管理器
     *
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        //设置IoC容器实例
        $this->setupContainer($container ?: new Container);

        // Once we have the container setup, we will setup the default configuration
        // options in the container "config" bindings. This just makes this queue
        // manager behave correctly since all the correct binding are in place.
        //
        // 一旦我们有了容器设置，我们将在容器“config”绑定中设置默认的配置选项
        // 这使得队列管理器的行为正确，因为所有正确的绑定都已就绪
        //
        //        设置默认队列配置选项
        $this->setupDefaultConfiguration();
        //构建队列管理器实例
        $this->setupManager();
        //注册组件所附带的缺省连接器
        $this->registerConnectors();
    }

    /**
     * Setup the default queue configuration options.
     *
     * 设置默认队列配置选项
     *
     * @return void
     */
    protected function setupDefaultConfiguration()
    {
        $this->container['config']['queue.default'] = 'default';
    }

    /**
     * Build the queue manager instance.
     *
     * 构建队列管理器实例
     *
     * @return void
     */
    protected function setupManager()
    {
        $this->manager = new QueueManager($this->container);
    }

    /**
     * Register the default connectors that the component ships with.
     *
     * 注册组件所附带的缺省连接器
     *
     * @return void
     */
    protected function registerConnectors()
    {
        $provider = new QueueServiceProvider($this->container);
        //     在队列管理器上注册连接器
        $provider->registerConnectors($this->manager);
    }

    /**
     * Get a connection instance from the global manager.
     *
     * 从全局管理器获取连接实例
     *
     * @param  string  $connection
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public static function connection($connection = null)
    {
        return static::$instance->getConnection($connection);
    }

    /**
     * Push a new job onto the queue.
     *
     * 把新工作推到队列上
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @param  string  $connection
     * @return mixed
     */
    public static function push($job, $data = '', $queue = null, $connection = null)
    {
        return static::$instance->connection($connection)->push($job, $data, $queue);
    }

    /**
     * Push a new an array of jobs onto the queue.
     *
     * 将一个新的作业数组推到队列中
     *
     * @param  array   $jobs
     * @param  mixed   $data
     * @param  string  $queue
     * @param  string  $connection
     * @return mixed
     */
    public static function bulk($jobs, $data = '', $queue = null, $connection = null)
    {
        return static::$instance->connection($connection)->bulk($jobs, $data, $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * 在延迟之后将新作业推到队列上
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @param  string  $connection
     * @return mixed
     */
    public static function later($delay, $job, $data = '', $queue = null, $connection = null)
    {
        return static::$instance->connection($connection)->later($delay, $job, $data, $queue);
    }

    /**
     * Get a registered connection instance.
     *
     * 获得注册的连接实例
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function getConnection($name = null)
    {
        //                   解析队列连接实例
        return $this->manager->connection($name);
    }

    /**
     * Register a connection with the manager.
     *
     * 与管理器注册一个连接
     *
     * @param  array   $config
     * @param  string  $name
     * @return void
     */
    public function addConnection(array $config, $name = 'default')
    {
        $this->container['config']["queue.connections.{$name}"] = $config;
    }

    /**
     * Get the queue manager instance.
     *
     * 获取队列管理器实例
     *
     * @return \Illuminate\Queue\QueueManager
     */
    public function getQueueManager()
    {
        return $this->manager;
    }

    /**
     * Pass dynamic instance methods to the manager.
     *
     * 将动态实例方法传递给管理器
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->manager->$method(...$parameters);
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * 动态地将方法传递给默认连接
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        //从全局管理器获取连接实例
        return static::connection()->$method(...$parameters);
    }
}
