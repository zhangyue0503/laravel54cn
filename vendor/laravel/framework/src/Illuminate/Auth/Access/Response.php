<?php

namespace Illuminate\Auth\Access;

class Response
{
    /**
     * The response message.
     *
     * 响应消息
     *
     * @var string|null
     */
    protected $message;

    /**
     * Create a new response.
     *
     * 创建一个新的响应
     *
     * @param  string|null  $message
     */
    public function __construct($message = null)
    {
        $this->message = $message;
    }

    /**
     * Get the response message.
     *
     * 获取响应消息
     *
     * @return string|null
     */
    public function message()
    {
        return $this->message;
    }

    /**
     * Get the string representation of the message.
     *
     * 获取消息的字符串表示
     *
     * @return string
     */
    public function __toString()
    {
        //              获取响应消息
        return $this->message();
    }
}
