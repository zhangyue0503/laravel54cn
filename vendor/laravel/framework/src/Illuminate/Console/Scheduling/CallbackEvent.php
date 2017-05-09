<?php

namespace Illuminate\Console\Scheduling;

use LogicException;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;

class CallbackEvent extends Event
{
    /**
     * The callback to call.
     *
     * 调用的回调
     *
     * @var string
     */
    protected $callback;

    /**
     * The parameters to pass to the method.
     *
     * 传递给该方法的参数
     *
     * @var array
     */
    protected $parameters;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @param  string  $callback
     * @param  array  $parameters
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Cache $cache, $callback, array $parameters = [])
    {
        if (! is_string($callback) && ! is_callable($callback)) {
            throw new InvalidArgumentException(
                'Invalid scheduled callback event. Must be a string or callable.'
            );
        }

        $this->cache = $cache;
        $this->callback = $callback;
        $this->parameters = $parameters;
    }

    /**
     * Run the given event.
     *
     * 运行给定的事件
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return mixed
     *
     * @throws \Exception
     */
    public function run(Container $container)
    {
        if ($this->description) {
            //在缓存中存储一个项        为调度的命令获取互斥的名称
            $this->cache->put($this->mutexName(), true, 1440);
        }

        try {
            //                   调用给定的闭包/类@方法并注入它的依赖项
            $response = $container->call($this->callback, $this->parameters);
        } finally {
            //从磁盘中删除互斥文件
            $this->removeMutex();
        }
        //为事件调用所有的“after”回调
        parent::callAfterCallbacks($container);

        return $response;
    }

    /**
     * Remove the mutex file from disk.
     *
     * 从磁盘中删除互斥文件
     *
     * @return void
     */
    protected function removeMutex()
    {
        if ($this->description) {
            //从缓存中删除一个项目        为调度的命令获取互斥的名称
            $this->cache->forget($this->mutexName());
        }
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * 不要让事件相互重叠
     *
     * @return $this
     *
     * @throws \LogicException
     */
    public function withoutOverlapping()
    {
        if (! isset($this->description)) {
            throw new LogicException(
                "A scheduled event name is required to prevent overlapping. Use the 'name' method before 'withoutOverlapping'."
            );
        }
        //注册一个回调以进一步筛选调度
        return $this->skip(function () {
            //确定缓存中是否存在某个项           为调度的命令获取互斥的名称
            return $this->cache->has($this->mutexName());
        });
    }

    /**
     * Get the mutex name for the scheduled command.
     *
     * 为调度的命令获取互斥的名称
     *
     * @return string
     */
    public function mutexName()
    {
        return 'framework/schedule-'.sha1($this->description);
    }

    /**
     * Get the summary of the event for display.
     *
     * 获取显示事件的摘要
     *
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) {
            return $this->description;
        }

        return is_string($this->callback) ? $this->callback : 'Closure';
    }
}
