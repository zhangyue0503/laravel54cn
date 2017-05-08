<?php

namespace Illuminate\Auth;

use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class DatabaseUserProvider implements UserProvider
{
    /**
     * The active database connection.
     *
     * 活动数据库连接
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $conn;

    /**
     * The hasher implementation.
     *
     * hasher实现
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * The table containing the users.
     *
     * 包含用户的表
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new database user provider.
     *
     * 创建一个新的数据库用户提供者
     *
     * @param  \Illuminate\Database\ConnectionInterface  $conn
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @param  string  $table
     * @return void
     */
    public function __construct(ConnectionInterface $conn, HasherContract $hasher, $table)
    {
        $this->conn = $conn;
        $this->table = $table;
        $this->hasher = $hasher;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * 通过惟一标识符检索用户
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        //         对数据库表开始一个链式的查询->通过ID执行单个记录的查询
        $user = $this->conn->table($this->table)->find($identifier);
        //获得通用用户
        return $this->getGenericUser($user);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * 通过其唯一标识符检索用户并“记住我”令牌
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        $user = $this->conn->table($this->table)//对数据库表开始一个链式的查询
                        ->where('id', $identifier)//将基本WHERE子句添加到查询中
                        ->where('remember_token', $token)//将基本WHERE子句添加到查询中
                        ->first();//执行查询和得到的第一个结果
        //        获得通用用户
        return $this->getGenericUser($user);
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
        $this->conn->table($this->table)//对数据库表开始一个链式的查询
                ->where('id', $user->getAuthIdentifier())//将基本WHERE子句添加到查询中(,获取用户的唯一标识符)
                ->update(['remember_token' => $token]);//更新数据库中的记录
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * 根据给定的凭证检索用户
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // generic "user" object that will be utilized by the Guard instances.
        //
        // 首先，我们将向查询中添加每个凭据元素作为where子句
        // 然后，我们可以执行查询，如果我们找到一个用户，将它返回到一个通用的“user”对象中，该对象将被警卫实例使用
        //
        $query = $this->conn->table($this->table);//对数据库表开始一个链式的查询

        foreach ($credentials as $key => $value) {
            //确定一个给定的字符串包含另一个字符串
            if (! Str::contains($key, 'password')) {
                //将基本WHERE子句添加到查询中
                $query->where($key, $value);
            }
        }

        // Now we are ready to execute the query to see if we have an user matching
        // the given credentials. If not, we will just return nulls and indicate
        // that there are no matching users for these given credential arrays.
        //
        // 现在，我们已经准备好执行查询，看看是否有一个用户匹配给定的凭证
        // 如果没有，我们将返回null，并指出这些给定的凭证数组没有匹配的用户
        //
        $user = $query->first();//执行查询和得到的第一个结果
        //获得通用用户
        return $this->getGenericUser($user);
    }

    /**
     * Get the generic user.
     *
     * 获得通用用户
     *
     * @param  mixed  $user
     * @return \Illuminate\Auth\GenericUser|null
     */
    protected function getGenericUser($user)
    {
        if (! is_null($user)) {
            //创建一个新的通用用户对象
            return new GenericUser((array) $user);
        }
    }

    /**
     * Validate a user against the given credentials.
     *
     * 根据给定的凭据对用户进行验证
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        //检查给定的普通值与散列值
        return $this->hasher->check(
            //                          获取用户的密码
            $credentials['password'], $user->getAuthPassword()
        );
    }
}
