<?php

namespace Illuminate\Console;

use Illuminate\Container\Container;

trait DetectsApplicationNamespace
{
    /**
     * Get the application namespace.
     *
     * 获取应用命名空间
     *
     * @return string
     */
    protected function getAppNamespace()
    {
        //设置容器的全局可用实例->获取应用程序的命名空间
        return Container::getInstance()->getNamespace();
    }
}
