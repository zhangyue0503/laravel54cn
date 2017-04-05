<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com> 这货是大神呗
 */

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
| 注册自动装载机
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels nice to relax.
|
| Composer提供了方便的，自动生成的类装载器应用。我们只需要利用它！
| 我们只需要将它加入到这里的脚本，以后就不必担心手动去加载我们的类。
| 放松的感觉真好。
|
*/

require __DIR__.'/../bootstrap/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
| 打开灯
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
| 我们需要照亮的PHP开发，所以让我们把灯打开。
| 这个白手起家的框架要准备好使用，那么它将加载该应用程序以便我们能够发送响应返回给浏览器并让我们很爽。
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application
| 运行应用
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
| 一旦我们创建应用程序，我们就可以通过内核处理传入的请求，并将相关的响应发送回客户端浏览器，让他们享受我们为他们准备的创造性和美妙的应用程序。
|
*/

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
