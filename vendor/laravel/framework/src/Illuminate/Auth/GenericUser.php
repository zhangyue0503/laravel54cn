<?php

namespace Illuminate\Auth;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class GenericUser implements UserContract
{
    /**
     * All of the user's attributes.
     *
     * 所有用户的属性
     *
     * @var array
     */
    protected $attributes;

    /**
     * Create a new generic User object.
     *
     * 创建一个新的通用用户对象
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * 为用户获取唯一标识符的名称
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * 获取用户的唯一标识符
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        //         为用户获取唯一标识符的名称
        $name = $this->getAuthIdentifierName();

        return $this->attributes[$name];
    }

    /**
     * Get the password for the user.
     *
     * 获取用户的密码
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->attributes['password'];
    }

    /**
     * Get the "remember me" token value.
     *
     * 获得“记住我”的标记值
     *
     * @return string
     */
    public function getRememberToken()
    {
        //                        获取“记住我”标记的列名
        return $this->attributes[$this->getRememberTokenName()];
    }

    /**
     * Set the "remember me" token value.
     *
     * 设置“记住我”的令牌值
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        //                  获取“记住我”标记的列名
        $this->attributes[$this->getRememberTokenName()] = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * 获取“记住我”标记的列名
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    /**
     * Dynamically access the user's attributes.
     *
     * 动态访问用户的属性
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->attributes[$key];
    }

    /**
     * Dynamically set an attribute on the user.
     *
     * 动态地为用户设置一个属性
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Dynamically check if a value is set on the user.
     *
     * 动态检查用户是否设置了一个值
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Dynamically unset a value on the user.
     *
     * 动态地为用户设置一个值
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}
