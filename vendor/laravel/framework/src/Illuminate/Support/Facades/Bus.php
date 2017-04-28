<?php

namespace Illuminate\Support\Facades;

use Illuminate\Support\Testing\Fakes\BusFake;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcherContract;

/**
 * @see \Illuminate\Contracts\Bus\Dispatcher
 */
class Bus extends Facade
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
        //热交换facade底层的实例（伪总线）
        static::swap(new BusFake);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BusDispatcherContract::class;
    }
}
