<?php

namespace Illuminate\Foundation\Bus;
//可分派
trait Dispatchable
{
    /**
     * Dispatch the job with the given arguments.
     *
     * 用给定的参数分派作业
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public static function dispatch()
    {
        //             创建一个新的待处理的作业
        return new PendingDispatch(new static(...func_get_args()));
    }
}
