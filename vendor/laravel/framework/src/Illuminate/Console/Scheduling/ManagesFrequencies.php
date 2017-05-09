<?php

namespace Illuminate\Console\Scheduling;

use Carbon\Carbon;

trait ManagesFrequencies
{
    /**
     * The Cron expression representing the event's frequency.
     *
     * Cron的表达方式代表了事件的频率
     *
     * @param  string  $expression
     * @return $this
     */
    public function cron($expression)
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Schedule the event to run between start and end time.
     *
     * 将事件安排在开始和结束之间运行
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return $this
     */
    public function between($startTime, $endTime)
    {
        //注册一个回调以进一步筛选调度(将事件安排在开始和结束之间运行)
        return $this->when($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * Schedule the event to not run between start and end time.
     *
     * 将事件安排在开始和结束时间之间不运行
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return $this
     */
    public function unlessBetween($startTime, $endTime)
    {
        //注册一个回调以进一步筛选调度(将事件安排在开始和结束之间运行)
        return $this->skip($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * Schedule the event to run between start and end time.
     *
     * 将事件安排在开始和结束之间运行
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return \Closure
     */
    private function inTimeInterval($startTime, $endTime)
    {
        return function () use ($startTime, $endTime) {
            //获取当前日期和时间的Carbon实例
            $now = Carbon::now()->getTimestamp();

            return $now >= strtotime($startTime) && $now <= strtotime($endTime);
        };
    }

    /**
     * Schedule the event to run hourly.
     *
     * 将事件安排为每小时运行一次
     *
     * @return $this
     */
    public function hourly()
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(1, 0);
    }

    /**
     * Schedule the event to run hourly at a given offset in the hour.
     *
     * 将事件安排在每小时的特定偏移量下运行
     *
     * @param  int  $offset
     * @return $this
     */
    public function hourlyAt($offset)
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(1, $offset);
    }

    /**
     * Schedule the event to run daily.
     *
     * 安排每天的活动
     *
     * @return $this
     */
    public function daily()
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0);
    }

    /**
     * Schedule the command at a given time.
     *
     * 在指定的时间调度命令
     *
     * @param  string  $time
     * @return $this
     */
    public function at($time)
    {
        //调度事件运行每日在给定的时间
        return $this->dailyAt($time);
    }

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
     *
     * 调度事件运行每日在给定的时间
     *
     * @param  string  $time
     * @return $this
     */
    public function dailyAt($time)
    {
        $segments = explode(':', $time);
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(2, (int) $segments[0])
                    ->spliceIntoPosition(1, count($segments) == 2 ? (int) $segments[1] : '0');
    }

