<?php

namespace Illuminate\Auth;

use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class EloquentUserProvider implements UserProvider
{
    /**
     * The hasher implementation.
     *
     * hasher实现
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * The Eloquent user model.
     *
     * Eloquent用户模型
     *
     * @var string
     */
    protected $model;

    /**
     * Create a new database user provider.
     *
     * 创建一个新的数据库用户提供程序
     *
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @param  string  $model
     * @return void
     */
    public function __construct(HasherContract $hasher, $model)
    {
        $this->model = $model;
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
        //     创建模型的新实例->获取模型表的新查询生成器->通过主键找到模型
        return $this->createModel()->newQuery()->find($identifier);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        //创建模型的新实例
        $model = $this->createModel();
        //           获取模型表的新查询生成器
        return $model->newQuery()
            ->where($model->getAuthIdentifierName(), $identifier)//将基本WHERE子句添加到查询中(,)
            ->where($model->getRememberTokenName(), $token)//将基本WHERE子句添加到查询中(,)
            ->first();//执行查询和得到的第一个结果
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * 在存储中为给定用户更新“记住我”令牌
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
        //   为“记住我”的会话设置令牌值
        $user->setRememberToken($token);

        $timestamps = $user->timestamps;

        $user->timestamps = false;

        $user->save();

        $user->timestamps = $timestamps;
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
        if (empty($credentials)) {
            return;
        }

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
        //
        // 首先，我们将向查询中添加每个凭据元素作为where子句
        // 然后，我们可以执行查询，如果我们找到一个用户，那么就用一个Eloquent的用户“模型”来返回它，这个“模型”将被警卫实例使用
        //
        //          创建模型的新实例->获取模型表的新查询生成器
        $query = $this->createModel()->newQuery();

        foreach ($credentials as $key => $value) {
            //   确定一个给定的字符串包含另一个字符串
            if (! Str::contains($key, 'password')) {
                //将基本WHERE子句添加到查询中
                $query->where($key, $value);
            }
        }
        //执行查询和得到的第一个结果
        return $query->first();
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
        $plain = $credentials['password'];
        //         检查给定的普通值与散列值(,获取用户的密码)
        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * Create a new instance of the model.
     *
     * 创建模型的新实例
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class;
    }

    /**
     * Gets the hasher implementation.
     *
     * 获取hasher的实现
     *
     * @return \Illuminate\Contracts\Hashing\Hasher
     */
    public function getHasher()
    {
        return $this->hasher;
    }

    /**
     * Sets the hasher implementation.
     *
     * 设置hasher的实现
     *
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @return $this
     */
    public function setHasher(HasherContract $hasher)
    {
        $this->hasher = $hasher;

        return $this;
    }

    /**
     * Gets the name of the Eloquent user model.
     *
     * 获取Eloquent的用户模型的名称
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the name of the Eloquent user model.
     *
     * 设置Eloquent的用户模型的名称
     *
     * @param  string  $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }
}
