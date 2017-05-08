<?php

namespace Illuminate\Auth;

use InvalidArgumentException;

trait CreatesUserProviders
{
    /**
     * The registered custom provider creators.
     * 注册自定义提供者的创建者
     *
     *
     * @var array
     */
    protected $customProviderCreators = [];

    /**
     * Create the user provider implementation for the driver.
     *
     * 为驱动程序创建用户提供程序实现
     *
     * @param  string  $provider
     * @return \Illuminate\Contracts\Auth\UserProvider
     *
     * @throws \InvalidArgumentException
     */
    public function createUserProvider($provider)
    {
        $config = $this->app['config']['auth.providers.'.$provider];

        if (isset($this->customProviderCreators[$config['driver']])) {
            return call_user_func(
                $this->customProviderCreators[$config['driver']], $this->app, $config
            );
        }

        switch ($config['driver']) {
            case 'database':
                //创建数据库用户提供程序的实例
                return $this->createDatabaseProvider($config);
            case 'eloquent':
                //创建一个Eloquent的用户提供程序实例
                return $this->createEloquentProvider($config);
            default:
                throw new InvalidArgumentException("Authentication user provider [{$config['driver']}] is not defined.");
        }
    }

    /**
     * Create an instance of the database user provider.
     *
     * 创建数据库用户提供程序的实例
     *
     * @param  array  $config
     * @return \Illuminate\Auth\DatabaseUserProvider
     */
    protected function createDatabaseProvider($config)
    {
        $connection = $this->app['db']->connection();
        //创建一个新的数据库用户提供者
        return new DatabaseUserProvider($connection, $this->app['hash'], $config['table']);
    }

    /**
     * Create an instance of the Eloquent user provider.
     *
     * 创建一个Eloquent的用户提供程序实例
     *
     * @param  array  $config
     * @return \Illuminate\Auth\EloquentUserProvider
     */
    protected function createEloquentProvider($config)
    {
        //创建一个新的数据库用户提供程序
        return new EloquentUserProvider($this->app['hash'], $config['model']);
    }
}