    /**
     * Schedule the event to run twice daily.
     *
     * 安排每天两次的活动
     *
     * @param  int  $first
     * @param  int  $second
     * @return $this
     */
    public function twiceDaily($first = 1, $second = 13)
    {
        $hours = $first.','.$second;
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, $hours);
    }

    /**
     * Schedule the event to run only on weekdays.
     *
     * 安排活动只在工作日运行
     *
     * @return $this
     */
    public function weekdays()
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(5, '1-5');
    }

    /**
     * Schedule the event to run only on weekends.
     *
     * 安排活动只在周末进行
     *
     * @return $this
     */
    public function weekends()
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(5, '0,6');
    }

    /**
     * Schedule the event to run only on Mondays.
     *
     * 把活动安排在周一进行
     *
     * @return $this
     */
    public function mondays()
    {
        //设置命令应该运行的一周的时间
        return $this->days(1);
    }

    /**
     * Schedule the event to run only on Tuesdays.
     *
     * 安排活动只在周二进行
     *
     * @return $this
     */
    public function tuesdays()
    {
        //设置命令应该运行的一周的时间
        return $this->days(2);
    }

    /**
     * Schedule the event to run only on Wednesdays.
     *
     * 安排活动只在周三进行
     *
     * @return $this
     */
    public function wednesdays()
    {
        //设置命令应该运行的一周的时间
        return $this->days(3);
    }

    /**
     * Schedule the event to run only on Thursdays.
     *
     * 安排活动只在周四进行
     *
     * @return $this
     */
    public function thursdays()
    {
        //设置命令应该运行的一周的时间
        return $this->days(4);
    }

    /**
     * Schedule the event to run only on Fridays.
     *
     * 安排活动只在周五进行
     *
     * @return $this
     */
    public function fridays()
    {
        //设置命令应该运行的一周的时间
        return $this->days(5);
    }

    /**
     * Schedule the event to run only on Saturdays.
     *
     * 安排活动只在周六进行
     *
     * @return $this
     */
    public function saturdays()
    {
        //设置命令应该运行的一周的时间
        return $this->days(6);
    }

    /**
     * Schedule the event to run only on Sundays.
     *
     * 安排活动只在周日进行
     *
     * @return $this
     */
    public function sundays()
    {
        //设置命令应该运行的一周的时间
        return $this->days(0);
    }

    /**
     * Schedule the event to run weekly.
     *
     * 计划每周运行一次
     *
     * @return $this
     */
    public function weekly()
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(5, 0);
    }

    /**
     * Schedule the event to run weekly on a given day and time.
     *
     * 在给定的时间和时间安排每周的活动
     *
     * @param  int  $day
     * @param  string  $time
     * @return $this
     */
    public function weeklyOn($day, $time = '0:0')
    {
        //调度事件运行每日在给定的时间
        $this->dailyAt($time);
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(5, $day);
    }

    /**
     * Schedule the event to run monthly.
     *
     * 将事件安排为每月运行一次
     *
     * @return $this
     */
    public function monthly()
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(3, 1);
    }

    /**
     * Schedule the event to run monthly on a given day and time.
     *
     * 在给定的时间和时间安排每个月的活动
     *
     * @param  int  $day
     * @param  string  $time
     * @return $this
     */
    public function monthlyOn($day = 1, $time = '0:0')
    {
        //调度事件运行每日在给定的时间
        $this->dailyAt($time);
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(3, $day);
    }

    /**
     * Schedule the event to run quarterly.
     *
     * 将事件安排为每季度运行一次
     *
     * @return $this
     */
    public function quarterly()
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(3, 1)
                    ->spliceIntoPosition(4, '*/3');
    }

    /**
     * Schedule the event to run yearly.
     *
     * 将事件安排为每年运行一次
     *
     * @return $this
     */
    public function yearly()
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(1, 0)
                    ->spliceIntoPosition(2, 0)
                    ->spliceIntoPosition(3, 1)
                    ->spliceIntoPosition(4, 1);
    }

    /**
     * Schedule the event to run every minute.
     *
     * 将事件安排为每分钟运行一次
     *
     * @return $this
     */
    public function everyMinute()
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(1, '*');
    }

    /**
     * Schedule the event to run every five minutes.
     *
     * 将事件安排为每5分钟运行一次
     *
     * @return $this
     */
    public function everyFiveMinutes()
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(1, '*/5');
    }

    /**
     * Schedule the event to run every ten minutes.
     *
     * 将事件安排在每10分钟运行一次
     *
     * @return $this
     */
    public function everyTenMinutes()
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(1, '*/10');
    }

    /**
     * Schedule the event to run every thirty minutes.
     *
     * 每30分钟安排一次活动
     *
     * @return $this
     */
    public function everyThirtyMinutes()
    {
        //将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(1, '0,30');
    }

    /**
     * Set the days of the week the command should run on.
     *
     * 设置命令应该运行的一周的时间
     *
     * @param  array|mixed  $days
     * @return $this
     */
    public function days($days)
    {
        $days = is_array($days) ? $days : func_get_args();
        //         将给定的值拼接到表达式的给定位置
        return $this->spliceIntoPosition(5, implode(',', $days));
    }

    /**
     * Set the timezone the date should be evaluated on.
     *
     * 设置时区，应该对日期进行评估
     *
     * @param  \DateTimeZone|string  $timezone
     * @return $this
     */
    public function timezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Splice the given value into the given position of the expression.
     *
     * 将给定的值拼接到表达式的给定位置
     *
     * @param  int  $position
     * @param  string  $value
     * @return $this
     */
    protected function spliceIntoPosition($position, $value)
    {
        $segments = explode(' ', $this->expression);

        $segments[$position - 1] = $value;
        //Cron的表达方式代表了事件的频率
        return $this->cron(implode(' ', $segments));
    }
}
