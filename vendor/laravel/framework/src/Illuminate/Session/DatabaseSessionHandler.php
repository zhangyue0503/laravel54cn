<?php

namespace Illuminate\Session;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use SessionHandlerInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\QueryException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Container\Container;

class DatabaseSessionHandler implements SessionHandlerInterface, ExistenceAwareInterface
{
    /**
     * The database connection instance.
     *
     * 数据库链接实例
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The name of the session table.
     *
     * 会话表的名称
     *
     * @var string
     */
    protected $table;

    /**
     * The number of minutes the session should be valid.
     *
     * 在缓存中存储数据的分钟数
     *
     * @var int
     */
    protected $minutes;

    /**
     * The container instance.
     *
     * 容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The existence state of the session.
     *
     * 会话的存在状态
     *
     * @var bool
     */
    protected $exists;

    /**
     * Create a new database session handler instance.
     *
     * 创建一个新的数据库会话处理程序实例
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  string  $table
     * @param  int  $minutes
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     * @return void
     */
    public function __construct(ConnectionInterface $connection, $table, $minutes, Container $container = null)
    {
        $this->table = $table;
        $this->minutes = $minutes;
        $this->container = $container;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        //              为表获取一个新的查询构建器实例 通过ID执行单个记录的查询
        $session = (object) $this->getQuery()->find($sessionId);
        //确定会话是否已过期
        if ($this->expired($session)) {
            $this->exists = true;

            return;
        }

        if (isset($session->payload)) {
            $this->exists = true;

            return base64_decode($session->payload);
        }
    }

    /**
     * Determine if the session is expired.
     *
     * 确定会话是否已过期
     *
     * @param  \StdClass  $session
     * @return bool
     */
    protected function expired($session)
    {
        return isset($session->last_activity) &&
            //               获取当前日期和时间的Carbon实例   从实例中删除几分钟
            $session->last_activity < Carbon::now()->subMinutes($this->minutes)->getTimestamp();
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        //          获得会话的默认有效负载
        $payload = $this->getDefaultPayload($data);

        if (! $this->exists) {
            $this->read($sessionId);
        }

        if ($this->exists) {
            //对会话ID执行更新操作
            $this->performUpdate($sessionId, $payload);
        } else {
            //在会话ID上执行插入操作
            $this->performInsert($sessionId, $payload);
        }

        return $this->exists = true;
    }

    /**
     * Perform an insert operation on the session ID.
     *
     * 在会话ID上执行插入操作
     *
     * @param  string  $sessionId
     * @param  string  $payload
     * @return void
     */
    protected function performInsert($sessionId, $payload)
    {
        try {
            //为表获取一个新的查询构建器实例  将新记录插入数据库   如果没有给定key的方法，整个数组将被替换
            return $this->getQuery()->insert(Arr::set($payload, 'id', $sessionId));
        } catch (QueryException $e) {
            //对会话ID执行更新操作
            $this->performUpdate($sessionId, $payload);
        }
    }

    /**
     * Perform an update operation on the session ID.
     *
     * 对会话ID执行更新操作
     *
     * @param  string  $sessionId
     * @param  string  $payload
     * @return int
     */
    protected function performUpdate($sessionId, $payload)
    {
        //为表获取一个新的查询构建器实例 将基本WHERE子句添加到查询中  更新数据库中的记录
        return $this->getQuery()->where('id', $sessionId)->update($payload);
    }

    /**
     * Get the default payload for the session.
     *
     * 获得会话的默认有效负载
     *
     * @param  string  $data
     * @return array
     */
    protected function getDefaultPayload($data)
    {
        $payload = [
            'payload' => base64_encode($data),
            //              获取当前日期和时间的Carbon实例
            'last_activity' => Carbon::now()->getTimestamp(),
        ];

        if (! $this->container) {
            return $payload;
        }

        return tap($payload, function (&$payload) {
            $this->addUserInformation($payload)//将用户信息添加到会话有效负载中
                 ->addRequestInformation($payload);//将请求信息添加到会话有效负载中
        });
    }

    /**
     * Add the user information to the session payload.
     *
     * 将用户信息添加到会话有效负载中
     *
     * @param  array  $payload
     * @return $this
     */
    protected function addUserInformation(&$payload)
    {
        //确定给定的抽象类型是否已绑定
        if ($this->container->bound(Guard::class)) {
            $payload['user_id'] = $this->userId();//获取当前经过身份验证的用户ID
        }

        return $this;
    }

    /**
     * Get the currently authenticated user's ID.
     *
     * 获取当前经过身份验证的用户ID
     *
     * @return mixed
     */
    protected function userId()
    {
        //              从容器中解析给定类型
        return $this->container->make(Guard::class)->id();
    }

    /**
     * Add the request information to the session payload.
     *
     * 将请求信息添加到会话有效负载中
     *
     * @param  array  $payload
     * @return $this
     */
    protected function addRequestInformation(&$payload)
    {
        //确定给定的抽象类型是否已绑定
        if ($this->container->bound('request')) {
            $payload = array_merge($payload, [
                'ip_address' => $this->ipAddress(),//获取当前请求的IP地址
                'user_agent' => $this->userAgent(),//获取当前请求的用户代理
            ]);
        }

        return $this;
    }

    /**
     * Get the IP address for the current request.
     *
     * 获取当前请求的IP地址
     *
     * @return string
     */
    protected function ipAddress()
    {
        //从容器中解析给定类型
        return $this->container->make('request')->ip();
    }

    /**
     * Get the user agent for the current request.
     *
     * 获取当前请求的用户代理
     *
     * @return string
     */
    protected function userAgent()
    {
        //                              从容器中解析给定类型
        return substr((string) $this->container->make('request')->header('User-Agent'), 0, 500);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        //为表获取一个新的查询构建器实例 将基本WHERE子句添加到查询中 从数据库中删除记录
        $this->getQuery()->where('id', $sessionId)->delete();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        //为表获取一个新的查询构建器实例 将基本WHERE子句添加到查询中     获取当前日期和时间的Carbon实例       从数据库中删除记录
        $this->getQuery()->where('last_activity', '<=', Carbon::now()->getTimestamp() - $lifetime)->delete();
    }

    /**
     * Get a fresh query builder instance for the table.
     *
     * 为表获取一个新的查询构建器实例
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getQuery()
    {
        //                  对数据库表开始一个链式的查询
        return $this->connection->table($this->table);
    }

    /**
     * Set the existence state for the session.
     *
     * 设置会话的存在状态
     *
     * @param  bool  $value
     * @return $this
     */
    public function setExists($value)
    {
        $this->exists = $value;

        return $this;
    }
}
