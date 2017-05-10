<?php

namespace Illuminate\Database\Connectors;

use InvalidArgumentException;

class SQLiteConnector extends Connector implements ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * 建立数据库连接
     *
     * @param  array  $config
     * @return \PDO
     *
     * @throws \InvalidArgumentException
     */
    public function connect(array $config)
    {
        //根据配置获得PDO选项
        $options = $this->getOptions($config);

        // SQLite supports "in-memory" databases that only last as long as the owning
        // connection does. These are useful for tests or for short lifetime store
        // querying. In-memory databases may only have a single open connection.
        //
        // SQLite支持“内存”数据库,只有最后只要拥有连接
        // 这些对于测试或短时间存储查询都很有用。内存数据库可能只有一个开放的连接
        //
        if ($config['database'] == ':memory:') {
            //创建一个新的PDO连接
            return $this->createConnection('sqlite::memory:', $config, $options);
        }

        $path = realpath($config['database']);

        // Here we'll verify that the SQLite database exists before going any further
        // as the developer probably wants to know if the database exists and this
        // SQLite driver will not throw any exception if it does not by default.
        //
        // 这里我们将验证SQLite数据库存在之前任何进一步的开发人员可能想知道如果存在这SQLite数据库驱动程序不会抛出任何异常如果没有默认情况下
        //
        if ($path === false) {
            throw new InvalidArgumentException("Database (${config['database']}) does not exist.");
        }
        //创建一个新的PDO连接
        return $this->createConnection("sqlite:{$path}", $config, $options);
    }
}
