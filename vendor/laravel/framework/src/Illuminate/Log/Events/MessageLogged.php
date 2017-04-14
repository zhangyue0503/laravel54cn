<?php

namespace Illuminate\Log\Events;

class MessageLogged
{
    /**
     * The log "level".
     *
     * 日志等级
     *
     * @var string
     */
    public $level;

    /**
     * The log message.
     *
     * 日志消息
     *
     * @var string
     */
    public $message;

    /**
     * The log context.
     *
     * 日志上下文
     *
     * @var array
     */
    public $context;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function __construct($level, $message, array $context = [])
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }
}
