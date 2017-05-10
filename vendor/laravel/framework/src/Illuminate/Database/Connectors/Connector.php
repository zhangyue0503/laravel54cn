<?php

namespace Illuminate\Database\Connectors;

use PDO;
use Exception;
use Illuminate\Support\Arr;
use Doctrine\DBAL\Driver\PDOConnection;
use Illuminate\Database\DetectsLostConnections;

class Connector
{
    use DetectsLostConnections;

    /**
     * The default PDO connection options.
     *
     * 默认的PDO连接选项
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * Create a new PDO connection.
	 *
	 * 创建一个新的PDO连接
     *
     * @param  string  $dsn
     * @param  array   $config
     * @param  array   $options
     * @return \PDO
     */
    public function createConnection($dsn, array $config, array $options)
    {
        list($username, $password) = [
            //使用“点”符号从数组中获取一个项
            Arr::get($config, 'username'), Arr::get($config, 'password'),
        ];

        try {
            return $this->createPdoConnection( // 创建一个新的PDO连接实例
                $dsn, $username, $password, $options
            );
        } catch (Exception $e) {
            //处理连接执行期间发生的异常
            return $this->tryAgainIfCausedByLostConnection(
                $e, $dsn, $username, $password, $options
            );
        }
    }

    /**
     * Create a new PDO connection instance.
	 *
	 * 创建一个新的PDO连接实例
     *
     * @param  string  $dsn
     * @param  string  $username
     * @param  string  $password
     * @param  array  $options
     * @return \PDO
     */
    protected function createPdoConnection($dsn, $username, $password, $options)
    {
        //                                             确定连接是否持久
        if (class_exists(PDOConnection::class) && ! $this->isPersistentConnection($options)) {
            return new PDOConnection($dsn, $username, $password, $options);
        }

        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * Determine if the connection is persistent.
     *
     * 确定连接是否持久
     *
     * @param  array  $options
     * @return bool
     */
    protected function isPersistentConnection($options)
    {
        return isset($options[PDO::ATTR_PERSISTENT]) &&
               $options[PDO::ATTR_PERSISTENT];
    }

    /**
     * Handle an exception that occurred during connect execution.
     *
     * 处理连接执行期间发生的异常
     *
     * @param  \Exception  $e
     * @param  string  $dsn
     * @param  string  $username
     * @param  string  $password
     * @param  array   $options
     * @return \PDO
     *
     * @throws \Exception
     */
    protected function tryAgainIfCausedByLostConnection(Exception $e, $dsn, $username, $password, $options)
    {
        //确定给定的异常是否由丢失的连接引起
        if ($this->causedByLostConnection($e)) {
            //创建一个新的PDO连接实例
            return $this->createPdoConnection($dsn, $username, $password, $options);
        }

        throw $e;
    }

    /**
     * Get the PDO options based on the configuration.
     *
     * 根据配置获得PDO选项
     *
     * @param  array  $config
     * @return array
     */
    public function getOptions(array $config)
    {
        //使用“点”符号从数组中获取一个项
        $options = Arr::get($config, 'options', []);

        return array_diff_key($this->options, $options) + $options;
    }

    /**
     * Get the default PDO connection options.
     *
     * 获得默认的PDO连接选项
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return $this->options;
    }

    /**
     * Set the default PDO connection options.
     *
     * 设置默认的PDO连接选项
     *
     * @param  array  $options
     * @return void
     */
    public function setDefaultOptions(array $options)
    {
        $this->options = $options;
    }
}
