<?php

namespace Illuminate\Events;

use Exception;
use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Dispatcher implements DispatcherContract
{
    /**
     * The IoC container instance.
     *
     * IoC容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The registered event listeners.
     *
     * 已注册的事件监听器
     *
     * @var array
     */
    protected $listeners = [];

    /**
     * The wildcard listeners.
     *
     * 通配符的监听
     *
     * @var array
     */
    protected $wildcards = [];

    /**
     * The queue resolver instance.
     *
     * 队列解析实例
     *
     * @var callable
     */
    protected $queueResolver;

    /**
     * Create a new event dispatcher instance.
     *
     * 创建一个新的事件调度实例
     *
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     * @return void
     */
    public function __construct(ContainerContract $container = null)
    {
        $this->container = $container ?: new Container;
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * 用分配器注册事件监听器
     *
     * @param  string|array  $events
     * @param  mixed  $listener
     * @return void
     */
    public function listen($events, $listener)
    {
        foreach ((array) $events as $event) {
            if (Str::contains($event, '*')) {
                $this->setupWildcardListen($event, $listener); //设置通配符侦听器回调
            } else {
                $this->listeners[$event][] = $this->makeListener($listener); //用分配器注册事件监听器
            }
        }
    }

    /**
     * Setup a wildcard listener callback.
     *
     * 设置通配符侦听器回调
     *
     * @param  string  $event
     * @param  mixed  $listener
     * @return void
     */
    protected function setupWildcardListen($event, $listener)
    {
        $this->wildcards[$event][] = $this->makeListener($listener, true); //用分配器注册事件监听器
    }

    /**
     * Determine if a given event has listeners.
     *
     * 确定给定事件是否有侦听器
     *
     * @param  string  $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return isset($this->listeners[$eventName]) || isset($this->wildcards[$eventName]);
    }

    /**
     * Register an event and payload to be fired later.
     *
     * 注册事件和有效载荷稍后被触发
     *
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function push($event, $payload = [])
    {
        //用分配器注册事件监听器
        $this->listen($event.'_pushed', function () use ($event, $payload) {
            $this->dispatch($event, $payload); //触发事件并调用监听器
        });
    }

    /**
     * Flush a set of pushed events.
     *
     * 刷新一组推送的事件
     *
     * @param  string  $event
     * @return void
     */
    public function flush($event)
    {
        $this->dispatch($event.'_pushed'); // 触发事件并调用监听器
    }

    /**
     * Register an event subscriber with the dispatcher.
     *
     * 使用分配器注册事件订阅服务器
     *
     * @param  object|string  $subscriber
     * @return void
     */
    public function subscribe($subscriber)
    {
        $subscriber = $this->resolveSubscriber($subscriber); //解析订阅实例

        $subscriber->subscribe($this);
    }

    /**
     * Resolve the subscriber instance.
     *
     * 解析订阅实例
     *
     * @param  object|string  $subscriber
     * @return mixed
     */
    protected function resolveSubscriber($subscriber)
    {
        if (is_string($subscriber)) {
            return $this->container->make($subscriber); // 从容器中解析给定类型
        }

        return $subscriber;
    }

    /**
     * Fire an event until the first non-null response is returned.
     *
     * 将事件触发，直到返回第一个非空响应
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @return array|null
     */
    public function until($event, $payload = [])
    {
        return $this->dispatch($event, $payload, true); // 触发事件并调用监听器
    }

    /**
     * Fire an event and call the listeners.
     *
     * 触发事件并调用监听器
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = false)
    {
        return $this->dispatch($event, $payload, $halt); // 触发事件并调用监听器
    }

    /**
     * Fire an event and call the listeners.
     *
     * 触发事件并调用监听器
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        // When the given "event" is actually an object we will assume it is an event
        // object and use the class as the event name and this event itself as the
        // payload to the handler, which makes object based events quite simple.
        //
        // 当给定的“事件”实际上是一个对象时，我们将假设它是一个事件对象，并使用类作为事件名，而这个事件本身作为处理程序的有效载荷，这使得基于对象的事件变得非常简单
        //
        list($event, $payload) = $this->parseEventAndPayload( //解析给定的事件和有效载荷，并为调度做好准备
            $event, $payload
        );

        if ($this->shouldBroadcast($payload)) {  //确定有效载荷有broadcastable事件
            $this->broadcastEvent($payload[0]); // 广播给定事件类
        }

        $responses = [];

        foreach ($this->getListeners($event) as $listener) { //获取给定事件名称的所有侦听器
            $response = $listener($event, $payload);

            // If a response is returned from the listener and event halting is enabled
            // we will just return this response, and not call the rest of the event
            // listeners. Otherwise we will add the response on the response list.
            //
            // 如果从侦听器返回响应并启用事件中止，我们将返回此响应，而不调用事件侦听器的其余部分
            // 否则，我们将在响应列表中添加响应
            //
            if (! is_null($response) && $halt) {
                return $response;
            }

            // If a boolean false is returned from a listener, we will stop propagating
            // the event to any further listeners down in the chain, else we keep on
            // looping through the listeners and firing every one in our sequence.
            //
            // 如果从侦听器返回一个布尔错误，我们将停止将事件传播给链中的任何进一步的侦听器，否则我们将继续通过侦听器循环并在序列中触发每一个
            //
            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        return $halt ? null : $responses;
    }

    /**
     * Parse the given event and payload and prepare them for dispatching.
     *
     * 解析给定的事件和有效载荷，并为调度做好准备
     *
     * @param  mixed  $event
     * @param  mixed  $payload
     * @return array
     */
    protected function parseEventAndPayload($event, $payload)
    {
        if (is_object($event)) {
            list($payload, $event) = [[$event], get_class($event)];
        }
        //               如果给定值不是数组，请将其包在一个数组中
        return [$event, array_wrap($payload)];
    }

    /**
     * Determine if the payload has a broadcastable event.
     *
     * 确定有效载荷有broadcastable事件
     *
     * @param  array  $payload
     * @return bool
     */
    protected function shouldBroadcast(array $payload)
    {
        return isset($payload[0]) && $payload[0] instanceof ShouldBroadcast;
    }

    /**
     * Broadcast the given event class.
     *
     * 广播给定事件类
     *
     * @param  \Illuminate\Contracts\Broadcasting\ShouldBroadcast  $event
     * @return void
     */
    protected function broadcastEvent($event)
    {
        //                 从容器中解析给定类型
        $this->container->make(BroadcastFactory::class)->queue($event);
    }

    /**
     * Get all of the listeners for a given event name.
     *
     * 获取给定事件名称的所有侦听器
     *
     * @param  string  $eventName
     * @return array
     */
    public function getListeners($eventName)
    {
        $listeners = isset($this->listeners[$eventName]) ? $this->listeners[$eventName] : [];

        $listeners = array_merge(
            $listeners, $this->getWildcardListeners($eventName) //获取事件的通配符侦听器
        );

        return class_exists($eventName, false)
                    ? $this->addInterfaceListeners($eventName, $listeners)     //将事件的监听器添加到给定数组中
                    : $listeners;
    }

    /**
     * Get the wildcard listeners for the event.
     *
     * 获取事件的通配符侦听器
     *
     * @param  string  $eventName
     * @return array
     */
    protected function getWildcardListeners($eventName)
    {
        $wildcards = [];

        foreach ($this->wildcards as $key => $listeners) {
            if (Str::is($key, $eventName)) {
                $wildcards = array_merge($wildcards, $listeners);
            }
        }

        return $wildcards;
    }

    /**
     * Add the listeners for the event's interfaces to the given array.
     *
     * 将事件的监听器添加到给定数组中
     *
     * @param  string  $eventName
     * @param  array  $listeners
     * @return array
     */
    protected function addInterfaceListeners($eventName, array $listeners = [])
    {
        foreach (class_implements($eventName) as $interface) {
            if (isset($this->listeners[$interface])) {
                foreach ($this->listeners[$interface] as $names) {
                    $listeners = array_merge($listeners, (array) $names);
                }
            }
        }

        return $listeners;
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * 用分配器注册事件监听器
     *
     * @param  string|\Closure  $listener
     * @param  bool  $wildcard
     * @return mixed
     */
    public function makeListener($listener, $wildcard = false)
    {
        if (is_string($listener)) {
            return $this->createClassListener($listener, $wildcard); //使用Ioc容器创建基于类的侦听器
        }

        return function ($event, $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                return $listener($event, $payload);
            } else {
                return $listener(...array_values($payload));
            }
        };
    }

    /**
     * Create a class based listener using the IoC container.
     *
     * 使用Ioc容器创建基于类的侦听器
     *
     * @param  string  $listener
     * @param  bool  $wildcard
     * @return \Closure
     */
    public function createClassListener($listener, $wildcard = false)
    {
        return function ($event, $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                //                        创建基于类的事件可调用
                return call_user_func($this->createClassCallable($listener), $event, $payload);
            } else {
                return call_user_func_array(
                    //    创建基于类的事件可调用
                    $this->createClassCallable($listener), $payload
                );
            }
        };
    }

    /**
     * Create the class based event callable.
     *
     * 创建基于类的事件可调用
     *
     * @param  string  $listener
     * @return callable
     */
    protected function createClassCallable($listener)
    {
        list($class, $method) = $this->parseClassCallable($listener);      //解析类侦听器到类和方法

        if ($this->handlerShouldBeQueued($class)) {      //确定事件处理程序类是否应该排队
            return $this->createQueuedHandlerCallable($class, $method); //创建将事件处理程序放在队列上的调用
        } else {
            //         从容器中解析给定类型
            return [$this->container->make($class), $method];
        }
    }

    /**
     * Parse the class listener into class and method.
     *
     * 解析类侦听器到类和方法
     *
     * @param  string  $listener
     * @return array
     */
    protected function parseClassCallable($listener)
    {
        //          解析 类@方法 类型回调到类和方法
        return Str::parseCallback($listener, 'handle');
    }

    /**
     * Determine if the event handler class should be queued.
     *
     * 确定事件处理程序类是否应该排队
     *
     * @param  string  $class
     * @return bool
     */
    protected function handlerShouldBeQueued($class)
    {
        try {
            return (new ReflectionClass($class))->implementsInterface( //检查是否实现了接口
                ShouldQueue::class
            );
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create a callable for putting an event handler on the queue.
     *
     * 创建将事件处理程序放在队列上的调用
     *
     * @param  string  $class
     * @param  string  $method
     * @return \Closure
     */
    protected function createQueuedHandlerCallable($class, $method)
    {
        return function () use ($class, $method) {
            $arguments = array_map(function ($a) {
                return is_object($a) ? clone $a : $a;
            }, func_get_args());

            if (method_exists($class, 'queue')) {
                $this->callQueueMethodOnHandler($class, $method, $arguments); //在处理程序类上调用队列方法
            } else {
                $this->queueHandler($class, $method, $arguments);//队列处理程序类
            }
        };
    }

    /**
     * Call the queue method on the handler class.
     *
     * 在处理程序类上调用队列方法
     *
     * @param  string  $class
     * @param  string  $method
     * @param  array  $arguments
     * @return void
     */
    protected function callQueueMethodOnHandler($class, $method, $arguments)
    {
        $handler = (new ReflectionClass($class))->newInstanceWithoutConstructor(); // 创建一个新的类的实例而不调用它的构造函数
        //                从解析器获取队列实现
        $handler->queue($this->resolveQueue(), 'Illuminate\Events\CallQueuedHandler@call', [
            'class' => $class, 'method' => $method, 'data' => serialize($arguments),
        ]);
    }

    /**
     * Queue the handler class.
     *
     * 队列处理程序类
     *
     * @param  string  $class
     * @param  string  $method
     * @param  array  $arguments
     * @return void
     */
    protected function queueHandler($class, $method, $arguments)
    {
        $listener = (new ReflectionClass($class))->newInstanceWithoutConstructor(); // 创建一个新的类的实例而不调用它的构造函数

        $connection = isset($listener->connection) ? $listener->connection : null;

        $queue = isset($listener->queue) ? $listener->queue : null;

        $this->resolveQueue() //从解析器获取队列实现
                ->connection($connection) //解析队列连接实例
                ->pushOn($queue, new CallQueuedListener($class, $method, $arguments)); //将新工作推到队列上
    }

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * 从调度程序中移除一组侦听器
     *
     * @param  string  $event
     * @return void
     */
    public function forget($event)
    {
        if (Str::contains($event, '*')) {
            unset($this->wildcards[$event]);
        } else {
            unset($this->listeners[$event]);
        }
    }

    /**
     * Forget all of the pushed listeners.
     *
     * 忘记所有被推的听众
     *
     * @return void
     */
    public function forgetPushed()
    {
        foreach ($this->listeners as $key => $value) {
            if (Str::endsWith($key, '_pushed')) {
                $this->forget($key);
            }
        }
    }

    /**
     * Get the queue implementation from the resolver.
     *
     * 从解析器获取队列实现
     *
     * @return \Illuminate\Contracts\Queue\Queue
     */
    protected function resolveQueue()
    {
        return call_user_func($this->queueResolver);
    }

    /**
     * Set the queue resolver implementation.
     *
     * 设置队列解析器实现
     *
     * @param  callable  $resolver
     * @return $this
     */
    public function setQueueResolver(callable $resolver)
    {
        $this->queueResolver = $resolver;

        return $this;
    }
}
