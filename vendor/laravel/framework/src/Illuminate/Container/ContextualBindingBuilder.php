<?php

namespace Illuminate\Container;

use Illuminate\Contracts\Container\ContextualBindingBuilder as ContextualBindingBuilderContract;

class ContextualBindingBuilder implements ContextualBindingBuilderContract
{
    /**
     * The underlying container instance.
     *
     * 基础容器实例
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The concrete instance.
     *
     * 具体实例
     *
     * @var string
     */
    protected $concrete;

    /**
     * The abstract target.
     *
     * 抽象目标
     *
     * @var string
     */
    protected $needs;

    /**
     * Create a new contextual binding builder.
     *
     * 创建一个新的上下文绑定生成器
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  string  $concrete
     * @return void
     */
    public function __construct(Container $container, $concrete)
    {
        $this->concrete = $concrete;
        $this->container = $container;
    }

    /**
     * Define the abstract target that depends on the context.
     *
     * 定义依赖上下文的抽象目标
     *
     * @param  string  $abstract
     * @return $this
     */
    public function needs($abstract)
    {
        $this->needs = $abstract;

        return $this;
    }

    /**
     * Define the implementation for the contextual binding.
     *
     * 定义上下文绑定的实现
     *
     * @param  \Closure|string  $implementation
     * @return void
     */
    public function give($implementation)
    {
        //向容器添加上下文绑定
        $this->container->addContextualBinding(
            $this->concrete, $this->needs, $implementation
        );
    }
}
