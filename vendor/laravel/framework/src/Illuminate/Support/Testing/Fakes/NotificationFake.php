<?php

namespace Illuminate\Support\Testing\Fakes;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Collection;
use PHPUnit_Framework_Assert as PHPUnit;
use Illuminate\Contracts\Notifications\Factory as NotificationFactory;
//伪通知
class NotificationFake implements NotificationFactory
{
    /**
     * All of the notifications that have been sent.
     *
     * 所有已发送的通知
     *
     * @var array
     */
    protected $notifications = [];

    /**
     * Assert if a notification was sent based on a truth-test callback.
     *
     * 断言如果一个通知是基于真实测试回调而被分派的
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @param  callable|null  $callback
     * @return void
     */
    public function assertSentTo($notifiable, $notification, $callback = null)
    {
        if (is_array($notifiable) || $notifiable instanceof Collection) {
            foreach ($notifiable as $singleNotifiable) {
                $this->assertSentTo($singleNotifiable, $notification, $callback);//断言如果一个通知是基于真实测试回调而被分派的
            }

            return;
        }

        PHPUnit::assertTrue(
            //获取匹配一个真实测试回调的所有通知->计数集合中的项目数
            $this->sent($notifiable, $notification, $callback)->count() > 0,
            "The expected [{$notification}] notification was not sent."
        );
    }

    /**
     * Determine if a notification was sent based on a truth-test callback.
     *
     * 确定一个通知是否基于真实测试的回调
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @param  callable|null  $callback
     * @return void
     */
    public function assertNotSentTo($notifiable, $notification, $callback = null)
    {
        if (is_array($notifiable) || $notifiable instanceof Collection) {
            foreach ($notifiable as $singleNotifiable) {
                $this->assertNotSentTo($singleNotifiable, $notification, $callback);//确定一个通知是否基于真实测试的回调
            }

            return;
        }

        PHPUnit::assertTrue(
            //获取匹配一个真实测试回调的所有通知->计数集合中的项目数
            $this->sent($notifiable, $notification, $callback)->count() === 0,
            "The unexpected [{$notification}] notification was sent."
        );
    }

    /**
     * Get all of the notifications matching a truth-test callback.
     *
     * 获取匹配一个真实测试回调的所有通知
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function sent($notifiable, $notification, $callback = null)
    {
        if (! $this->hasSent($notifiable, $notification)) { //确定是否还有更多的通知需要检查
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        $notifications = collect($this->notificationsFor($notifiable, $notification));//按类型获取一个通知实体的所有通知
        //                    在每个项目上运行过滤器
        return $notifications->filter(function ($arguments) use ($callback) {
            return $callback(...array_values($arguments));
        })->pluck('notification');//获取给定键的值
    }

    /**
     * Determine if there are more notifications left to inspect.
     *
     * 确定是否还有更多的通知需要检查
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @return bool
     */
    public function hasSent($notifiable, $notification)
    {
        return ! empty($this->notificationsFor($notifiable, $notification));//按类型获取一个通知实体的所有通知
    }

    /**
     * Get all of the notifications for a notifiable entity by type.
     *
     * 按类型获取一个通知实体的所有通知
     *
     * @param  mixed  $notifiable
     * @param  string  $notification
     * @return array
     */
    protected function notificationsFor($notifiable, $notification)
    {
        if (isset($this->notifications[get_class($notifiable)][$notifiable->getKey()][$notification])) {
            return $this->notifications[get_class($notifiable)][$notifiable->getKey()][$notification];
        }

        return [];
    }

    /**
     * Send the given notification to the given notifiable entities.
     *
     * 将给定的通知发送给指定的通知实体
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification)
    {
        return $this->sendNow($notifiables, $notification);//立即发送通知
    }

    /**
     * Send the given notification immediately.
     *
     * 立即发送通知
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function sendNow($notifiables, $notification)
    {
        if (! $notifiables instanceof Collection && ! is_array($notifiables)) {
            $notifiables = [$notifiables];
        }

        foreach ($notifiables as $notifiable) {
            $notification->id = Uuid::uuid4()->toString();

            $this->notifications[get_class($notifiable)][$notifiable->getKey()][get_class($notification)][] = [
                'notification' => $notification,
                'channels' => $notification->via($notifiable),
            ];
        }
    }

    /**
     * Get a channel instance by name.
     *
     * 通过名称获取一个频道实例
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function channel($name = null)
    {
        //
    }
}
