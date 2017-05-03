<?php

namespace Illuminate\Foundation\Testing;

use Exception;

trait WithoutMiddleware
{
    /**
     * Prevent all middleware from being executed for this test class.
     *
     * 为这个测试类防止所有的中间件被执行
     *
     * @throws \Exception
     */
    public function disableMiddlewareForAllTests()
    {
        if (method_exists($this, 'withoutMiddleware')) {
            $this->withoutMiddleware();
        } else {
            throw new Exception('Unable to disable middleware. MakesHttpRequests trait not used.');
        }
    }
}
