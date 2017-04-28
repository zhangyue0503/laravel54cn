<?php

namespace Illuminate\Support\Facades;

use Illuminate\Support\Testing\Fakes\QueueFake;

/**
 * @see \Illuminate\Queue\QueueManager
 * @see \Illuminate\Queue\Queue
 */
class Queue extends Facade
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
        //热交换facade底层的实例(伪队列)
        static::swap(new QueueFake);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'queue';
    }
}
