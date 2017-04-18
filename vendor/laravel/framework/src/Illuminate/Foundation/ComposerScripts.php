<?php

namespace Illuminate\Foundation;

use Composer\Script\Event;

class ComposerScripts
{
    /**
     * Handle the post-install Composer event.
     *
     * 处理安装后的Composer事件
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postInstall(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        static::clearCompiled();//清除Laravel的缓存引导文件
    }

    /**
     * Handle the post-update Composer event.
     *
     * 处理升级后的Composer事件
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postUpdate(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        static::clearCompiled();//清除Laravel的缓存引导文件
    }

    /**
     * Clear the cached Laravel bootstrapping files.
     *
     * 清除Laravel的缓存引导文件
     *
     * @return void
     */
    protected static function clearCompiled()
    {
        $laravel = new Application(getcwd());

        if (file_exists($servicesPath = $laravel->getCachedServicesPath())) { //获取缓存目录中services.php文件的路径
            @unlink($servicesPath);
        }
    }
}
