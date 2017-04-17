<?php

namespace App\ServiceTest;

use Illuminate\Support\ServiceProvider;

/**
 * Class TestServiceProvider
 *
 * Lavarl框架关键技术解析，第8章
 *
 * @package App\ServiceTest
 */
class TestServiceProvider extends ServiceProvider
{
	protected $defer = true;
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //


    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
		$this->app->bind(ServiceContract::class, GeneralService::class);
		$this->app->bind(GeneralService::class, GeneralService::class);
		$instance = new InstanceService();
		$this->app->instance('instanceService', $instance);
    }

    //返回缓载服务提供者所绑定服务的名称
    public function provides()
	{
		return [
			ServiceContract::class,
			GeneralService::class,
			'instanceService'
		];
	}
}
