<?php

namespace Illuminate\Database\Connectors;

use PDO;

class PostgresConnector extends Connector implements ConnectorInterface
{
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
        // First we'll create the basic DSN and connection instance connecting to the
        // using the configuration option specified by the developer. We will also
        // set the default character set on the connections to UTF-8 by default.
        //
        // 首先，我们将创建使用开发人员指定的配置选项连接到使用的基本DSN和连接实例
        // 我们还将默认设置默认字符集，默认设置为UTF-8
        //
        //创建一个新的PDO连接
        $connection = $this->createConnection(
            //从配置中创建一个DSN字符串              根据配置获得PDO选项
            $this->getDsn($config), $config, $this->getOptions($config)
        );
        //设置连接字符集和排序
        $this->configureEncoding($connection, $config);

        // Next, we will check to see if a timezone has been specified in this config
        // and if it has we will issue a statement to modify the timezone with the
        // database. Setting this DB timezone is an optional configuration item.
        //
        // 接下来，我们将检查是否在这个配置中指定了一个时区，如果它有的话，我们将发出一个语句来修改带数据库的时区
        // 设置这个DB timezone是一个可选的配置项
        //
        //在连接上设置时区
        $this->configureTimezone($connection, $config);
        //在连接上设置模式
        $this->configureSchema($connection, $config);

        // Postgres allows an application_name to be set by the user and this name is
        // used to when monitoring the application with pg_stat_activity. So we'll
        // determine if the option has been specified and run a statement if so.
        //
        // Postgres允许用户设定的一个application_name这个名字是用来当pg_stat_activity监视应用程序
        // 因此，我们将确定是否已经指定了该选项并运行一个语句
        //
        //   在连接上设置模式
        $this->configureApplicationName($connection, $config);

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
    protected function configureEncoding($connection, $config)
    {
        $charset = $config['charset'];

        $connection->prepare("set names '$charset'")->execute();
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
            $timezone = $config['timezone'];

            $connection->prepare("set time zone '{$timezone}'")->execute();
        }
    }

    /**
     * Set the schema on the connection.
     *
     * 在连接上设置模式
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureSchema($connection, $config)
    {
        if (isset($config['schema'])) {
            //为DSN格式化模式
            $schema = $this->formatSchema($config['schema']);

            $connection->prepare("set search_path to {$schema}")->execute();
        }
    }

    /**
     * Format the schema for the DSN.
     *
     * 为DSN格式化模式
     *
     * @param  array|string  $schema
     * @return string
     */
    protected function formatSchema($schema)
    {
        if (is_array($schema)) {
            return '"'.implode('", "', $schema).'"';
        } else {
            return '"'.$schema.'"';
        }
    }

    /**
     * Set the schema on the connection.
     *
     * 在连接上设置模式
     *
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function configureApplicationName($connection, $config)
    {
        if (isset($config['application_name'])) {
            $applicationName = $config['application_name'];

            $connection->prepare("set application_name to '$applicationName'")->execute();
        }
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
        extract($config, EXTR_SKIP);

        $host = isset($host) ? "host={$host};" : '';

        $dsn = "pgsql:{$host}dbname={$database}";

        // If a port was specified, we will add it to this Postgres DSN connections
        // format. Once we have done that we are ready to return this connection
        // string back out for usage, as this has been fully constructed here.
        //
        // 如果指定一个端口,我们将把它添加到这个Postgres DSN连接格式
        // 一旦我们完成了这个操作，我们就可以将这个连接字符串返回给使用了，因为这里已经完全构造好了
        //
        if (isset($config['port'])) {
            $dsn .= ";port={$port}";
        }
        //向DSN添加SSL选项
        return $this->addSslOptions($dsn, $config);
    }

    /**
     * Add the SSL options to the DSN.
     *
     * 向DSN添加SSL选项
     *
     * @param  string  $dsn
     * @param  array  $config
     * @return string
     */
    protected function addSslOptions($dsn, array $config)
    {
        foreach (['sslmode', 'sslcert', 'sslkey', 'sslrootcert'] as $option) {
            if (isset($config[$option])) {
                $dsn .= ";{$option}={$config[$option]}";
            }
        }

        return $dsn;
    }
}
