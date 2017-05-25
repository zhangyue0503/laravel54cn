<?php

namespace Illuminate\Queue;

use Carbon\Carbon;
use DateTimeInterface;

trait InteractsWithTime
{
    /**
     * Get the number of seconds until the given DateTime.
     *
     * 在给定的DateTime之前获得秒数
     *
     * @param  \DateTimeInterface  $delay
     * @return int
     */
    protected function secondsUntil($delay)
    {
        return $delay instanceof DateTimeInterface
        //                                                    将当前系统时间作为UNIX时间戳
                            ? max(0, $delay->getTimestamp() - $this->currentTime())
                            : (int) $delay;
    }

    /**
     * Get the "available at" UNIX timestamp.
	 *
	 * 获取“available at”UNIX时间戳
     *
     * @param  \DateTimeInterface|int  $delay
     * @return int
     */
    protected function availableAt($delay = 0)
    {
        return $delay instanceof DateTimeInterface
                            ? $delay->getTimestamp()
            //         获取当前日期和时间的Carbon实例 在实例中添加秒
                            : Carbon::now()->addSeconds($delay)->getTimestamp();
    }

    /**
     * Get the current system time as a UNIX timestamp.
     *
     * 将当前系统时间作为UNIX时间戳
     *
     * @return int
     */
    protected function currentTime()
    {
        //  获取当前日期和时间的Carbon实例
        return Carbon::now()->getTimestamp();
    }
}
