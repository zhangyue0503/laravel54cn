<?php

namespace Illuminate\Foundation\Testing;

use Exception;

trait WithoutEvents
{
    /**
     * Prevent all event handles from being executed.
     *
     * 防止所有事件句柄被执行
     *
     * @throws \Exception
     */
    public function disableEventsForAllTests()
    {
        if (method_exists($this, 'withoutEvents')) {
            $this->withoutEvents();
        } else {
            throw new Exception('Unable to disable middleware. ApplicationTrait not used.');
        }
    }
}
