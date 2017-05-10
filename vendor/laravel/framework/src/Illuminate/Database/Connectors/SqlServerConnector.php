<?php

namespace Illuminate\Database\Connectors;

use PDO;
use Illuminate\Support\Arr;

class SqlServerConnector extends Connector implements ConnectorInterface
{
    /**
     * The PDO connection options.
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
    ];

    /**
     * Establish a database connection.
     *
     * 建立数据库连接
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        //根据配置获得PDO选项
        $options = $this->getOptions($config);
        //创建一个新的PDO连接             从配置中创建一个DSN字符串
        return $this->createConnection($this->getDsn($config), $config, $options);
    }

    /**
     * Create a DSN string from a configuration.
     *
     * 从配置中创建一个DSN字符串
     *
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        // First we will create the basic DSN setup as well as the port if it is in
        // in the configuration options. This will give us the basic DSN we will
        // need to establish the PDO connections and return them back for use.
        //
        // 首先，如果在配置选项中，我们将创建基本的DSN设置和端口
        // 这将为我们提供基本的DSN，我们需要建立PDO连接并将它们返回来使用
        //
        //                           获取可用的PDO驱动程序
        if (in_array('dblib', $this->getAvailableDrivers())) {
            //获取一个DbLib连接的DSN字符串
            return $this->getDblibDsn($config);
            //            确定数据库配置是否更喜欢ODBC
        } elseif ($this->prefersOdbc($config)) {
            //获取ODBC连接的DSN字符串
            return $this->getOdbcDsn($config);
        } else {
            //得到DSN SqlSrv连接字符串
            return $this->getSqlSrvDsn($config);
        }
    }

    /**
     * Determine if the database configuration prefers ODBC.
     *
     * 确定数据库配置是否更喜欢ODBC
     *
     * @param  array  $config
     * @return bool
     */
    protected function prefersOdbc(array $config)
    {
        //                         获取可用的PDO驱动程序
        return in_array('odbc', $this->getAvailableDrivers()) &&
            //使用“点”符号从数组中获取一个项
               array_get($config, 'odbc') === true;
    }

    /**
     * Get the DSN string for a DbLib connection.
     *
     * 获取一个DbLib连接的DSN字符串
     *
     * @param  array  $config
     * @return string
     */
    protected function getDblibDsn(array $config)
    {
        //从给定的参数中构建连接字符串
        return $this->buildConnectString('dblib', array_merge([
            //              从给定的配置中构建一个主机字符串
            'host' => $this->buildHostString($config, ':'),
            'dbname' => $config['database'],
            //从给定数组中获取项目的子集
        ], Arr::only($config, ['appname', 'charset'])));
    }

    /**
     * Get the DSN string for an ODBC connection.
     *
     * 获取ODBC连接的DSN字符串
     *
     * @param  array  $config
     * @return string
     */
    protected function getOdbcDsn(array $config)
    {
        return isset($config['odbc_datasource_name'])
                    ? 'odbc:'.$config['odbc_datasource_name'] : '';
    }

    /**
     * Get the DSN string for a SqlSrv connection.
     *
     * 得到DSN SqlSrv连接字符串
     *
     * @param  array  $config
     * @return string
     */
    protected function getSqlSrvDsn(array $config)
    {
        $arguments = [
            //               从给定的配置中构建一个主机字符串
            'Server' => $this->buildHostString($config, ','),
        ];

        if (isset($config['database'])) {
            $arguments['Database'] = $config['database'];
        }

        if (isset($config['readonly'])) {
            $arguments['ApplicationIntent'] = 'ReadOnly';
        }

        if (isset($config['pooling']) && $config['pooling'] === false) {
            $arguments['ConnectionPooling'] = '0';
        }

        if (isset($config['appname'])) {
            $arguments['APP'] = $config['appname'];
        }

        if (isset($config['encrypt'])) {
            $arguments['Encrypt'] = $config['encrypt'];
        }

        if (isset($config['trust_server_certificate'])) {
            $arguments['TrustServerCertificate'] = $config['trust_server_certificate'];
        }
        //           从给定的参数中构建连接字符串
        return $this->buildConnectString('sqlsrv', $arguments);
    }

    /**
     * Build a connection string from the given arguments.
     *
     * 从给定的参数中构建连接字符串
     *
     * @param  string  $driver
     * @param  array  $arguments
     * @return string
     */
    protected function buildConnectString($driver, array $arguments)
    {
        return $driver.':'.implode(';', array_map(function ($key) use ($arguments) {
            return sprintf('%s=%s', $key, $arguments[$key]);
        }, array_keys($arguments)));
    }

    /**
     * Build a host string from the given configuration.
     *
     * 从给定的配置中构建一个主机字符串
     *
     * @param  array  $config
     * @param  string  $separator
     * @return string
     */
    protected function buildHostString(array $config, $separator)
    {
        if (isset($config['port']) && ! empty($config['port'])) {
            return $config['host'].$separator.$config['port'];
        } else {
            return $config['host'];
        }
    }

    /**
     * Get the available PDO drivers.
     *
     * 获取可用的PDO驱动程序
     *
     * @return array
     */
    protected function getAvailableDrivers()
    {
        return PDO::getAvailableDrivers();
    }
}
