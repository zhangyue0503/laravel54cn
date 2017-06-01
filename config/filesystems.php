<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk 默认文件系统磁盘
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    | 在这里，您可以指定应该由框架使用的默认文件系统磁盘。您的应用程序可以使用“本地”磁盘以及各种基于云的磁盘。只是储存
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk 默认的云文件系统磁盘
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    | 许多应用程序将文件存储在本地和云中。出于这个原因，您可以在这里指定默认的“云”驱动程序。这个驱动程序将被绑定为容器中的云磁盘实现
    |
    */

    'cloud' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks 文件系统磁盘
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | 在这里，您可以像您希望的那样配置多个文件系统“磁盘”，甚至可以配置同一个驱动程序的多个磁盘。每个驱动程序的默认设置为所需选项的示例
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_KEY'),
            'secret' => env('AWS_SECRET'),
            'region' => env('AWS_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],

    ],

];
