<?php

namespace Illuminate\Notifications;

use Illuminate\Support\Str;
use Illuminate\Contracts\Notifications\Dispatcher;

trait RoutesNotifications
{
    /**
     * Send the given notification.
     *
     * 发送给定的通知
     *
     * @param  mixed  $instance
     * @return void
     */
    public function notify($instance)
    {
        ////调度程序              将给定的通知发送给指定的通知实体
        app(Dispatcher::class)->send($this, $instance);
    }

    /**
     * Get the notification routing information for the given driver.
     *
     * 获取给定驱动程序的通知路由信息
     *
     * @param  string  $driver
     * @return mixed
     */
    public function routeNotificationFor($driver)
    {
        //                                                      将值转换为大驼峰
        if (method_exists($this, $method = 'routeNotificationFor'.Str::studly($driver))) {
            return $this->{$method}();
        }

        switch ($driver) {
            case 'database':
                return $this->notifications();//获取实体的通知
            case 'mail':
                return $this->email;
            case 'nexmo':
                return $this->phone_number;
        }
    }
}
