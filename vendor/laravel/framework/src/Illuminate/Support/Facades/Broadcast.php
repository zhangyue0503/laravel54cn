<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactoryContract;

/**
 * @see \Illuminate\Contracts\Broadcasting\Factory
 */
class Broadcast extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * 获取组件的注册名称
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        //Illuminate\Contracts\Broadcasting\Factory
        return BroadcastingFactoryContract::class;
    }
}
