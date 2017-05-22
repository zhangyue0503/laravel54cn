<?php

namespace Illuminate\Mail\Transport;

use Swift_Mime_Message;
use GuzzleHttp\ClientInterface;

class SparkPostTransport extends Transport
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
     * The SparkPost API key.
     *
     * SparkPost API密钥
     *
     * @var string
     */
    protected $key;

    /**
     * Transmission options.
     *
     * 传输选项
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new SparkPost transport instance.
     *
     * 创建一个新的SparkPost传输实例
     *
     * @param  \GuzzleHttp\ClientInterface  $client
     * @param  string  $key
     * @param  array  $options
     * @return void
     */
    public function __construct(ClientInterface $client, $key, $options = [])
    {
        $this->key = $key;
        $this->client = $client;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     * 发送
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        //遍历已注册的插件并执行插件的方法
        $this->beforeSendPerformed($message);
        //      获取该消息应发送到的所有地址
        $recipients = $this->getRecipients($message);
        //设置Bcc地址(es)
        $message->setBcc([]);

        $this->client->post('https://api.sparkpost.com/api/v1/transmissions', [
            'headers' => [
                'Authorization' => $this->key,
            ],
            'json' => array_merge([
                'recipients' => $recipients,
                'content' => [
                    'email_rfc822' => $message->toString(),//将整个实体以字符串形式进行
                ],
            ], $this->options),
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
     * Note that SparkPost still respects CC, BCC headers in raw message itself.
     *
     * @param  \Swift_Mime_Message $message
     * @return array
     */
    protected function getRecipients(Swift_Mime_Message $message)
    {
        $recipients = [];
        //获取该消息的地址
        foreach ((array) $message->getTo() as $email => $name) {
            $recipients[] = ['address' => compact('name', 'email')];
        }
        //获取该消息的CC地址
        foreach ((array) $message->getCc() as $email => $name) {
            $recipients[] = ['address' => compact('name', 'email')];
        }
        //获取该消息的BCC地址
        foreach ((array) $message->getBcc() as $email => $name) {
            $recipients[] = ['address' => compact('name', 'email')];
        }

        return $recipients;
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
     * 设置传输使用的API键
     *
     * @param  string  $key
     * @return string
     */
    public function setKey($key)
    {
        return $this->key = $key;
    }

    /**
     * Get the transmission options being used by the transport.
     *
     * 获得传输使用的传输选项
     *
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the transmission options being used by the transport.
     *
     * 设置传输使用的传输选项
     *
     * @param  array  $options
     * @return array
     */
    public function setOptions(array $options)
    {
        return $this->options = $options;
    }
}
