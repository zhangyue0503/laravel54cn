<?php

namespace Illuminate\Bus;

use Closure;
use RuntimeException;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Bus\QueueingDispatcher;

class Dispatcher implements QueueingDispatcher
{
    /**
     * The container implementation.
     *
     * 容器实现
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The pipeline instance for the bus.
     *
     * 总线的管道实例
     *
     * @var \Illuminate\Pipeline\Pipeline
     */
    protected $pipeline;

    /**
     * The pipes to send commands through before dispatching.
     *
     * 在发送命令之前通过管道发送命令
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * The command to handler mapping for non-self-handling events.
     *
     * 命令处理程序映射non-self-handling事件
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * The queue resolver callback.
     *
     * 队列解析器回调
     *
     * @var \Closure|null
     */
    protected $queueResolver;

    /**
     * Create a new command dispatcher instance.
	 *
	 * 创建新的命令调度实例
	 * * 一个新的命令分发器实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  \Closure|null  $queueResolver
     * @return void
     */
    public function __construct(Container $container, Closure $queueResolver = null)
    {
        $this->container = $container;
        $this->queueResolver = $queueResolver;
        $this->pipeline = new Pipeline($container);//创建新的实例
    }

    /**
     * Dispatch a command to its appropriate handler.
	 *
	 * 向适当的处理程序发送命令
	 * * 分发一个命令到适当的处理模块
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command)
    {
        if ($this->queueResolver && $this->commandShouldBeQueued($command)) {
            return $this->dispatchToQueue($command);//向队列后面的适当处理程序发送命令
        } else {
            return $this->dispatchNow($command);//在当前进程中向其适当的处理程序发送命令
        }
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
	 *
	 * 在当前进程中向其适当的处理程序发送命令
	 * * 在当前进程中将命令发送给适当的处理模块
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchNow($command, $handler = null)
    {
        //                               获取一个命令的处理程序
        if ($handler || $handler = $this->getCommandHandler($command)) {
            $callback = function ($command) use ($handler) {
                return $handler->handle($command);
            };
        } else {
            $callback = function ($command) {
                return $this->container->call([$command, 'handle']);//调用给定的闭包/类@方法并注入它的依赖项
            };
        }
        //              设置通过管道发送的对象->设置管道数组->使用最终目标回调来运行管道
        return $this->pipeline->send($command)->through($this->pipes)->then($callback);
    }

    /**
     * Determine if the given command has a handler.
     *
     * 确定给定的命令是否具有处理程序
     *
     * @param  mixed  $command
     * @return bool
     */
    public function hasCommandHandler($command)
    {
        return array_key_exists(get_class($command), $this->handlers);
    }

    /**
     * Retrieve the handler for a command.
     *
     * 获取一个命令的处理程序
     *
     * @param  mixed  $command
     * @return bool|mixed
     */
    public function getCommandHandler($command)
    {
        //确定给定的命令是否具有处理程序
        if ($this->hasCommandHandler($command)) {
            //从容器中解析给定类型
            return $this->container->make($this->handlers[get_class($command)]);
        }

        return false;
    }

    /**
     * Determine if the given command should be queued.
     *
     * 确定给定的命令是否应该排队
     *
     * @param  mixed  $command
     * @return bool
     */
    protected function commandShouldBeQueued($command)
    {
        return $command instanceof ShouldQueue;
    }

    /**
     * Dispatch a command to its appropriate handler behind a queue.
     *
	 * 向队列后面的适当处理程序发送命令
	 * * 分发命令到对应的消息队列中
	 *
     * @param  mixed  $command
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function dispatchToQueue($command)
    {
        $connection = isset($command->connection) ? $command->connection : null;

        $queue = call_user_func($this->queueResolver, $connection);

        if (! $queue instanceof Queue) {
            throw new RuntimeException('Queue resolver did not return a Queue implementation.');
        }

        if (method_exists($command, 'queue')) {
            return $command->queue($queue, $command);
        } else {
            //将命令推送到给定的队列实例
            return $this->pushCommandToQueue($queue, $command);
        }
    }

    /**
     * Push the command onto the given queue instance.
	 *
	 * 将命令推送到给定的队列实例
	 * * 推送一条命令到消息队列实例
     *
     * @param  \Illuminate\Contracts\Queue\Queue  $queue
     * @param  mixed  $command
     * @return mixed
     */
    protected function pushCommandToQueue($queue, $command)
    {
        if (isset($command->queue, $command->delay)) {
            //在延迟之后将新作业推到队列上
            return $queue->laterOn($command->queue, $command->delay, $command);
        }

        if (isset($command->queue)) {
            //把新工作推到队列上
            return $queue->pushOn($command->queue, $command);
        }

        if (isset($command->delay)) {
            //在延迟之后将新作业推到队列上
            return $queue->later($command->delay, $command);
        }
        //推送一条新的消息到队列
        return $queue->push($command);
    }

    /**
     * Set the pipes through which commands should be piped before dispatching.
     *
     * 设置管道，在分派之前，命令应该通过管道进行
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes)
    {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * Map a command to a handler.
     *
     * 将命令映射到处理程序
     *
     * @param  array  $map
     * @return $this
     */
    public function map(array $map)
    {
        $this->handlers = array_merge($this->handlers, $map);

        return $this;
    }
}
