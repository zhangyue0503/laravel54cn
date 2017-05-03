<?php

namespace Illuminate\Foundation\Testing\Concerns;

trait InteractsWithContainer
{
    /**
     * Register an instance of an object in the container.
     *
     * 在容器中注册一个对象的实例
     *
     * @param  string  $abstract
     * @param  object  $instance
     * @return object
     */
    protected function swap($abstract, $instance)
    {
        //在容器中注册一个对象的实例
        return $this->instance($abstract, $instance);
    }

    /**
     * Register an instance of an object in the container.
     *
     * 在容器中注册一个对象的实例
     *
     * @param  string  $abstract
     * @param  object  $instance
     * @return object
     */
    protected function instance($abstract, $instance)
    {
        //在容器中注册一个对象的实例
        $this->app->instance($abstract, $instance);

        return $instance;
    }
}
