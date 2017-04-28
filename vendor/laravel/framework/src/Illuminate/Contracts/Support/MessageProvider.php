<?php

namespace Illuminate\Contracts\Support;
//消息提供者
interface MessageProvider
{
    /**
     * Get the messages for the instance.
     *
     * 从实例中获取消息
     *
     * @return \Illuminate\Contracts\Support\MessageBag
     */
    public function getMessageBag();
}
