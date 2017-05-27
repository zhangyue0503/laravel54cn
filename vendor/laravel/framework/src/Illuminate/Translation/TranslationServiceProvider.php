<?php

namespace Illuminate\Translation;

use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * 指示是否延迟了提供者的加载
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
	 *
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
		//  注册翻译行加载器
        $this->registerLoader();
		//         在容器中注册共享绑定
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            //
            // 在注册翻译组件时，我们需要设置默认的语言环境和备用语言环境
            // 因此，我们将获取应用程序配置，这样我们就可以很容易地从其中获得这两个值
            //
            $locale = $app['config']['app.locale'];

            $trans = new Translator($loader, $locale);
            //设置使用的回退地区
            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }

    /**
     * Register the translation line loader.
	 *
	 * 注册翻译行加载器
     *
     * @return void
     */
    protected function registerLoader()
    {
        //在容器中注册共享绑定
        $this->app->singleton('translation.loader', function ($app) {
            return new FileLoader($app['files'], $app['path.lang']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * 获取提供者提供的服务
     *
     * @return array
     */
    public function provides()
    {
        return ['translator', 'translation.loader'];
    }
}
