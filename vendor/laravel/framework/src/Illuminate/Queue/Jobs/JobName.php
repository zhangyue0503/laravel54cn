<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class JobName
{
    /**
     * Parse the given job name into a class / method array.
	 *
	 * 将给定的作业名称解析成类/方法数组
     *
     * @param  string  $job
     * @return array
     */
    public static function parse($job)
    {
        //          解析 类@方法 类型回调到类和方法
        return Str::parseCallback($job, 'fire');
    }

    /**
     * Get the resolved name of the queued job class.
     *
     * 获取队列作业类的解析名称
     *
     * @param  string  $name
     * @param  array  $payload
     * @return string
     */
    public static function resolve($name, $payload)
    {
        if (! empty($payload['displayName'])) {
            return $payload['displayName'];
        }

        if ($name === 'Illuminate\Queue\CallQueuedHandler@call') {
            //      使用“点”符号从数组中获取一个项
            return Arr::get($payload, 'data.commandName', $name);
        }

        if ($name === 'Illuminate\Events\CallQueuedHandler@call') {
            return $payload['data']['class'].'@'.$payload['data']['method'];
        }

        return $name;
    }
}
