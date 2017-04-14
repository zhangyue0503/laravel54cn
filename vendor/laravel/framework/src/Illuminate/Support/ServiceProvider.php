<?php

namespace Illuminate\Support;

use Illuminate\Console\Application as Artisan;

abstract class ServiceProvider
{
    /**
     * The application instance.
     *
     * 应用程序实例
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * 指示是否推迟提供程序的加载
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * The paths that should be published.
     *
     * 应该发布的路径
     *
     * @var array
     */
    protected static $publishes = [];

    /**
     * The paths that should be published by group.
     *
     * 应该由组发布的路径
     *
     * @var array
     */
    protected static $publishGroups = [];

    /**
     * Create a new service provider instance.
     *
     * 创建一个新的服务提供者实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * 将给定配置与现有配置合并
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    protected function mergeConfigFrom($path, $key)
    {
        $config = $this->app['config']->get($key, []);

        $this->app['config']->set($key, array_merge(require $path, $config));
    }

    /**
     * Load the given routes file if routes are not already cached.
     *
     * 如果路由未被缓存，加载给定的路由文件
     *
     * @param  string  $path
     * @return void
     */
    protected function loadRoutesFrom($path)
    {
        if (! $this->app->routesAreCached()) {     // 确定应用程序路由是否被缓存
            require $path;
        }
    }

    /**
     * Register a view file namespace.
     *
     * 注册视图文件命名空间
     *
     * @param  string  $path
     * @param  string  $namespace
     * @return void
     */
    protected function loadViewsFrom($path, $namespace)
    {
        //                         获取资源目录路径
        if (is_dir($appPath = $this->app->resourcePath().'/views/vendor/'.$namespace)) {
            $this->app['view']->addNamespace($namespace, $appPath); // 向目录添加命名空间提示 Illuminate\View\FileViewFinder::addNamespace()
        }

        $this->app['view']->addNamespace($namespace, $path); // 向目录添加命名空间提示 Illuminate\View\FileViewFinder::addNamespace()
    }

    /**
     * Register a translation file namespace.
     *
     * 注册翻译文件命名空间
     *
     * @param  string  $path
     * @param  string  $namespace
     * @return void
     */
    protected function loadTranslationsFrom($path, $namespace)
    {
        $this->app['translator']->addNamespace($namespace, $path); // 向目录添加命名空间提示 Illuminate\View\FileViewFinder::addNamespace()
    }

    /**
     * Register a database migration path.
     *
     * 注册数据库迁移路径
     *
     * @param  array|string  $paths
     * @return void
     */
    protected function loadMigrationsFrom($paths)
    {
        //          为所有类型注册一个新的解析后的回调 \Illuminate\Container\Container::afterResolving()
        $this->app->afterResolving('migrator', function ($migrator) use ($paths) {
            foreach ((array) $paths as $path) {
                $migrator->path($path);
            }
        });
    }

    /**
     * Register paths to be published by the publish command.
     *
     * 注册发布命令发布的路径
     *
     * @param  array  $paths
     * @param  string  $group
     * @return void
     */
    protected function publishes(array $paths, $group = null)
    {
        $this->ensurePublishArrayInitialized($class = static::class);    //确保服务提供程序的发布数组初始化

        static::$publishes[$class] = array_merge(static::$publishes[$class], $paths);

        if ($group) {
            $this->addPublishGroup($group, $paths);  // 向服务提供商添加发布组/标签
        }
    }

    /**
     * Ensure the publish array for the service provider is initialized.
     *
     * 确保服务提供程序的发布数组初始化
     *
     * @param  string  $class
     * @return void
     */
    protected function ensurePublishArrayInitialized($class)
    {
        if (! array_key_exists($class, static::$publishes)) {
            static::$publishes[$class] = [];
        }
    }

    /**
     * Add a publish group / tag to the service provider.
     *
     * 向服务提供商添加发布组/标签
     *
     * @param  string  $group
     * @param  array  $paths
     * @return void
     */
    protected function addPublishGroup($group, $paths)
    {
        if (! array_key_exists($group, static::$publishGroups)) {
            static::$publishGroups[$group] = [];
        }

        static::$publishGroups[$group] = array_merge(
            static::$publishGroups[$group], $paths
        );
    }

    /**
     * Get the paths to publish.
     *
     * 获取发布的路径
     *
     * @param  string  $provider
     * @param  string  $group
     * @return array
     */
    public static function pathsToPublish($provider = null, $group = null)
    {
        if (! is_null($paths = static::pathsForProviderOrGroup($provider, $group))) { //获取提供者或组（或两者）的路径
            return $paths;
        }

        return collect(static::$publishes)->reduce(function ($paths, $p) { //调用array_reduce
            return array_merge($paths, $p); //合并数组
        }, []);
    }

    /**
     * Get the paths for the provider or group (or both).
     *
     * 获取提供者或组（或两者）的路径
     *
     * @param  string|null  $provider
     * @param  string|null  $group
     * @return array
     */
    protected static function pathsForProviderOrGroup($provider, $group)
    {
        if ($provider && $group) {
            return static::pathsForProviderAndGroup($provider, $group);
        } elseif ($group && array_key_exists($group, static::$publishGroups)) {
            return static::$publishGroups[$group];
        } elseif ($provider && array_key_exists($provider, static::$publishes)) {
            return static::$publishes[$provider];
        } elseif ($group || $provider) {
            return [];
        }
    }

    /**
     * Get the paths for the provdider and group.
     *
     * 获取提供者和组的路径
     *
     * @param  string  $provider
     * @param  string  $group
     * @return array
     */
    protected static function pathsForProviderAndGroup($provider, $group)
    {
        if (! empty(static::$publishes[$provider]) && ! empty(static::$publishGroups[$group])) {
            return array_intersect_key(static::$publishes[$provider], static::$publishGroups[$group]);
        }

        return [];
    }

    /**
     * Register the package's custom Artisan commands.
     *
     * 注册包的自定义Artisan命令
     *
     * @param  array|mixed  $commands
     * @return void
     */
    public function commands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        //登记一个控制台的“starting”程序
        Artisan::starting(function ($artisan) use ($commands) {
            $artisan->resolveCommands($commands); //通过应用程序解析命令数组
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
        return [];
    }

    /**
     * Get the events that trigger this service provider to register.
     *
     * 获取触发此服务提供程序注册的事件
     *
     * @return array
     */
    public function when()
    {
        return [];
    }

    /**
     * Determine if the provider is deferred.
     *
     * 确定是否延迟提供程序
     *
     * @return bool
     */
    public function isDeferred()
    {
        return $this->defer;
    }

    /**
     * Get a list of files that should be compiled for the package.
     *
     * 获取要为包编译的文件列表
     *
     * @deprecated
     *
     * @return array
     */
    public static function compiles()
    {
        return [];
    }
}
