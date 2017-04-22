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
        return Str::parseCallback($job, 'fire');
    }

    /**
     * Get the resolved name of the queued job class.
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
            return Arr::get($payload, 'data.commandName', $name);
        }

        if ($name === 'Illuminate\Events\CallQueuedHandler@call') {
            return $payload['data']['class'].'@'.$payload['data']['method'];
        }

        return $name;
    }
}
