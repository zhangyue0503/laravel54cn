<?php

namespace Illuminate\View;

use Illuminate\View\Engines\PhpEngine;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerFactory();//注册视图环境

        $this->registerViewFinder();//注册视图查找器实现

        $this->registerEngineResolver();//注册引擎解析器实例
    }

    /**
     * Register the view environment.
     *
     * 注册视图环境
     *
     * @return void
     */
    public function registerFactory()
    {
        //在容器中注册共享绑定
        $this->app->singleton('view', function ($app) {
            // Next we need to grab the engine resolver instance that will be used by the
            // environment. The resolver will be used by an environment to get each of
            // the various engine implementations such as plain PHP or Blade engine.
            //
            // 接下来，我们需要获取将要被环境使用的引擎解析器实例。这个解析器将被一个环境用来获取各种引擎实现，例如普通的PHP或Blade引擎
            //
            $resolver = $app['view.engine.resolver'];

            $finder = $app['view.finder'];

            $env = new Factory($resolver, $finder, $app['events']);

            // We will also set the container instance on this view environment since the
            // view composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            //
            // 我们还将在这个视图环境中设置容器实例，因为视图的composers可能是在容器中注册的类，这为应用程序开发人员提供了可测试的、灵活的composers
            //
            //      设置IoC容器实例
            $env->setContainer($app);
            //向环境中添加一段共享数据
            $env->share('app', $app);

            return $env;
        });
    }

    /**
     * Register the view finder implementation.
     *
     * 注册视图查找器实现
     *
     * @return void
     */
    public function registerViewFinder()
    {
        //向容器注册一个绑定
        $this->app->bind('view.finder', function ($app) {
            return new FileViewFinder($app['files'], $app['config']['view.paths']);
        });
    }

    /**
     * Register the engine resolver instance.
     *
     * 注册引擎解析器实例
     *
     * @return void
     */
    public function registerEngineResolver()
    {
        //在容器中注册共享绑定
        $this->app->singleton('view.engine.resolver', function () {
            $resolver = new EngineResolver;

            // Next, we will register the various view engines with the resolver so that the
            // environment will resolve the engines needed for various views based on the
            // extension of view file. We call a method for each of the view's engines.
            //
            // 接下来，我们将用解析器注册各种视图引擎，这样环境就可以根据视图文件的扩展来解析各种视图所需的引擎
            // 我们为每个视图的引擎调用一个方法
            //
            foreach (['file', 'php', 'blade'] as $engine) {
                $this->{'register'.ucfirst($engine).'Engine'}($resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the file engine implementation.
     *
     * 注册文件引擎实现
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerFileEngine($resolver)
    {
        //注册一个新的引擎解析器
        $resolver->register('file', function () {
            return new FileEngine;
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * 注册PHP引擎实现
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerPhpEngine($resolver)
    {
        //注册一个新的引擎解析器
        $resolver->register('php', function () {
            return new PhpEngine;
        });
    }

    /**
     * Register the Blade engine implementation.
     *
     * 注册Blade引擎实现
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerBladeEngine($resolver)
    {
        // The Compiler engine requires an instance of the CompilerInterface, which in
        // this case will be the Blade compiler, so we'll first create the compiler
        // instance to pass into the engine so it can compile the views properly.
        //
        // 编译器引擎需要一个编译器接口的实例，在本例中是刀片编译器，因此我们首先创建编译器实例，以便将其传递到引擎，以便正确地编译视图
        //
        //        在容器中注册共享绑定
        $this->app->singleton('blade.compiler', function () {
            return new BladeCompiler(
                $this->app['files'], $this->app['config']['view.compiled']
            );
        });
        //注册一个新的引擎解析器
        $resolver->register('blade', function () {
            return new CompilerEngine($this->app['blade.compiler']);
        });
    }
}
