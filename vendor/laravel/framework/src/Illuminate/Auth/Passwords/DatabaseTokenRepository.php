<?php

namespace Illuminate\Auth\Passwords;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class DatabaseTokenRepository implements TokenRepositoryInterface
{
    /**
     * The database connection instance.
     *
     * 数据库连接实例
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The Hasher implementation.
     *
     * Hasher的实现
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * The token database table.
     *
     * 令牌数据库表
     *
     * @var string
     */
    protected $table;

    /**
     * The hashing key.
     *
     * 散列键
     *
     * @var string
     */
    protected $hashKey;

    /**
     * The number of seconds a token should last.
     *
     * 一个令牌应该持续的秒数
     *
     * @var int
     */
    protected $expires;

    /**
     * Create a new token repository instance.
     *
     * 创建一个新的令牌存储库实例
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @param  string  $table
     * @param  string  $hashKey
     * @param  int  $expires
     * @return void
     */
    public function __construct(ConnectionInterface $connection, HasherContract $hasher,
                                $table, $hashKey, $expires = 60)
    {
        $this->table = $table;
        $this->hasher = $hasher;
        $this->hashKey = $hashKey;
        $this->expires = $expires * 60;
        $this->connection = $connection;
    }

    /**
     * Create a new token record.
     *
     * 创建一个新的令牌记录
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return string
     */
    public function create(CanResetPasswordContract $user)
    {
        //获取密码重置链接的电子邮件地址
        $email = $user->getEmailForPasswordReset();
        //从数据库中删除所有现有的重置令牌
        $this->deleteExisting($user);

        // We will create a new, random token for the user so that we can e-mail them
        // a safe link to the password reset form. Then we will insert a record in
        // the database so that we can verify the token within the actual reset.
        //
        // 我们将为用户创建一个新的、随机的令牌，以便我们可以给他们发送一个安全的密码重置表单的链接
        // 然后，我们将在数据库中插入一个记录，这样我们就可以在实际的重置中验证这个令牌
        //
        //            为用户创建一个新令牌
        $token = $this->createNewToken();
        //对表启动一个新的数据库查询->将新记录插入数据库(为表构建记录有效载荷)
        $this->getTable()->insert($this->getPayload($email, $token));

        return $token;
    }

    /**
     * Delete all existing reset tokens from the database.
     *
     * 从数据库中删除所有现有的重置令牌
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return int
     */
    protected function deleteExisting(CanResetPasswordContract $user)
    {
        //对表启动一个新的数据库查询->将基本WHERE子句添加到查询中(,获取密码重置链接的电子邮件地址)->从数据库中删除记录
        return $this->getTable()->where('email', $user->getEmailForPasswordReset())->delete();
    }

    /**
     * Build the record payload for the table.
     *
     * 为表构建记录有效载荷
     *
     * @param  string  $email
     * @param  string  $token
     * @return array
     */
    protected function getPayload($email, $token)
    {
        //                                                  哈希给定的值
        return ['email' => $email, 'token' => $this->hasher->make($token), 'created_at' => new Carbon];
    }

    /**
     * Determine if a token record exists and is valid.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $token
     * @return bool
     */
    public function exists(CanResetPasswordContract $user, $token)
    {
        //对表启动一个新的数据库查询->将基本WHERE子句添加到查询中
        $record = (array) $this->getTable()->where(
            //             获取密码重置链接的电子邮件地址
            'email', $user->getEmailForPasswordReset()
        )->first();//执行查询和得到的第一个结果

        return $record &&
                //确定该令牌是否已过期
               ! $this->tokenExpired($record['created_at']) &&
                //检查给定的普通值与散列值
                 $this->hasher->check($token, $record['token']);
    }

    /**
     * Determine if the token has expired.
     *
     * 确定该令牌是否已过期
     *
     * @param  string  $createdAt
     * @return bool
     */
    protected function tokenExpired($createdAt)
    {
        //         从字符串中创建一个carbon实例    在实例中添加秒          确定实例是否在过去，例如。比现在少(之前)
        return Carbon::parse($createdAt)->addSeconds($this->expires)->isPast();
    }

    /**
     * Delete a token record by user.
     *
     * 删除用户的令牌记录
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @return void
     */
    public function delete(CanResetPasswordContract $user)
    {
        //从数据库中删除所有现有的重置令牌
        $this->deleteExisting($user);
    }

    /**
     * Delete expired tokens.
     *
     * 删除过期的令牌
     *
     * @return void
     */
    public function deleteExpired()
    {
        //     获取当前日期和时间的Carbon实例  从实例中删除秒
        $expiredAt = Carbon::now()->subSeconds($this->expires);
        //对表启动一个新的数据库查询->将基本WHERE子句添加到查询中->从数据库中删除记录
        $this->getTable()->where('created_at', '<', $expiredAt)->delete();
    }

    /**
     * Create a new token for the user.
     *
     * 为用户创建一个新令牌
     *
     * @return string
     */
    public function createNewToken()
    {
        //                           生成一个更真实的“随机”alpha数字字符串
        return hash_hmac('sha256', Str::random(40), $this->hashKey);
    }

    /**
     * Get the database connection instance.
     *
     * 获取数据库连接实例
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Begin a new database query against the table.
     *
     * 对表启动一个新的数据库查询
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getTable()
    {
        //                         对数据库表开始一个链式的查询
        return $this->connection->table($this->table);
    }

    /**
     * Get the hasher instance.
     *
     * 获取hasher实例
     *
     * @return \Illuminate\Contracts\Hashing\Hasher
     */
    public function getHasher()
    {
        return $this->hasher;
    }
}
