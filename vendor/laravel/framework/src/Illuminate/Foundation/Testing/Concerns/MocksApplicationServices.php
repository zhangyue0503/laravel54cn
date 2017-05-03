<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Mockery;
use Exception;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcherContract;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcherContract;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;

trait MocksApplicationServices
{
    /**
     * All of the fired events.
     *
     * 所有的调用事件
     *
     * @var array
     */
    protected $firedEvents = [];

    /**
     * All of the fired model events.
     *
     * 所有的调用模型事件
     *
     * @var array
     */
    protected $firedModelEvents = [];

    /**
     * All of the dispatched jobs.
     *
     * 所有的调度任务
     *
     * @var array
     */
    protected $dispatchedJobs = [];

    /**
     * All of the dispatched notifications.
     *
     * 所有的调度通知
     *
     * @var array
     */
    protected $dispatchedNotifications = [];

    /**
     * Specify a list of events that should be fired for the given operation.
     *
     * 指定应该为给定操作而触发的事件列表
     *
     * These events will be mocked, so that handlers will not actually be executed.
     *
     * 这些事件将被模拟，这样处理程序就不会被执行
     *
     * @param  array|string  $events
     * @return $this
     *
     * @throws \Exception
     */
    public function expectsEvents($events)
    {
        $events = is_array($events) ? $events : func_get_args();

        $this->withoutEvents();//模拟事件调度程序，使所有事件都保持沉默和收集
        //注册一个回调，在应用程序被销毁之前运行
        $this->beforeApplicationDestroyed(function () use ($events) {
            $fired = $this->getFiredEvents($events);//根据触发事件对给定事件进行筛选

            if ($eventsNotFired = array_diff($events, $fired)) {
                throw new Exception(
                    'These expected events were not fired: ['.implode(', ', $eventsNotFired).']'
                );
            }
        });

        return $this;
    }

    /**
     * Specify a list of events that should not be fired for the given operation.
     *
     * 指定不应该为给定操作而触发的事件列表
     *
     * These events will be mocked, so that handlers will not actually be executed.
     *
     * 这些事件将被模拟，这样处理程序就不会被执行
     *
     * @param  array|string  $events
     * @return $this
     */
    public function doesntExpectEvents($events)
    {
        $events = is_array($events) ? $events : func_get_args();

        $this->withoutEvents();//模拟事件调度程序，使所有事件都保持沉默和收集
        //注册一个回调，在应用程序被销毁之前运行
        $this->beforeApplicationDestroyed(function () use ($events) {
            //             根据触发事件对给定事件进行筛选
            if ($fired = $this->getFiredEvents($events)) {
                throw new Exception(
                    'These unexpected events were fired: ['.implode(', ', $fired).']'
                );
            }
        });

        return $this;
    }

    /**
     * Mock the event dispatcher so all events are silenced and collected.
     *
     * 模拟事件调度程序，使所有事件都保持沉默和收集
     *
     * @return $this
     */
    protected function withoutEvents()
    {
        $mock = Mockery::mock(EventsDispatcherContract::class);

        $mock->shouldReceive('fire', 'dispatch')->andReturnUsing(function ($called) {
            $this->firedEvents[] = $called;
        });
        //在容器中注册一个已存在的实例
        $this->app->instance('events', $mock);

        return $this;
    }

    /**
     * Filter the given events against the fired events.
     *
     * 根据触发事件对给定事件进行筛选
     *
     * @param  array  $events
     * @return array
     */
    protected function getFiredEvents(array $events)
    {
        //对给定的类进行筛选，以避免被分派的类
        return $this->getDispatched($events, $this->firedEvents);
    }

    /**
     * Specify a list of jobs that should be dispatched for the given operation.
     *
     * 指定应该为给定操作发送的作业的列表
     *
     * These jobs will be mocked, so that handlers will not actually be executed.
     *
     * 这些作业将会被模拟，这样处理程序就不会被执行
     *
     * @param  array|string  $jobs
     * @return $this
     */
    protected function expectsJobs($jobs)
    {
        $jobs = is_array($jobs) ? $jobs : func_get_args();

        $this->withoutJobs();//模拟事件调度程序，使所有事件都保持沉默和收集
        //注册一个回调，在应用程序被销毁之前运行
        $this->beforeApplicationDestroyed(function () use ($jobs) {
            $dispatched = $this->getDispatchedJobs($jobs);//根据分派的任务筛选给定的作业

            if ($jobsNotDispatched = array_diff($jobs, $dispatched)) {
                throw new Exception(
                    'These expected jobs were not dispatched: ['.implode(', ', $jobsNotDispatched).']'
                );
            }
        });

        return $this;
    }

