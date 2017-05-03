<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Support\AggregateServiceProvider;

class FoundationServiceProvider extends AggregateServiceProvider
{
    /**
     * The provider class names.
     *
     * 提供程序类名
     *
     * @var array
     */
    protected $providers = [
        FormRequestServiceProvider::class,
    ];
}
