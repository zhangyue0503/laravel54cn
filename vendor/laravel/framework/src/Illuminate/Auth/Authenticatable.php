<?php

namespace Illuminate\Auth;

trait Authenticatable
{
    /**
     * The column name of the "remember me" token.
     *
     * “记住我”的列名
     *
     * @var string
     */
    protected $rememberTokenName = 'remember_token';

    /**
     * Get the name of the unique identifier for the user.
     *
     * 为用户获取唯一标识符的名称
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        //从模型中获取主键
        return $this->getKeyName();
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
        //获取模型主键的值
        return $this->getKey();
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
        return $this->password;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * 获取“记住我”会话的令牌值
     *
     * @return string
     */
    public function getRememberToken()
    {
        //             获取“记住我”标记的列名
        if (! empty($this->getRememberTokenName())) {
            //            获取“记住我”标记的列名
            return $this->{$this->getRememberTokenName()};
        }
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * 为“记住我”的会话设置令牌值
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        //             获取“记住我”标记的列名
        if (! empty($this->getRememberTokenName())) {
            //            获取“记住我”标记的列名
            $this->{$this->getRememberTokenName()} = $value;
        }
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
        return $this->rememberTokenName;
    }
}
