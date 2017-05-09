<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Application;
use Illuminate\Container\Container;
use Symfony\Component\Process\ProcessUtils;
use Illuminate\Contracts\Cache\Repository as Cache;

class Schedule
{
    /**
     * The cache store implementation.
     *
     * 缓存存储实现
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * All of the events on the schedule.
     *
     * 所有的活动都安排在日程上
     *
     * @var array
     */
    protected $events = [];

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Add a new callback event to the schedule.
     *
     * 将一个新的回调事件添加到调度中
     *
     * @param  string|callable  $callback
     * @param  array   $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function call($callback, array $parameters = [])
    {
        $this->events[] = $event = new CallbackEvent($this->cache, $callback, $parameters);

        return $event;
    }

    /**
     * Add a new Artisan command event to the schedule.
     *
     * 将一个新的Artisan命令事件添加到调度中
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function command($command, array $parameters = [])
    {
        if (class_exists($command)) {
            //            设置容器的全局可用实例->从容器中解析给定类型
            $command = Container::getInstance()->make($command)->getName();
        }
        //将一个新的命令事件添加到调度中
        return $this->exec(
            //将给定的命令格式化为完全限定的可执行命令
            Application::formatCommandString($command), $parameters
        );
    }

    /**
     * Add a new command event to the schedule.
     *
     * 将一个新的命令事件添加到调度中
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function exec($command, array $parameters = [])
    {
        if (count($parameters)) {
            //                为命令编译参数
            $command .= ' '.$this->compileParameters($parameters);
        }

        $this->events[] = $event = new Event($this->cache, $command);

        return $event;
    }

    /**
     * Compile parameters for a command.
     *
     * 为命令编译参数
     *
     * @param  array  $parameters
     * @return string
     */
    protected function compileParameters(array $parameters)
    {
        //                           在每个项目上运行map
        return collect($parameters)->map(function ($value, $key) {
            if (is_array($value)) {
                //                        在每个项目上运行map
                $value = collect($value)->map(function ($value) {
                    return ProcessUtils::escapeArgument($value);//转义字符串用作shell参数
                })->implode(' ');//一个给定的键连接的值作为一个字符串
            } elseif (! is_numeric($value) && ! preg_match('/^(-.$|--.*)/i', $value)) {
                $value = ProcessUtils::escapeArgument($value);//转义字符串用作shell参数
            }

            return is_numeric($key) ? $value : "{$key}={$value}";
        })->implode(' ');//一个给定的键连接的值作为一个字符串
    }

    /**
     * Get all of the events on the schedule that are due.
     *
     * 把所有的事情都安排在日程表上
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return array
     */
    public function dueEvents($app)
    {
        return collect($this->events)->filter->isDue($app);
    }

    /**
     * Get all of the events on the schedule.
     *
     * 把所有的事件都安排好
     *
     * @return array
     */
    public function events()
    {
        return $this->events;
    }
}
