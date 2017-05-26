<?php

namespace Illuminate\Session;

use Illuminate\Support\Manager;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;

class SessionManager extends Manager
{
    /**
     * Call a custom driver creator.
     *
     * 调用自定义驱动程序创建者
     *
     * @param  string  $driver
     * @return mixed
     */
    protected function callCustomCreator($driver)
    {
        //               建立会话实例        调用自定义驱动程序创建者
        return $this->buildSession(parent::callCustomCreator($driver));
    }

    /**
     * Create an instance of the "array" session driver.
     *
     * 创建一个“数组”会话驱动程序的实例
     *
     * @return \Illuminate\Session\Store
     */
    protected function createArrayDriver()
    {
        //             建立会话实例
        return $this->buildSession(new NullSessionHandler);
    }

    /**
     * Create an instance of the "cookie" session driver.
     *
     * 创建一个“cookie”会话驱动程序的实例
     *
     * @return \Illuminate\Session\Store
     */
    protected function createCookieDriver()
    {
        //             建立会话实例         创建一个新的cookie驱动的处理程序实例
        return $this->buildSession(new CookieSessionHandler(
            $this->app['cookie'], $this->app['config']['session.lifetime']
        ));
    }

    /**
     * Create an instance of the file session driver.
	 *
	 * 创建文件会话驱动程序的实例
     *
     * @return \Illuminate\Session\Store
     */
    protected function createFileDriver()
    {
        // 创建文件会话驱动程序的实例
        return $this->createNativeDriver();
    }

    /**
     * Create an instance of the file session driver.
	 *
	 * 创建文件会话驱动程序的实例
     *
     * @return \Illuminate\Session\Store
     */
    protected function createNativeDriver()
    {
        $lifetime = $this->app['config']['session.lifetime'];
		//      建立会话实例                创建一个新的文件驱动处理程序实例
        return $this->buildSession(new FileSessionHandler(
            $this->app['files'], $this->app['config']['session.files'], $lifetime
        ));
    }

    /**
     * Create an instance of the database session driver.
     *
     * 创建数据库会话驱动程序的实例
     *
     * @return \Illuminate\Session\Store
     */
    protected function createDatabaseDriver()
    {
        $table = $this->app['config']['session.table'];

        $lifetime = $this->app['config']['session.lifetime'];
        //             建立会话实例      创建一个新的数据库会话处理程序实例
        return $this->buildSession(new DatabaseSessionHandler(
            //获取数据库驱动程序的数据库连接
            $this->getDatabaseConnection(), $table, $lifetime, $this->app
        ));
    }

    /**
     * Get the database connection for the database driver.
     *
     * 获取数据库驱动程序的数据库连接
     *
     * @return \Illuminate\Database\Connection
     */
    protected function getDatabaseConnection()
    {
        $connection = $this->app['config']['session.connection'];

        return $this->app['db']->connection($connection);
    }

    /**
     * Create an instance of the APC session driver.
     *
     * 创建APC会话驱动程序的实例
     *
     * @return \Illuminate\Session\Store
     */
    protected function createApcDriver()
    {
        //          创建一个高速缓存驱动驱动程序的实例
        return $this->createCacheBased('apc');
    }

    /**
     * Create an instance of the Memcached session driver.
     *
     * 创建Memcached会话驱动程序的实例
     *
     * @return \Illuminate\Session\Store
     */
    protected function createMemcachedDriver()
    {
        //          创建一个高速缓存驱动驱动程序的实例
        return $this->createCacheBased('memcached');
    }

    /**
     * Create an instance of the Redis session driver.
     *
     * 创建一个Redis会话驱动程序的实例
     *
     * @return \Illuminate\Session\Store
     */
    protected function createRedisDriver()
    {
        //          创建基于缓存的会话处理程序实例
        $handler = $this->createCacheHandler('redis');
        //获取底层的缓存存储库     获取缓存存储实现  设置要使用的连接名称
        $handler->getCache()->getStore()->setConnection(
            $this->app['config']['session.connection']
        );
        //           建立会话实例
        return $this->buildSession($handler);
    }

    /**
     * Create an instance of a cache driven driver.
     *
     * 创建一个高速缓存驱动驱动程序的实例
     *
     * @param  string  $driver
     * @return \Illuminate\Session\Store
     */
    protected function createCacheBased($driver)
    {
        //            建立会话实例         创建基于缓存的会话处理程序实例
        return $this->buildSession($this->createCacheHandler($driver));
    }

    /**
     * Create the cache based session handler instance.
     *
     * 创建基于缓存的会话处理程序实例
     *
     * @param  string  $driver
     * @return \Illuminate\Session\CacheBasedSessionHandler
     */
    protected function createCacheHandler($driver)
    {
        $store = $this->app['config']->get('session.store') ?: $driver;
        //       创建一个新的缓存驱动的处理程序实例
        return new CacheBasedSessionHandler(
            clone $this->app['cache']->store($store),
            $this->app['config']['session.lifetime']
        );
    }

    /**
     * Build the session instance.
	 *
	 * 建立会话实例
     *
     * @param  \SessionHandlerInterface  $handler
     * @return \Illuminate\Session\Store
     */
    protected function buildSession($handler)
    {
        if ($this->app['config']['session.encrypt']) {
            //            构建加密的会话实例
            return $this->buildEncryptedSession($handler);
        } else {
			//       创建一个新的会话实例
            return new Store($this->app['config']['session.cookie'], $handler);
        }
    }

    /**
     * Build the encrypted session instance.
     *
     * 构建加密的会话实例
     *
     * @param  \SessionHandlerInterface  $handler
     * @return \Illuminate\Session\EncryptedStore
     */
    protected function buildEncryptedSession($handler)
    {
        //       创建一个新的会话实例
        return new EncryptedStore(
            $this->app['config']['session.cookie'], $handler, $this->app['encrypter']
        );
    }

    /**
     * Get the session configuration.
	 *
	 * 获取会话配置
     *
     * @return array
     */
    public function getSessionConfig()
    {
        return $this->app['config']['session'];
    }

    /**
     * Get the default session driver name.
	 *
	 * 获取默认会话驱动程序名称
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['session.driver'];
    }

    /**
     * Set the default session driver name.
     *
     * 设置默认的会话驱动程序名称
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['session.driver'] = $name;
    }
}
