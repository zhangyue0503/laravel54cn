<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\View\Compilers\BladeCompiler
 */
class Blade extends Facade
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
        //                            获取引擎解析器实例   按名称命名引擎实例    获取编译器实现
        return static::$app['view']->getEngineResolver()->resolve('blade')->getCompiler();
    }
}
