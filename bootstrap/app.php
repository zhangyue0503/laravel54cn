<?php

/*
|--------------------------------------------------------------------------
| Create The Application
| 创建应用
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
| 我们首先要做的是创建一个新的laravel应用实例作为“胶水”用于laravel的所有组件，并为系统结合各种零件的IOC容器。
|
*/

$app = new Illuminate\Foundation\Application(
    realpath(__DIR__.'/../')
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
| 绑定重要的接口
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
| 接下来，我们需要把一些重要的接口接入容器，这样我们会在需要的时候能够解析他们。内核服务将传入的请求从Web和CLI发送到这个应用程序。
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
| 反回应用
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
| 这个脚本返回的应用实例。该实例给出了调用脚本，因此我们可以将实例的构建与应用程序的实际运行分开并发送响应。
|
*/

return $app;
