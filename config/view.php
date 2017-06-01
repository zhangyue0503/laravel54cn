<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths 视图存储路径
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. Of course
    | the usual Laravel view path has already been registered for you.
    |
    | 大多数模板系统从磁盘加载模板。在这里，您可以指定应该为视图检查的路径数组。当然，通常的Laravel视图路径已经为您注册了
    |
    */

    'paths' => [
        realpath(base_path('resources/views')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path 编译视图的路径
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    | 此选项决定将为您的应用程序存储所有编译后的刀片模板。通常，这是在存储目录中。但是，像往常一样，您可以自由地更改这个值
    |
    */

    'compiled' => realpath(storage_path('framework/views')),

];
