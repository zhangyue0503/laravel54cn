<?php

namespace Illuminate\Broadcasting;

use Pusher;
use Closure;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Illuminate\Broadcasting\Broadcasters\LogBroadcaster;
use Illuminate\Broadcasting\Broadcasters\NullBroadcaster;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\Broadcasters\RedisBroadcaster;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Contracts\Broadcasting\Factory as FactoryContract;

class BroadcastManager implements FactoryContract
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
     * The array of resolved broadcast drivers.
     *
     * 解析的广播驱动程序数组
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * The registered custom driver creators.
     *
     * 注册自定义驱动程序的创建者
     *
     * @var array
     */
    protected $customCreators = [];

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
     * Register the routes for handling broadcast authentication and sockets.
     *
     * 注册用于处理广播身份验证和套接字的路由
     *
     * @param  array|null  $attributes
     * @return void
     */
    public function routes(array $attributes = null)
    {
        //确定应用程序路由是否被缓存
        if ($this->app->routesAreCached()) {
            return;
        }

        $attributes = $attributes ?: ['middleware' => ['web']];
        //                  创建具有共享属性的路由组
        $this->app['router']->group($attributes, function ($router) {
            //用路由器注册新的POST路由
            $router->post('/broadcasting/auth', BroadcastController::class.'@authenticate');
        });
    }

    /**
     * Get the socket ID for the given request.
     *
     * 获取给定请求的套接字ID
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return string|null
     */
    public function socket($request = null)
    {
        //                               确定给定的抽象类型是否已绑定
        if (! $request && ! $this->app->bound('request')) {
            return;
        }

        $request = $request ?: $this->app['request'];

        if ($request->hasHeader('X-Socket-ID')) {
            return $request->header('X-Socket-ID');
        }
    }

    /**
     * Begin broadcasting an event.
     *
     * 开始广播一个事件
     *
     * @param  mixed|null  $event
     * @return \Illuminate\Broadcasting\PendingBroadcast|void
     */
    public function event($event = null)
    {
        //创建一个新的等待广播实例(从容器中解析给定类型)
        return new PendingBroadcast($this->app->make('events'), $event);
    }

    /**
     * Queue the given event for broadcast.
     *
     * 为广播队列排队
     *
     * @param  mixed  $event
     * @return void
     */
    public function queue($event)
    {
        $connection = $event instanceof ShouldBroadcastNow ? 'sync' : null;

        if (is_null($connection) && isset($event->connection)) {
            $connection = $event->connection;
        }

        $queue = null;

        if (isset($event->broadcastQueue)) {
            $queue = $event->broadcastQueue;
        } elseif (isset($event->queue)) {
            $queue = $event->queue;
        }
        //从容器中解析给定类型->解析队列连接实例->将新工作推到队列上
        $this->app->make('queue')->connection($connection)->pushOn(
            $queue, new BroadcastEvent(clone $event)//创建一个新的作业处理程序实例
        );
    }

    /**
     * Get a driver instance.
     *
     * 获取驱动实例
     *
     * @param  string  $driver
     * @return mixed
     */
    public function connection($driver = null)
    {
        //获取驱动实例
        return $this->driver($driver);
    }

    /**
     * Get a driver instance.
     *
     * 获取驱动实例
     *
     * @param  string  $name
     * @return mixed
     */
    public function driver($name = null)
    {
        //获取默认的驱动程序名称
        $name = $name ?: $this->getDefaultDriver();
        //                                 尝试从本地缓存获取连接
        return $this->drivers[$name] = $this->get($name);
    }

    /**
     * Attempt to get the connection from the local cache.
     *
     * 尝试从本地缓存获取连接
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function get($name)
    {
        return isset($this->drivers[$name]) ? $this->drivers[$name] : $this->resolve($name);
    }

    /**
     * Resolve the given store.
     *
     * 解析给定的存储
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        //获取连接配置
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Broadcaster [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            //调用自定义驱动程序创建者
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (! method_exists($this, $driverMethod)) {
            throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
        }

        return $this->{$driverMethod}($config);
    }

    /**
     * Call a custom driver creator.
     *
     * 调用自定义驱动程序创建者
     *
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create an instance of the driver.
     *
     * 创建驱动程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createPusherDriver(array $config)
    {
        //创建一个新的广播实例
        return new PusherBroadcaster(
            new Pusher($config['key'], $config['secret'],
            //                   使用“点”符号从数组中获取一个项
            $config['app_id'], Arr::get($config, 'options', []))
        );
    }

    /**
     * Create an instance of the driver.
     *
     * 创建驱动程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createRedisDriver(array $config)
    {
        //创建一个新的广播实例
        return new RedisBroadcaster(
            //从容器中解析给定类型              使用“点”符号从数组中获取一个项
            $this->app->make('redis'), Arr::get($config, 'connection')
        );
    }

    /**
     * Create an instance of the driver.
     *
     * 创建驱动程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createLogDriver(array $config)
    {
        //创建一个新的广播实例
        return new LogBroadcaster(
            //从容器中解析给定类型
            $this->app->make(LoggerInterface::class)
        );
    }

    /**
     * Create an instance of the driver.
     *
     * 创建驱动程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createNullDriver(array $config)
    {
        return new NullBroadcaster;
    }

    /**
     * Get the connection configuration.
     *
     * 获取连接配置
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["broadcasting.connections.{$name}"];
    }

    /**
     * Get the default driver name.
     *
     * 获取默认的驱动程序名称
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['broadcasting.default'];
    }

    /**
     * Set the default driver name.
     *
     * 设置默认的驱动程序名称
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['broadcasting.default'] = $name;
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
        return $this->driver()->$method(...$parameters);
    }
}
