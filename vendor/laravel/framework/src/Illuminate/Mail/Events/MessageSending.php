<?php

namespace Illuminate\Mail\Events;

class MessageSending
{
    /**
     * The Swift message instance.
     *
     * Swift消息实例
     *
     * @var \Swift_Message
     */
    public $message;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  \Swift_Message  $message
     * @return void
     */
    public function __construct($message)
    {
        $this->message = $message;
    }
}
