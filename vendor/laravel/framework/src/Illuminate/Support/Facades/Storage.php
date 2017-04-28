<?php

namespace Illuminate\Support\Facades;

use Illuminate\Filesystem\Filesystem;

/**
 * @see \Illuminate\Filesystem\FilesystemManager
 */
class Storage extends Facade
{
    /**
     * Replace the given disk with a local, testing disk.
     *
     * 用本地磁盘替换测试磁盘
     *
     * @param  string  $disk
     * @return void
     */
    public static function fake($disk)
    {
        (new Filesystem)->cleanDirectory(//清空所有文件和文件夹的指定目录
            $root = storage_path('framework/testing/disks/'.$disk)
        );
        //设置给定的磁盘实例        创建本地驱动的实例
        static::set($disk, self::createLocalDriver(['root' => $root]));
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'filesystem';
    }
}
