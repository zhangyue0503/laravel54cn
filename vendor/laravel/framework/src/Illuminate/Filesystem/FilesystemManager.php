<?php

namespace Illuminate\Filesystem;

use Closure;
use Aws\S3\S3Client;
use OpenCloud\Rackspace;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\Rackspace\RackspaceAdapter;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\AwsS3v3\AwsS3Adapter as S3Adapter;
use Illuminate\Contracts\Filesystem\Factory as FactoryContract;

class FilesystemManager implements FactoryContract
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
     * The array of resolved filesystem drivers.
     *
     * 解析的文件系统驱动程序的数组
     *
     * @var array
     */
    protected $disks = [];

    /**
     * The registered custom driver creators.
     *
     * 注册自定义驱动程序的创建者
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new filesystem manager instance.
     *
     * 创建一个新的文件系统管理器实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a filesystem instance.
     *
     * 获取文件系统实例
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function drive($name = null)
    {
        return $this->disk($name);//获取文件系统实例
    }

    /**
     * Get a filesystem instance.
     *
     * 获取文件系统实例
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function disk($name = null)
    {
        //                  获取默认的驱动程序名称
        $name = $name ?: $this->getDefaultDriver();
        //                           尝试从本地缓存获取磁盘
        return $this->disks[$name] = $this->get($name);
    }

    /**
     * Get a default cloud filesystem instance.
     *
     * 获得一个默认的云文件系统实例
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function cloud()
    {
        //             获得默认的云驱动名称
        $name = $this->getDefaultCloudDriver();
        //                           尝试从本地缓存获取磁盘
        return $this->disks[$name] = $this->get($name);
    }

    /**
     * Attempt to get the disk from the local cache.
     *
     * 尝试从本地缓存获取磁盘
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected function get($name)
    {
        //                                                         解析给定磁盘
        return isset($this->disks[$name]) ? $this->disks[$name] : $this->resolve($name);
    }

    /**
     * Resolve the given disk.
     *
     * 解析给定磁盘
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        //获取文件系统连接配置
        $config = $this->getConfig($name);

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);//调用自定义驱动程序创建者
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        } else {
            throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
        }
    }

    /**
     * Call a custom driver creator.
     *
     * 调用自定义驱动程序创建者
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected function callCustomCreator(array $config)
    {
        $driver = $this->customCreators[$config['driver']]($this->app, $config);

        if ($driver instanceof FilesystemInterface) {
            return $this->adapt($driver);//适配文件系统实现
        }

        return $driver;
    }

    /**
     * Create an instance of the local driver.
     *
     * 创建本地驱动的实例
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function createLocalDriver(array $config)
    {
        $permissions = isset($config['permissions']) ? $config['permissions'] : [];
        //使用“点”符号从数组中获取一个项
        $links = Arr::get($config, 'links') === 'skip'
            ? LocalAdapter::SKIP_LINKS
            : LocalAdapter::DISALLOW_LINKS;
        //适配文件系统实现           使用给定适配器创建一个Flysystem实例
        return $this->adapt($this->createFlysystem(new LocalAdapter(
            $config['root'], LOCK_EX, $links, $permissions
        ), $config));
    }

    /**
     * Create an instance of the ftp driver.
     *
     * 创建ftp驱动程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function createFtpDriver(array $config)
    {
        //从给定数组中获取项目的子集
        $ftpConfig = Arr::only($config, [
            'host', 'username', 'password', 'port', 'root', 'passive', 'ssl', 'timeout',
        ]);
        //适配文件系统实现           使用给定适配器创建一个Flysystem实例
        return $this->adapt($this->createFlysystem(
            new FtpAdapter($ftpConfig), $config
        ));
    }

    /**
     * Create an instance of the Amazon S3 driver.
     *
     * 创建Amazon S3驱动程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Filesystem\Cloud
     */
    public function createS3Driver(array $config)
    {
        //              使用默认选项格式化给定的S3配置
        $s3Config = $this->formatS3Config($config);

        $root = isset($s3Config['root']) ? $s3Config['root'] : null;

        $options = isset($config['options']) ? $config['options'] : [];
        //适配文件系统实现           使用给定适配器创建一个Flysystem实例
        return $this->adapt($this->createFlysystem(
            new S3Adapter(new S3Client($s3Config), $s3Config['bucket'], $root, $options), $config
        ));
    }

    /**
     * Format the given S3 configuration with the default options.
     *
     * 使用默认选项格式化给定的S3配置
     *
     * @param  array  $config
     * @return array
     */
    protected function formatS3Config(array $config)
    {
        $config += ['version' => 'latest'];

        if ($config['key'] && $config['secret']) {
            //                    从给定数组中获取项目的子集
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }

        return $config;
    }

    /**
     * Create an instance of the Rackspace driver.
     *
     * 创建一个Rackspace驱动程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Filesystem\Cloud
     */
    public function createRackspaceDriver(array $config)
    {
        $client = new Rackspace($config['endpoint'], [
            'username' => $config['username'], 'apiKey' => $config['key'],
        ]);

        $root = isset($config['root']) ? $config['root'] : null;
        //适配文件系统实现           使用给定适配器创建一个Flysystem实例
        return $this->adapt($this->createFlysystem(
            //获取Rackspace Cloud文件容器
            new RackspaceAdapter($this->getRackspaceContainer($client, $config), $root), $config
        ));
    }

    /**
     * Get the Rackspace Cloud Files container.
     *
     * 获取Rackspace Cloud文件容器
     *
     * @param  \OpenCloud\Rackspace  $client
     * @param  array  $config
     * @return \OpenCloud\ObjectStore\Resource\Container
     */
    protected function getRackspaceContainer(Rackspace $client, array $config)
    {
        //使用“点”符号从数组中获取一个项
        $urlType = Arr::get($config, 'url_type');

        $store = $client->objectStoreService('cloudFiles', $config['region'], $urlType);

        return $store->getContainer($config['container']);
    }

    /**
     * Create a Flysystem instance with the given adapter.
     *
     * 使用给定适配器创建一个Flysystem实例
     *
     * @param  \League\Flysystem\AdapterInterface  $adapter
     * @param  array  $config
     * @return \League\Flysystem\FlysystemInterface
     */
    protected function createFlysystem(AdapterInterface $adapter, array $config)
    {
        //       从给定数组中获取项目的子集
        $config = Arr::only($config, ['visibility', 'disable_asserts', 'url']);

        return new Flysystem($adapter, count($config) > 0 ? $config : null);
    }

    /**
     * Adapt the filesystem implementation.
     *
     * 适配文件系统实现
     *
     * @param  \League\Flysystem\FilesystemInterface  $filesystem
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected function adapt(FilesystemInterface $filesystem)
    {
        return new FilesystemAdapter($filesystem);
    }

    /**
     * Set the given disk instance.
     *
     * 设置给定的磁盘实例
     *
     * @param  string  $name
     * @param  mixed  $disk
     * @return void
     */
    public function set($name, $disk)
    {
        $this->disks[$name] = $disk;
    }

    /**
     * Get the filesystem connection configuration.
     *
     * 获取文件系统连接配置
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["filesystems.disks.{$name}"];
    }

    /**
     * Get the default driver name.
     *
     * 获取默认的驱动程序名称
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['filesystems.default'];
    }

    /**
     * Get the default cloud driver name.
     *
     * 获得默认的云驱动名称
     *
     * @return string
     */
    public function getDefaultCloudDriver()
    {
        return $this->app['config']['filesystems.cloud'];
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string    $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

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
        //获取文件系统实例
        return $this->disk()->$method(...$parameters);
    }
}
