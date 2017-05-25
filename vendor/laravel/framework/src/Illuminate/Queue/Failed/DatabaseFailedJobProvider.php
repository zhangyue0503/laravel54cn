<?php

namespace Illuminate\Queue\Failed;

use Carbon\Carbon;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseFailedJobProvider implements FailedJobProviderInterface
{
    /**
     * The connection resolver implementation.
     *
     * 连接解析实现
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The database connection name.
     *
     * 数据库连接名
     *
     * @var string
     */
    protected $database;

    /**
     * The database table.
     *
     * 数据库表
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new database failed job provider.
     *
     * 创建一个新的数据库失败的作业提供者
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $database
     * @param  string  $table
     * @return void
     */
    public function __construct(ConnectionResolverInterface $resolver, $database, $table)
    {
        $this->table = $table;
        $this->resolver = $resolver;
        $this->database = $database;
    }

    /**
     * Log a failed job into storage.
     *
     * 将一个失败的作业记录到存储中
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  \Exception  $exception
     * @return int|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        $failed_at = Carbon::now();//获取当前日期和时间的Carbon实例

        $exception = (string) $exception;
        //为表获取一个新的查询构建器实例  插入新记录并获取主键的值
        return $this->getTable()->insertGetId(compact(
            'connection', 'queue', 'payload', 'exception', 'failed_at'
        ));
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * 列出所有失败的工作
     *
     * @return array
     */
    public function all()
    {
        //为表获取一个新的查询构建器实例 向查询添加一个“order by”子句 将查询执行为“SELECT”语句  获取集合中的所有项目
        return $this->getTable()->orderBy('id', 'desc')->get()->all();
    }

    /**
     * Get a single failed job.
     *
     * 获取一个失败的工作
     *
     * @param  mixed  $id
     * @return array
     */
    public function find($id)
    {
        //为表获取一个新的查询构建器实例  通过ID执行单个记录的查询
        return $this->getTable()->find($id);
    }

    /**
     * Delete a single failed job from storage.
     *
     * 从存储中删除一个失败的作业
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        //为表获取一个新的查询构建器实例   将基本WHERE子句添加到查询中    从数据库中删除记录
        return $this->getTable()->where('id', $id)->delete() > 0;
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * 从存储中删除一个失败的作业
     *
     * @return void
     */
    public function flush()
    {
        //为表获取一个新的查询构建器实例  从数据库中删除记录
        $this->getTable()->delete();
    }

    /**
     * Get a new query builder instance for the table.
     *
     * 为表获取一个新的查询构建器实例
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getTable()
    {
        //                    获取一个数据连接实例            对数据库表开始一个链式的查询
        return $this->resolver->connection($this->database)->table($this->table);
    }
}
