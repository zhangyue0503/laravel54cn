<?php

namespace Illuminate\Support\Facades;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Testing\Fakes\NotificationFake;

/**
 * @see \Illuminate\Notifications\ChannelManager
 */
class Notification extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * 用假的替换绑定实例
     *
     * @return void
     */
    public static function fake()
    {
        //热交换facade底层的实例(伪邮件)
        static::swap(new NotificationFake);
    }

    /**
     * Get the registered name of the component.
     *
     * 获取组件的注册名称
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        //频道管理器
        return ChannelManager::class;
    }
}