    /**
     * Specify a list of jobs that should not be dispatched for the given operation.
     *
     * 指定在给定操作中不应该发送的作业列表
     *
     * These jobs will be mocked, so that handlers will not actually be executed.
     *
     * 这些作业将会被模拟，这样处理程序就不会被执行
     *
     * @param  array|string  $jobs
     * @return $this
     */
    protected function doesntExpectJobs($jobs)
    {
        $jobs = is_array($jobs) ? $jobs : func_get_args();

        $this->withoutJobs();//模拟事件调度程序，使所有事件都保持沉默和收集
        //注册一个回调，在应用程序被销毁之前运行
        $this->beforeApplicationDestroyed(function () use ($jobs) {
            //根据分派的任务筛选给定的作业
            if ($dispatched = $this->getDispatchedJobs($jobs)) {
                throw new Exception(
                    'These unexpected jobs were dispatched: ['.implode(', ', $dispatched).']'
                );
            }
        });

        return $this;
    }

    /**
     * Mock the job dispatcher so all jobs are silenced and collected.
     *
     * 模拟作业调度器，使所有作业都保持沉默和收集
     *
     * @return $this
     */
    protected function withoutJobs()
    {
        $mock = Mockery::mock(BusDispatcherContract::class);

        $mock->shouldReceive('dispatch', 'dispatchNow')->andReturnUsing(function ($dispatched) {
            $this->dispatchedJobs[] = $dispatched;
        });
        //在容器中注册一个已存在的实例
        $this->app->instance(
            BusDispatcherContract::class, $mock
        );

        return $this;
    }

    /**
     * Filter the given jobs against the dispatched jobs.
     *
     * 根据分派的任务筛选给定的作业
     *
     * @param  array  $jobs
     * @return array
     */
    protected function getDispatchedJobs(array $jobs)
    {
        //对给定的类进行筛选，以避免被分派的类
        return $this->getDispatched($jobs, $this->dispatchedJobs);
    }

    /**
     * Filter the given classes against an array of dispatched classes.
     *
     * 对给定的类进行筛选，以避免被分派的类
     *
     * @param  array  $classes
     * @param  array  $dispatched
     * @return array
     */
    protected function getDispatched(array $classes, array $dispatched)
    {
        return array_filter($classes, function ($class) use ($dispatched) {
            return $this->wasDispatched($class, $dispatched);//检查给定的类是否存在于已调度的类数组中
        });
    }

    /**
     * Check if the given class exists in an array of dispatched classes.
     *
     * 检查给定的类是否存在于已调度的类数组中
     *
     * @param  string  $needle
     * @param  array  $haystack
     * @return bool
     */
    protected function wasDispatched($needle, array $haystack)
    {
        foreach ($haystack as $dispatched) {
            if ((is_string($dispatched) && ($dispatched === $needle || is_subclass_of($dispatched, $needle))) ||
                $dispatched instanceof $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mock the notification dispatcher so all notifications are silenced.
     *
     * 模拟通知调度程序，使所有通知都保持沉默
     *
     * @return $this
     */
    protected function withoutNotifications()
    {
        $mock = Mockery::mock(NotificationDispatcher::class);

        $mock->shouldReceive('send')->andReturnUsing(function ($notifiable, $instance, $channels = []) {
            $this->dispatchedNotifications[] = compact(
                'notifiable', 'instance', 'channels'
            );
        });
        //在容器中注册一个已存在的实例
        $this->app->instance(NotificationDispatcher::class, $mock);

        return $this;
    }

    /**
     * Specify a notification that is expected to be dispatched.
     *
     * 指定要发送的通知
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @return $this
     */
    protected function expectsNotification($notifiable, $notification)
    {
        $this->withoutNotifications();//模拟通知调度程序，使所有通知都保持沉默
        //注册一个回调，在应用程序被销毁之前运行
        $this->beforeApplicationDestroyed(function () use ($notifiable, $notification) {
            foreach ($this->dispatchedNotifications as $dispatched) {
                $notified = $dispatched['notifiable'];

                if (($notified === $notifiable ||
                     $notified->getKey() == $notifiable->getKey()) &&
                    get_class($dispatched['instance']) === $notification
                ) {
                    return $this;
                }
            }

            throw new Exception(
                'The following expected notification were not dispatched: ['.$notification.']'
            );
        });

        return $this;
    }
}
