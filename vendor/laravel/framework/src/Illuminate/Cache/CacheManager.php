<?php

namespace Illuminate\Cache;

use Closure;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Cache\Factory as FactoryContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

class CacheManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * 应用程序实例
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved cache stores.
     *
     * 已解析的缓存存储的数组
     *
     * @var array
     */
    protected $stores = [];

    /**
     * The registered custom driver creators.
     *
     * 注册自定义驱动程序的创建者
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new Cache manager instance.
     *
     * 创建一个新的缓存管理器实例
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a cache store instance by name.
     *
     * 以名称获取缓存存储实例
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function store($name = null)
    {
        //获取默认的高速缓存驱动程序名称
        $name = $name ?: $this->getDefaultDriver();
        //                           试图从本地缓存获取存储区
        return $this->stores[$name] = $this->get($name);
    }

    /**
     * Get a cache driver instance.
     *
     * 获取高速缓存驱动程序实例
     *
     * @param  string  $driver
     * @return mixed
     */
    public function driver($driver = null)
    {
        //以名称获取缓存存储实例
        return $this->store($driver);
    }

    /**
     * Attempt to get the store from the local cache.
     *
     * 试图从本地缓存获取存储区
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function get($name)
    {
        return isset($this->stores[$name]) ? $this->stores[$name] : $this->resolve($name);
    }

    /**
     * Resolve the given store.
     *
     * 解析给定的存储
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Cache\Repository
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        //          获取高速缓存连接配置
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Cache store [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            //调用自定义驱动程序创建者
            return $this->callCustomCreator($config);
        } else {
            $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

            if (method_exists($this, $driverMethod)) {
                return $this->{$driverMethod}($config);
            } else {
                throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
            }
        }
    }

    /**
     * Call a custom driver creator.
     *
     * 调用自定义驱动程序创建者
     *
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create an instance of the APC cache driver.
     *
     * 创建APC高速缓存驱动程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Cache\ApcStore
     */
    protected function createApcDriver(array $config)
    {
        //获取缓存前缀
        $prefix = $this->getPrefix($config);
        //使用给定的实现创建一个新的缓存存储库(创建一个新的APC商店(创建一个新的APC包装器实例))
        return $this->repository(new ApcStore(new ApcWrapper, $prefix));
    }

    /**
     * Create an instance of the array cache driver.
     *
     * 创建一个数组缓存驱动程序的实例
     *
     * @return \Illuminate\Cache\ArrayStore
     */
    protected function createArrayDriver()
    {
        //使用给定的实现创建一个新的缓存存储库
        return $this->repository(new ArrayStore);
    }

    /**
     * Create an instance of the file cache driver.
     *
     * 创建文件缓存驱动程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Cache\FileStore
     */
    protected function createFileDriver(array $config)
    {
        //使用给定的实现创建一个新的缓存存储库(创建一个新的文件缓存存储实例)
        return $this->repository(new FileStore($this->app['files'], $config['path']));
    }

    /**
     * Create an instance of the Memcached cache driver.
     *
     * 创建Memcached缓存驱动程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Cache\MemcachedStore
     */
    protected function createMemcachedDriver(array $config)
    {
        //获取缓存前缀
        $prefix = $this->getPrefix($config);
        //                                      创建一个新的Memcached连接
        $memcached = $this->app['memcached.connector']->connect(
            $config['servers'],
            array_get($config, 'persistent_id'),
            array_get($config, 'options', []),
            array_filter(array_get($config, 'sasl', []))
        );
        //使用给定的实现创建一个新的缓存存储库(创建一个新的Memcached存储)
        return $this->repository(new MemcachedStore($memcached, $prefix));
    }

    /**
     * Create an instance of the Null cache driver.
     *
     * 创建一个空高速缓存驱动程序的实例
     *
     * @return \Illuminate\Cache\NullStore
     */
    protected function createNullDriver()
    {
        //使用给定的实现创建一个新的缓存存储库()
        return $this->repository(new NullStore);
    }

    /**
     * Create an instance of the Redis cache driver.
     *
     * 创建一个Redis缓存驱动程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Cache\RedisStore
     */
    protected function createRedisDriver(array $config)
    {
        $redis = $this->app['redis'];
        //            使用“点”符号从数组中获取一个项
        $connection = Arr::get($config, 'connection', 'default');
        //          使用给定的实现创建一个新的缓存存储库(创建一个新的Redis存储(,获取缓存前缀,))
        return $this->repository(new RedisStore($redis, $this->getPrefix($config), $connection));
    }

    /**
     * Create an instance of the database cache driver.
     *
     * 创建数据库缓存驱动程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Cache\DatabaseStore
     */
    protected function createDatabaseDriver(array $config)
    {
        //                        创建一个新的数据库存储(使用“点”符号从数组中获取一个项)
        $connection = $this->app['db']->connection(Arr::get($config, 'connection'));
        //使用给定的实现创建一个新的缓存存储库
        return $this->repository(
            new DatabaseStore(//创建一个新的数据库存储                                获取缓存前缀
                $connection, $this->app['encrypter'], $config['table'], $this->getPrefix($config)
            )
        );
    }

    /**
     * Create a new cache repository with the given implementation.
     *
     * 使用给定的实现创建一个新的缓存存储库
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return \Illuminate\Cache\Repository
     */
    public function repository(Store $store)
    {
        //            创建一个新的缓存存储库实例
        $repository = new Repository($store);
        //  确定给定的抽象类型是否已绑定()
        if ($this->app->bound(DispatcherContract::class)) {
            $repository->setEventDispatcher(//设置事件调度程序实例
                $this->app[DispatcherContract::class]
            );
        }

        return $repository;
    }

    /**
     * Get the cache prefix.
     *
     * 获取缓存前缀
     *
     * @param  array  $config
     * @return string
     */
    protected function getPrefix(array $config)
    {
        //使用“点”符号从数组中获取一个项
        return Arr::get($config, 'prefix') ?: $this->app['config']['cache.prefix'];
    }

    /**
     * Get the cache connection configuration.
     *
     * 获取高速缓存连接配置
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["cache.stores.{$name}"];
    }

    /**
     * Get the default cache driver name.
     *
     * 获取默认的高速缓存驱动程序名称
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['cache.default'];
    }

    /**
     * Set the default cache driver name.
     *
     * 设置默认的高速缓存驱动程序名称
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['cache.default'] = $name;
    }

    /**
     * Register a custom driver creator Closure.
     *
     * 注册一个自定义驱动程序创建者的闭包
     *
     * @param  string    $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback->bindTo($this, $this);

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * 动态调用默认驱动程序实例
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        //以名称获取缓存存储实例
        return $this->store()->$method(...$parameters);
    }
}
