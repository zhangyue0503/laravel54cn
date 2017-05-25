<?php

namespace Illuminate\Queue;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Jobs\DatabaseJobRecord;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class DatabaseQueue extends Queue implements QueueContract
{
    /**
     * The database connection instance.
     *
     * 数据库连接实例
     *
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * The database table that holds the jobs.
     *
     * 保存作业的数据库表
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the default queue.
     *
     * 默认队列的名称
     *
     * @var string
     */
    protected $default;

    /**
     * The expiration time of a job.
     *
     * 工作的截止时间
     *
     * @var int|null
     */
    protected $retryAfter = 60;

    /**
     * Create a new database queue instance.
     *
     * 创建一个新的数据库队列实例
     *
     * @param  \Illuminate\Database\Connection  $database
     * @param  string  $table
     * @param  string  $default
     * @param  int  $retryAfter
     * @return void
     */
    public function __construct(Connection $database, $table, $default = 'default', $retryAfter = 60)
    {
        $this->table = $table;
        $this->default = $default;
        $this->database = $database;
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get the size of the queue.
     *
     * 获取队列的大小
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        //                   对数据库表开始一个流式查询
        return $this->database->table($this->table)
            //将基本WHERE子句添加到查询中      获取队列或返回默认值
                    ->where('queue', $this->getQueue($queue))
                    ->count();//检索查询的“count”结果
    }

    /**
     * Push a new job onto the queue.
	 *
	 * 将新工作推到队列上
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
		//根据给定的延迟时间推送一个原始的消息载荷到数据库(通过给定的消息和数据创建一个载荷字符串)
        return $this->pushToDatabase($queue, $this->createPayload($job, $data));
    }

    /**
     * Push a raw payload onto the queue.
     *
     * 将原始有效负载推到队列中
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        //根据给定的延迟时间推送一个原始的消息载荷到数据库
        return $this->pushToDatabase($queue, $payload);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * 在延迟之后将新作业推到队列上
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return void
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        //根据给定的延迟时间推送一个原始的消息载荷到数据库    从给定的作业和数据创建有效载荷字符串
        return $this->pushToDatabase($queue, $this->createPayload($job, $data), $delay);
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * 将一系列作业推到队列中
     *
     * @param  array   $jobs
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        $queue = $this->getQueue($queue);//获取队列或返回默认值

        $availableAt = $this->availableAt();//获取“available at”UNIX时间戳
        //            对数据库表开始一个流式查询          将新记录插入数据库               在每个项目上运行map
        return $this->database->table($this->table)->insert(collect((array) $jobs)->map(
            function ($job) use ($queue, $data, $availableAt) {
                //        创建要为给定作业插入的数组              从给定的作业和数据创建有效载荷字符串
                return $this->buildDatabaseRecord($queue, $this->createPayload($job, $data), $availableAt);
            }
        )->all());//获取集合中的所有项目
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * 将保留的作业放回队列中
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\DatabaseJobRecord  $job
     * @param  int  $delay
     * @return mixed
     */
    public function release($queue, $job, $delay)
    {
        //根据给定的延迟时间推送一个原始的消息载荷到数据库
        return $this->pushToDatabase($queue, $job->payload, $delay, $job->attempts);
    }

    /**
     * Push a raw payload to the database with a given delay.
	 *
	 * 将原始有效载荷推送到给定延迟的数据库
	 * * 根据给定的延迟时间推送一个原始的消息载荷到数据库
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  \DateTime|int  $delay
     * @param  int  $attempts
     * @return mixed
     */
    protected function pushToDatabase($queue, $payload, $delay = 0, $attempts = 0)
    {
		//对数据库表开始一个流式查询->插入新记录并获取主键的值(创建要为给定作业插入的数组())
        return $this->database->table($this->table)->insertGetId($this->buildDatabaseRecord(
        	//获取队列或返回默认值     载荷         获取“available at”UNIX时间戳
            $this->getQueue($queue), $payload, $this->availableAt($delay), $attempts
        ));
    }

    /**
     * Create an array to insert for the given job.
	 *
	 * 创建要为给定作业插入的数组
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  int  $availableAt
     * @param  int  $attempts
     * @return array
     */
    protected function buildDatabaseRecord($queue, $payload, $availableAt, $attempts = 0)
    {
        return [
            'queue' => $queue,
            'payload' => $payload,
            'attempts' => $attempts,
            'reserved_at' => null,
            'available_at' => $availableAt,
            'created_at' => $this->currentTime(),//将当前系统时间作为UNIX时间戳
        ];
    }

