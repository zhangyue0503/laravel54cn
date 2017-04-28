<?php

namespace Illuminate\Support\Facades;

use Illuminate\Support\Testing\Fakes\EventFake;

/**
 * @see \Illuminate\Events\Dispatcher
 */
class Event extends Facade
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
        //热交换facade底层的实例（伪事件）
        static::swap(new EventFake);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'events';
    }
}
