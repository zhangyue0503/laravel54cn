<?php

namespace Illuminate\Mail\Transport;

use Swift_Mime_Message;
use Illuminate\Support\Collection;

class ArrayTransport extends Transport
{
    /**
     * The collection of Swift Messages.
     *
     * Swift消息的集合
     *
     * @var \Illuminate\Support\Collection
     */
    protected $messages;

    /**
     * Create a new array transport instance.
     *
     * 创建一个新的数组传输实例
     *
     * @return void
     */
    public function __construct()
    {
        $this->messages = new Collection;
    }

    /**
     * {@inheritdoc}
     * 发送消息
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        //遍历已注册的插件并执行插件的方法
        $this->beforeSendPerformed($message);

        $this->messages[] = $message;
        //         获得受助者的数量
        return $this->numberOfRecipients($message);
    }

    /**
     * Retrieve the collection of messages.
     *
     * 检索消息的集合
     *
     * @return \Illuminate\Support\Collection
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * Clear all of the messages from the local collection.
     *
     * 清除本地收集的所有消息
     *
     * @return \Illuminate\Support\Collection
     */
    public function flush()
    {
        return $this->messages = new Collection;
    }
}
