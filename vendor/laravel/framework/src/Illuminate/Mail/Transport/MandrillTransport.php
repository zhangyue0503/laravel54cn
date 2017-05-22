<?php

namespace Illuminate\Mail\Transport;

use Swift_Mime_Message;
use GuzzleHttp\ClientInterface;

class MandrillTransport extends Transport
{
    /**
     * Guzzle client instance.
     *
     * Guzzle客户实例
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The Mandrill API key.
     *
     * Mandrill API键
     *
     * @var string
     */
    protected $key;

    /**
     * Create a new Mandrill transport instance.
     *
     * 创建一个新的Mandrill传输实例
     *
     * @param  \GuzzleHttp\ClientInterface  $client
     * @param  string  $key
     * @return void
     */
    public function __construct(ClientInterface $client, $key)
    {
        $this->key = $key;
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     * 发送
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        //     遍历已注册的插件并执行插件的方法
        $this->beforeSendPerformed($message);

        $this->client->post('https://mandrillapp.com/api/1.0/messages/send-raw.json', [
            'form_params' => [
                'key' => $this->key,
                'to' => $this->getTo($message),//获取该消息应发送到的所有地址
                'raw_message' => $message->toString(),//将整个实体以字符串形式进行
                'async' => true,
            ],
        ]);
        //遍历已注册的插件并执行插件的方法
        $this->sendPerformed($message);
        //获得受助者的数量
        return $this->numberOfRecipients($message);
    }

    /**
     * Get all the addresses this message should be sent to.
     *
     * 获取该消息应发送到的所有地址
     *
     * Note that Mandrill still respects CC, BCC headers in raw message itself.
     *
     * 注意，Mandrill仍然在原始消息中关联CC，BCC头部
     *
     * @param  \Swift_Mime_Message $message
     * @return array
     */
    protected function getTo(Swift_Mime_Message $message)
    {
        $to = [];
        //获取该消息的地址
        if ($message->getTo()) {
            $to = array_merge($to, array_keys($message->getTo()));
        }
        //获取该消息的CC地址
        if ($message->getCc()) {
            $to = array_merge($to, array_keys($message->getCc()));
        }
        //获取该消息的BCC地址
        if ($message->getBcc()) {
            $to = array_merge($to, array_keys($message->getBcc()));
        }

        return $to;
    }

    /**
     * Get the API key being used by the transport.
     *
     * 获得传输使用的API密钥
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the API key being used by the transport.
     *
     * 设置传输使用的API密钥
     *
     * @param  string  $key
     * @return string
     */
    public function setKey($key)
    {
        return $this->key = $key;
    }
}
