<?php

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Composer Auto Loader
| 注册Composer的自动加载
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
| Composer提供了方便的，自动生成应用程序的类加载器。我们只需要利用它！
| 我们需要将它的脚本放在这里，这样我们就不必担心任何类需要我们“手动”加载。
| 放松心情好极了。
| **** http://docs.phpcomposer.com/ 还没用过composer的可以学习下 ****
|
*/

require __DIR__.'/../vendor/autoload.php';
