<?php

namespace Illuminate\Database\Connectors;

use PDO;

class MySqlConnector extends Connector implements ConnectorInterface
{
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
        $dsn = $this->getDsn($config);// 从DSN字符串创建一个配置

        $options = $this->getOptions($config);

        // We need to grab the PDO options that should be used while making the brand
        // new connection instance. The PDO options control various aspects of the
        // connection's behavior, and some might be specified by the developers.
		//
		// 我们需要抓住，要使品牌新的连接实例使用PDO的选项
		// PDO选项控制连接的行为的各个方面，有的可能是由开发商指定的
		//
        $connection = $this->createConnection($dsn, $config, $options); // 创建一个新的PDO连接

        if (! empty($config['database'])) {
            $connection->exec("use `{$config['database']}`;");
        }
        //设置连接字符集和排序
        $this->configureEncoding($connection, $config);

        // Next, we will check to see if a timezone has been specified in this config
        // and if it has we will issue a statement to modify the timezone with the
        // database. Setting this DB timezone is an optional configuration item.
        //
        // 接下来，我们将检查是否在这个配置中指定了一个时区，如果它有的话，我们将发出一个语句来修改带数据库的时区
        // 设置这个DB timezone是一个可选的配置项
        //
        //     在连接上设置时区
        $this->configureTimezone($connection, $config);
        //为连接设置模式
        $this->setModes($connection, $config);

        return $connection;
    }

    /**
     * Set the connection character set and collation.
     *
     * 设置连接字符集和排序
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureEncoding($connection, array $config)
    {
        if (! isset($config['charset'])) {
            return $connection;
        }

        $connection->prepare(
            //                                  获取连接配置
            "set names '{$config['charset']}'".$this->getCollation($config)
        )->execute();
    }

    /**
     * Get the collation for the configuration.
     *
     * 获取连接配置
     *
     * @param  array  $config
     * @return string
     */
    protected function getCollation(array $config)
    {
        return ! is_null($config['collation']) ? " collate '{$config['collation']}'" : '';
    }

    /**
     * Set the timezone on the connection.
     *
     * 在连接上设置时区
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureTimezone($connection, array $config)
    {
        if (isset($config['timezone'])) {
            $connection->prepare('set time_zone="'.$config['timezone'].'"')->execute();
        }
    }

    /**
     * Create a DSN string from a configuration.
	 *
	 * 从DSN字符串创建一个配置
	 *
     * Chooses socket or host/port based on the 'unix_socket' config value.
	 *
	 * 选择socket或主机/端口基于“unix_socket”的配置值
     *
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        return $this->hasSocket($config) //确定给定的配置数组是否具有UNIX套接字值
                            ? $this->getSocketDsn($config)//获取套接字配置的DSN字符串
                            : $this->getHostDsn($config); // 从DSN字符串得到一个主机/端口配置
    }

    /**
     * Determine if the given configuration array has a UNIX socket value.
     *
     * 确定给定的配置数组是否具有UNIX套接字值
     *
     * @param  array  $config
     * @return bool
     */
    protected function hasSocket(array $config)
    {
        return isset($config['unix_socket']) && ! empty($config['unix_socket']);
    }

    /**
     * Get the DSN string for a socket configuration.
     *
     * 获取套接字配置的DSN字符串
     *
     * @param  array  $config
     * @return string
     */
    protected function getSocketDsn(array $config)
    {
        return "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
    }

    /**
     * Get the DSN string for a host / port configuration.
	 *
	 * 从DSN字符串得到一个主机/端口配置
     *
     * @param  array  $config
     * @return string
     */
    protected function getHostDsn(array $config)
    {
        extract($config, EXTR_SKIP);

        return isset($port)
                    ? "mysql:host={$host};port={$port};dbname={$database}"
                    : "mysql:host={$host};dbname={$database}";
    }

    /**
     * Set the modes for the connection.
     *
     * 为连接设置模式
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function setModes(PDO $connection, array $config)
    {
        if (isset($config['modes'])) {
            //在连接上设置自定义模式
            $this->setCustomModes($connection, $config);
        } elseif (isset($config['strict'])) {
            if ($config['strict']) {
                //                      获取该查询以启用严格模式
                $connection->prepare($this->strictMode())->execute();
            } else {
                $connection->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
            }
        }
    }

    /**
     * Set the custom modes on the connection.
     *
     * 在连接上设置自定义模式
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function setCustomModes(PDO $connection, array $config)
    {
        $modes = implode(',', $config['modes']);

        $connection->prepare("set session sql_mode='{$modes}'")->execute();
    }

    /**
     * Get the query to enable strict mode.
     *
     * 获取该查询以启用严格模式
     *
     * @return string
     */
    protected function strictMode()
    {
        return "set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'";
    }
}
