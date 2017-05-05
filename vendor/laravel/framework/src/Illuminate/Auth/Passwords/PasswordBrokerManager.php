<?php

namespace Illuminate\Auth\Passwords;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Contracts\Auth\PasswordBrokerFactory as FactoryContract;

class PasswordBrokerManager implements FactoryContract
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
     * The array of created "drivers".
     *
     * 创建数组的“drivers”
     *
     * @var array
     */
    protected $brokers = [];

    /**
     * Create a new PasswordBroker manager instance.
     *
     * 创建一个新的PasswordBroker管理器实例
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Attempt to get the broker from the local cache.
     *
     * 尝试从本地缓存中获取代理
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker($name = null)
    {
        //                获得默认的密码代理名称
        $name = $name ?: $this->getDefaultDriver();

        return isset($this->brokers[$name])
                    ? $this->brokers[$name]
                    : $this->brokers[$name] = $this->resolve($name);//解析给定的代理
    }

    /**
     * Resolve the given broker.
     *
     * 解析给定的代理
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        //              获取密码代理配置
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Password resetter [{$name}] is not defined.");
        }

        // The password broker uses a token repository to validate tokens and send user
        // password e-mails, as well as validating that password reset process as an
        // aggregate service of sorts providing a convenient interface for resets.
        //
        // 密码代理使用令牌存储库来验证令牌，并发送用户密码电子邮件，并验证密码重置过程为各种类型的聚合服务，为重新设置提供方便的接口
        //
        //       创建一个新的密码代理实例
        return new PasswordBroker(
            $this->createTokenRepository($config),//根据给定的配置创建一个令牌存储库实例
            $this->app['auth']->createUserProvider($config['provider'])//创建基于会话的身份验证保护
        );
    }

    /**
     * Create a token repository instance based on the given configuration.
     *
     * 根据给定的配置创建一个令牌存储库实例
     *
     * @param  array  $config
     * @return \Illuminate\Auth\Passwords\TokenRepositoryInterface
     */
    protected function createTokenRepository(array $config)
    {
        $key = $this->app['config']['app.key'];
        //确定给定的子字符串是否属于给定的字符串
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $connection = isset($config['connection']) ? $config['connection'] : null;
        //创建一个新的令牌存储库实例
        return new DatabaseTokenRepository(
            $this->app['db']->connection($connection),//获取数据库连接实例
            $this->app['hash'],
            $config['table'],
            $key,
            $config['expire']
        );
    }

    /**
     * Get the password broker configuration.
     *
     * 获取密码代理配置
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["auth.passwords.{$name}"];
    }

    /**
     * Get the default password broker name.
     *
     * 获得默认的密码代理名称
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['auth.defaults.passwords'];
    }

    /**
     * Set the default password broker name.
     *
     * 设置默认的密码代理名称
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['auth.defaults.passwords'] = $name;
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
        //尝试从本地缓存中获取代理
        return $this->broker()->{$method}(...$parameters);
    }
}
