<?php

namespace Illuminate\Contracts\Notifications;
//调度程序
interface Dispatcher
{
    /**
     * Send the given notification to the given notifiable entities.
     *
     * 将给定的通知发送给指定的通知实体
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification);

    /**
     * Send the given notification immediately.
     *
     * 立即发送通知
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function sendNow($notifiables, $notification);
}
