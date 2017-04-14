<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * 应用程序的策略映射
     *
     * @var array
     */
    protected $policies = [];

    /**
     * Register the application's policies.
     *
     * 注册应用程序的策略
     *
     * @return void
     */
    public function registerPolicies()
    {
        foreach ($this->policies as $key => $value) {
            Gate::policy($key, $value); //为给定类类型定义策略类 Illuminate\Auth\Access\Gate::policy()
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        //
    }
}
