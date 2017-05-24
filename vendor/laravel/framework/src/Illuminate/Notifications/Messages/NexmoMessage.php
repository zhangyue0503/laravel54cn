<?php

namespace Illuminate\Notifications\Messages;

class NexmoMessage
{
    /**
     * The message content.
     *
     * 消息内容
     *
     * @var string
     */
    public $content;

    /**
     * The phone number the message should be sent from.
     *
     * 应该从电话号码中发出信息
     *
     * @var string
     */
    public $from;

    /**
     * The message type.
     *
     * 消息类型
     *
     * @var string
     */
    public $type = 'text';

    /**
     * Create a new message instance.
     *
     * 创建一个新的消息实例
     *
     * @param  string  $content
     * @return void
     */
    public function __construct($content = '')
    {
        $this->content = $content;
    }

    /**
     * Set the message content.
     *
     * 设置消息内容
     *
     * @param  string  $content
     * @return $this
     */
    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set the phone number the message should be sent from.
     *
     * 设置应该从电话号码中发出信息
     *
     * @param  string  $from
     * @return $this
     */
    public function from($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Set the message type.
     *
     * 设置消息类型
     *
     * @return $this
     */
    public function unicode()
    {
        $this->type = 'unicode';

        return $this;
    }
}
