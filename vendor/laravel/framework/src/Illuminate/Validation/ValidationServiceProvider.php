<?php

namespace Illuminate\Validation;

use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
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
        //注册数据库存在验证器
        $this->registerPresenceVerifier();
        //注册验证工厂
        $this->registerValidationFactory();
    }

    /**
     * Register the validation factory.
	 *
	 * 注册验证工厂
     *
     * @return void
     */
    protected function registerValidationFactory()
    {
		//在容器中注册共享绑定
        $this->app->singleton('validator', function ($app) {
			//创建一个新的验证工厂实例
            $validator = new Factory($app['translator'], $app);

            // The validation presence verifier is responsible for determining the existence of
            // values in a given data collection which is typically a relational database or
            // other persistent data stores. It is used to check for "uniqueness" as well.
            //
            // 验证存在验证器负责确定给定数据集合中的值的存在，这些数据集合通常是关系数据库或其他持久性数据存储
            // 它也被用来检查“唯一性”
            //
            if (isset($app['db']) && isset($app['validation.presence'])) {
                //          设置实现验证器的实现
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $validator;
        });
    }

    /**
     * Register the database presence verifier.
     *
     * 注册数据库存在验证器
     *
     * @return void
     */
    protected function registerPresenceVerifier()
    {
        // 在容器中注册共享绑定
        $this->app->singleton('validation.presence', function ($app) {
            return new DatabasePresenceVerifier($app['db']);
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
        return [
            'validator', 'validation.presence',
        ];
    }
}
