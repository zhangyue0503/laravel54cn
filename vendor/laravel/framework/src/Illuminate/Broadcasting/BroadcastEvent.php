<?php

namespace Illuminate\Broadcasting;

use ReflectionClass;
use ReflectionProperty;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Broadcasting\Broadcaster;

class BroadcastEvent implements ShouldQueue
{
    use Queueable;

    /**
     * The event instance.
     *
     * 事件实例
     *
     * @var mixed
     */
    public $event;

    /**
     * Create a new job handler instance.
     *
     * 创建一个新的作业处理程序实例
     *
     * @param  mixed  $event
     * @return void
     */
    public function __construct($event)
    {
        $this->event = $event;
    }

    /**
     * Handle the queued job.
     *
     * 处理工作队列
     *
     * @param  \Illuminate\Contracts\Broadcasting\Broadcaster  $broadcaster
     * @return void
     */
    public function handle(Broadcaster $broadcaster)
    {
        $name = method_exists($this->event, 'broadcastAs')
                ? $this->event->broadcastAs() : get_class($this->event);
        //广播给定事件
        $broadcaster->broadcast(
            //    如果给定值不是数组，请将其包在一个数组中(获取该事件应该播放的频道,
            array_wrap($this->event->broadcastOn()), $name,
            $this->getPayloadFromEvent($this->event)//获得给定事件的有效载荷
        );
    }

    /**
     * Get the payload for the given event.
     *
     * 获得给定事件的有效载荷
     *
     * @param  mixed  $event
     * @return array
     */
    protected function getPayloadFromEvent($event)
    {
        if (method_exists($event, 'broadcastWith')) {
            return array_merge(
                //得到的数据应该发送的广播事件
                $event->broadcastWith(), ['socket' => data_get($event, 'socket')]
            );
        }

        $payload = [];

        foreach ((new ReflectionClass($event))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            //                                        对属性的给定值进行格式化
            $payload[$property->getName()] = $this->formatProperty($property->getValue($event));
        }

        return $payload;
    }

    /**
     * Format the given value for a property.
     *
     * 对属性的给定值进行格式化
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function formatProperty($value)
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();//获取数组实例
        }

        return $value;
    }

    /**
     * Get the display name for the queued job.
     *
     * 获取队列作业的显示名称
     *
     * @return string
     */
    public function displayName()
    {
        return get_class($this->event);
    }
}
