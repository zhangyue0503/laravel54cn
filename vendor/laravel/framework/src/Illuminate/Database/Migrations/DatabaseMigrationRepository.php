<?php

namespace Illuminate\Database\Migrations;

use Illuminate\Database\ConnectionResolverInterface as Resolver;

class DatabaseMigrationRepository implements MigrationRepositoryInterface
{
    /**
     * The database connection resolver instance.
     *
     * 数据库连接解析实例
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the migration table.
     *
     * 迁移表名
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the database connection to use.
     *
     * 要使用的数据库连接的名称
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new database migration repository instance.
     *
     * 创建新的数据库迁移库实例
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $table
     * @return void
     */
    public function __construct(Resolver $resolver, $table)
    {
        $this->table = $table;
        $this->resolver = $resolver;
    }

    /**
     * Get the ran migrations.
     *
     * 获取运行的迁移
     *
     * @return array
     */
    public function getRan()
    {
        //           为迁移表获取一个查询构建器
        return $this->table()
                ->orderBy('batch', 'asc')//向查询添加一个“order by”子句
                ->orderBy('migration', 'asc')
                ->pluck('migration')->all();//用给定列的值获取数组->获取集合中的所有项目
    }

    /**
     * Get list of migrations.
     *
     * 获取迁移列表
     *
     * @param  int  $steps
     * @return array
     */
    public function getMigrations($steps)
    {
        //      为迁移表获取一个查询构建器->将基本WHERE子句添加到查询中
        $query = $this->table()->where('batch', '>=', '1');
        //向查询添加一个“order by”子句->别名设置查询的“limit”值->将查询执行为“SELECT”语句->获取集合中的所有项目
        return $query->orderBy('migration', 'desc')->take($steps)->get()->all();
    }

    /**
     * Get the last migration batch.
     *
     * 最后一次迁移
     *
     * @return array
     */
    public function getLast()
    {
        //      为迁移表获取一个查询构建器->将基本WHERE子句添加到查询中(,获得最后一个迁移批号)
        $query = $this->table()->where('batch', $this->getLastBatchNumber());
        //向查询添加一个“order by”子句->将查询执行为“SELECT”语句->获取集合中的所有项目
        return $query->orderBy('migration', 'desc')->get()->all();
    }

    /**
     * Log that a migration was run.
     *
     * 记录迁移运行的日志
     *
     * @param  string  $file
     * @param  int     $batch
     * @return void
     */
    public function log($file, $batch)
    {
        $record = ['migration' => $file, 'batch' => $batch];
        //      为迁移表获取一个查询构建器->将新记录插入数据库
        $this->table()->insert($record);
    }

    /**
     * Remove a migration from the log.
     *
     * 从日志中删除一个迁移
     *
     * @param  object  $migration
     * @return void
     */
    public function delete($migration)
    {
        //    为迁移表获取一个查询构建器->将基本WHERE子句添加到查询中->从数据库中删除记录
        $this->table()->where('migration', $migration->migration)->delete();
    }

    /**
     * Get the next migration batch number.
     *
     * 获得下一个迁移批号
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        //获得最后一个迁移批号
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last migration batch number.
     *
     * 获得最后一个迁移批号
     *
     * @return int
     */
    public function getLastBatchNumber()
    {
        //    为迁移表获取一个查询构建器->检索给定列的最大值
        return $this->table()->max('batch');
    }

    /**
     * Create the migration repository data store.
     *
     * 创建迁移存储库数据存储
     *
     * @return void
     */
    public function createRepository()
    {
        //解析数据库连接实例->获取连接的架构生成器实例
        $schema = $this->getConnection()->getSchemaBuilder();
        //在模式上创建一个新表
        $schema->create($this->table, function ($table) {
            // The migrations table is responsible for keeping track of which of the
            // migrations have actually run for the application. We'll create the
            // table to hold the migration file's path as well as the batch ID.
            //
            // 迁移表负责跟踪哪些迁移实际上是为应用程序运行的
            // 我们将创建表来保存迁移文件的路径以及批处理ID
            //
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });
    }

    /**
     * Determine if the migration repository exists.
     *
     * 确定迁移存储库是否存在
     *
     * @return bool
     */
    public function repositoryExists()
    {
        //解析数据库连接实例                    获取连接的架构生成器实例
        $schema = $this->getConnection()->getSchemaBuilder();
        //确定给定的表是否存在
        return $schema->hasTable($this->table);
    }

    /**
     * Get a query builder for the migration table.
     *
     * 为迁移表获取一个查询构建器
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        //解析数据库连接实例->对数据库表开始一个流式查询
        return $this->getConnection()->table($this->table);
    }

    /**
     * Get the connection resolver instance.
     *
     * 获取连接的解析实例
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public function getConnectionResolver()
    {
        return $this->resolver;
    }

    /**
     * Resolve the database connection instance.
     *
     * 解析数据库连接实例
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        //                  获取一个数据连接实例
        return $this->resolver->connection($this->connection);
    }

    /**
     * Set the information source to gather data.
     *
     * 设置信息源以收集数据
     *
     * @param  string  $name
     * @return void
     */
    public function setSource($name)
    {
        $this->connection = $name;
    }
}
