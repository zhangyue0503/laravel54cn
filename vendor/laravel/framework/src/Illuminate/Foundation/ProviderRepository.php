<?php

namespace Illuminate\Foundation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

class ProviderRepository
{
    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The path to the manifest file.
     *
     * @var string
     */
    protected $manifestPath;

    /**
     * Create a new service repository instance.
     *
     * 创建一个新的服务库实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(ApplicationContract $app, Filesystem $files, $manifestPath)
    {
        $this->app = $app;
        $this->files = $files;
        $this->manifestPath = $manifestPath;
    }

    /**
     * Register the application service providers.
     *
     * 注册应用服务提供商
     *
     * @param  array  $providers
     * @return void
     */
    public function load(array $providers)
    {
        $manifest = $this->loadManifest(); //加载服务提供商清单JSON文件

        // First we will load the service manifest, which contains information on all
        // service providers registered with the application and which services it
        // provides. This is used to know which services are "deferred" loaders.
		//
		// 首先，我们将加载服务清单，该服务清单包含应用程序注册的所有服务提供商的信息以及它提供的服务
		// 这是用来知道哪些服务是“延期”装载机
		// * 加载服务清单，这里包含程序所有的服务提供者并进行了分类
		//
		//        确定清单是否应被编译
        if ($this->shouldRecompile($manifest, $providers)) {
            $manifest = $this->compileManifest($providers); // 编译应用程序服务清单文件
        }

        // Next, we will register events to load the providers for each of the events
        // that it has requested. This allows the service provider to defer itself
        // while still getting automatically loaded when a certain event occurs.
		//
		// 接下来，我们将注册事件以加载它所请求的每个事件的提供者
		// 这允许服务提供者在某些事件发生时仍自动加载
		// * 服务提供者加载事件，当这个事件发生时才自动加载 这个服务提供者
		//
        foreach ($manifest['when'] as $provider => $events) {
            $this->registerLoadEvents($provider, $events); // 为给定的提供者注册加载事件
        }

        // We will go ahead and register all of the eagerly loaded providers with the
        // application so their services can be registered with the application as
        // a provided service. Then we will set the deferred service list on it.
		//
		// 我们将继续使用应用程序注册所有急切加载的提供者，这样他们的服务就可以与应用程序作为提供的服务注册
		// 然后，我们将设置延迟服务列表
		// * 提前注册那些必须加载的服务提供者，以此为应用提供服务
		//
        foreach ($manifest['eager'] as $provider) {
            $this->app->register($provider); // 应用程序注册服务提供者
        }
		//在列表中记录延迟加载的服务提供者，需要时再加载
        $this->app->addDeferredServices($manifest['deferred']); //向应用程序的延迟服务数组添加一组服务
    }

    /**
     * Load the service provider manifest JSON file.
	 *
	 * 加载服务提供商清单JSON文件
	 * //laravel\bootstrap\cache\services.json
     *
     * @return array|null
     */
    public function loadManifest()
    {
        // The service manifest is a file containing a JSON representation of every
        // service provided by the application and whether its provider is using
        // deferred loading or should be eagerly loaded on each request to us.
		//
		// 服务清单是包含应用程序提供的每一项服务的JSON表示的文件，其提供程序是否使用延迟加载，或应在每个请求上急切加载
		//
		//       确定文件或目录是否存在
        if ($this->files->exists($this->manifestPath)) {
            $manifest = $this->files->getRequire($this->manifestPath); // 获取文件的返回值 require $file

            if ($manifest) {
                return array_merge(['when' => []], $manifest);
            }
        }
    }

    /**
     * Determine if the manifest should be compiled.
	 *
	 * 确定清单是否应被编译
     *
     * @param  array  $manifest
     * @param  array  $providers
     * @return bool
     */
    public function shouldRecompile($manifest, $providers)
    {
        return is_null($manifest) || $manifest['providers'] != $providers;
    }

    /**
     * Register the load events for the given provider.
	 *
	 * 为给定的提供者注册加载事件
     *
     * @param  string  $provider
     * @param  array  $events
     * @return void
     */
    protected function registerLoadEvents($provider, array $events)
    {
        if (count($events) < 1) {
            return;
        }

        $this->app->make('events')->listen($events, function () use ($provider) {
            $this->app->register($provider);
        });
    }

    /**
     * Compile the application service manifest file.
	 *
	 * 编译应用程序服务清单文件
     *
     * @param  array  $providers
     * @return array
     */
    protected function compileManifest($providers)
    {
        // The service manifest should contain a list of all of the providers for
        // the application so we can compare it on each request to the service
        // and determine if the manifest should be recompiled or is current.
        $manifest = $this->freshManifest($providers);

        foreach ($providers as $provider) {
            $instance = $this->createProvider($provider);

            // When recompiling the service manifest, we will spin through each of the
            // providers and check if it's a deferred provider or not. If so we'll
            // add it's provided services to the manifest and note the provider.
            if ($instance->isDeferred()) {
                foreach ($instance->provides() as $service) {
                    $manifest['deferred'][$service] = $provider;
                }

                $manifest['when'][$provider] = $instance->when();
            }

            // If the service providers are not deferred, we will simply add it to an
            // array of eagerly loaded providers that will get registered on every
            // request to this application instead of "lazy" loading every time.
            else {
                $manifest['eager'][] = $provider;
            }
        }

        return $this->writeManifest($manifest);
    }

    /**
     * Create a fresh service manifest data structure.
     *
     * @param  array  $providers
     * @return array
     */
    protected function freshManifest(array $providers)
    {
        return ['providers' => $providers, 'eager' => [], 'deferred' => []];
    }

    /**
     * Write the service manifest file to disk.
     *
     * @param  array  $manifest
     * @return array
     */
    public function writeManifest($manifest)
    {
        $this->files->put(
            $this->manifestPath, '<?php return '.var_export($manifest, true).';'
        );

        return array_merge(['when' => []], $manifest);
    }

    /**
     * Create a new provider instance.
     *
     * @param  string  $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function createProvider($provider)
    {
        return new $provider($this->app);
    }
}
