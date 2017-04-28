<?php

namespace Illuminate\Support\Traits;

use Illuminate\Support\Fluent;
use Illuminate\Contracts\Container\Container;
//胶囊管理器（小容器）
trait CapsuleManagerTrait
{
    /**
     * The current globally used instance.
     *
     * 当前全局使用的实例
     *
     * @var object
     */
    protected static $instance;

    /**
     * The container instance.
     *
     * 容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Setup the IoC container instance.
     *
     * 设置IoC容器实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    protected function setupContainer(Container $container)
    {
        $this->container = $container;
        //确定给定的抽象类型是否已绑定
        if (! $this->container->bound('config')) {
            //在容器中注册一个已存在的实例(,创建一个新的流容器实例)
            $this->container->instance('config', new Fluent);
        }
    }

    /**
     * Make this capsule instance available globally.
     *
     * 让这个胶囊实例可以在全局范围内使用
     *
     * @return void
     */
    public function setAsGlobal()
    {
        static::$instance = $this;
    }

    /**
     * Get the IoC container instance.
     *
     * 获取IoC容器实例
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the IoC container instance.
     *
     * 设置IoC容器实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}