    /**
     * Pop the next job off of the queue.
	 *
	 * 弹出队列的下一个作业
	 * * 从队列中抛出下一个消息
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);//获取队列或返回默认值

        $this->database->beginTransaction();//启动新的数据库事务

        if ($job = $this->getNextAvailableJob($queue)) { //获取队列的下一个可用工作
            return $this->marshalJob($queue, $job);//排列保留的工作进入DatabaseJob实例
        }

        $this->database->commit();//提交活动的数据库事务
    }

    /**
     * Get the next available job for the queue.
	 *
	 * 获取队列的下一个可用工作
     *
     * @param  string|null  $queue
     * @return \Illuminate\Queue\Jobs\DatabaseJobRecord|null
     */
    protected function getNextAvailableJob($queue)
    {
        //对数据库表开始一个流式查询
        $job = $this->database->table($this->table)
                    ->lockForUpdate()//锁定表中选定的行进行更新
                    ->where('queue', $this->getQueue($queue))//将基本WHERE子句添加到查询中(,获取队列或返回默认值)
                    ->where(function ($query) {
                        $this->isAvailable($query);//修改查询以检查可用的作业
                        $this->isReservedButExpired($query);//修改查询以检查已保留但已过期的作业
                    })
                    ->orderBy('id', 'asc')//向查询添加一个“order by”子句
                    ->first();//执行查询和得到的第一个结果
        //              创建一个新的工作记录实例
        return $job ? new DatabaseJobRecord((object) $job) : null;
    }

    /**
     * Modify the query to check for available jobs.
     *
     * 修改查询以检查可用的作业
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    protected function isAvailable($query)
    {
        $query->where(function ($query) {//将基本WHERE子句添加到查询中
            $query->whereNull('reserved_at')//向查询添加“where null”子句
                  ->where('available_at', '<=', $this->currentTime());//将当前系统时间作为UNIX时间戳
        });
    }

    /**
     * Modify the query to check for jobs that are reserved but have expired.
     *
     * 修改查询以检查已保留但已过期的作业
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    protected function isReservedButExpired($query)
    {
        //     获取当前日期和时间的Carbon实例  从实例中删除秒
        $expiration = Carbon::now()->subSeconds($this->retryAfter)->getTimestamp();
        //向查询添加“or where”子句
        $query->orWhere(function ($query) use ($expiration) {
            //将基本WHERE子句添加到查询中
            $query->where('reserved_at', '<=', $expiration);
        });
    }

    /**
     * Marshal the reserved job into a DatabaseJob instance.
	 *
	 * 排列保留的工作进入DatabaseJob实例
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\DatabaseJobRecord  $job
     * @return \Illuminate\Queue\Jobs\DatabaseJob
     */
    protected function marshalJob($queue, $job)
    {
        $job = $this->markJobAsReserved($job);//将给定的消息ID标记为保留

        $this->database->commit();//提交活动的数据库事务
        //创建一个新的工作实例
        return new DatabaseJob(
            $this->container, $this, $job, $this->connectionName, $queue
        );
    }

    /**
     * Mark the given job ID as reserved.
	 *
	 * 将给定的作业标识标记为保留
	 * * 将给定的消息ID标记为保留
     *
     * @param  \Illuminate\Queue\Jobs\DatabaseJobRecord  $job
     * @return \Illuminate\Queue\Jobs\DatabaseJobRecord
     */
    protected function markJobAsReserved($job)
    {
        //对数据库表开始一个流式查询        将基本WHERE子句添加到查询中          更新数据库中的记录
        $this->database->table($this->table)->where('id', $job->id)->update([
            'reserved_at' => $job->touch(),//更新作业的“reserved at”时间戳
            'attempts' => $job->increment(),//增加工作尝试次数的次数
        ]);

        return $job;
    }

    /**
     * Delete a reserved job from the queue.
     *
     * 从队列中删除一个保留的作业
     *
     * @param  string  $queue
     * @param  string  $id
     * @return void
     */
    public function deleteReserved($queue, $id)
    {
        $this->database->beginTransaction();//启动新的数据库事务
        //对数据库表开始一个流式查询                锁定表中选定的行进行更新   通过ID执行单个记录的查询
        if ($this->database->table($this->table)->lockForUpdate()->find($id)) {
            //                             将基本WHERE子句添加到查询中     从数据库中删除记录
            $this->database->table($this->table)->where('id', $id)->delete();
        }
        //           提交活动的数据库事务
        $this->database->commit();
    }

    /**
     * Get the queue or return the default.
	 *
	 * 获取队列或返回默认值
	 * * 如果队列参数为空，则获取默认值
     *
     * @param  string|null  $queue
     * @return string
     */
    protected function getQueue($queue)
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the underlying database instance.
     *
     * 获取底层数据库实例
     *
     * @return \Illuminate\Database\Connection
     */
    public function getDatabase()
    {
        return $this->database;
    }
}
