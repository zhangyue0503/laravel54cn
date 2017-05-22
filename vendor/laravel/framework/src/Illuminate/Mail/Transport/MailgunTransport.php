<?php

namespace Illuminate\Mail\Transport;

use Swift_Mime_Message;
use GuzzleHttp\ClientInterface;

class MailgunTransport extends Transport
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
     * The Mailgun API key.
     *
     * Mailgun API键
     *
     * @var string
     */
    protected $key;

    /**
     * The Mailgun domain.
     *
     * Mailgun域
     *
     * @var string
     */
    protected $domain;

    /**
     * THe Mailgun API end-point.
     *
     * Mailgun API端点
     *
     * @var string
     */
    protected $url;

    /**
     * Create a new Mailgun transport instance.
     *
     * 创建一个新的Mailgun传输实例
     *
     * @param  \GuzzleHttp\ClientInterface  $client
     * @param  string  $key
     * @param  string  $domain
     * @return void
     */
    public function __construct(ClientInterface $client, $key, $domain)
    {
        $this->key = $key;
        $this->client = $client;
        //      设置传输使用的域
        $this->setDomain($domain);
    }

    /**
     * {@inheritdoc}
     * 发送
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        //遍历已注册的插件并执行插件的方法
        $this->beforeSendPerformed($message);
        //获取API请求的“to”有效负载字段
        $to = $this->getTo($message);
        //设置Bcc地址(es)
        $message->setBcc([]);
        //                                 获取发送Mailgun消息的HTTP有效负载
        $this->client->post($this->url, $this->payload($message, $to));
        // 遍历已注册的插件并执行插件的方法
        $this->sendPerformed($message);
        //        获得受助者的数量
        return $this->numberOfRecipients($message);
    }

    /**
     * Get the HTTP payload for sending the Mailgun message.
     *
     * 获取发送Mailgun消息的HTTP有效负载
     *
     * @param  \Swift_Mime_Message  $message
     * @param  string  $to
     * @return array
     */
    protected function payload(Swift_Mime_Message $message, $to)
    {
        return [
            'auth' => [
                'api',
                $this->key,
            ],
            'multipart' => [
                [
                    'name' => 'to',
                    'contents' => $to,
                ],
                [
                    'name' => 'message',
                    'contents' => $message->toString(),//将整个实体以字符串形式进行
                    'filename' => 'message.mime',
                ],
            ],
        ];
    }

    /**
     * Get the "to" payload field for the API request.
     *
     * 获取API请求的“to”有效负载字段
     *
     * @param  \Swift_Mime_Message  $message
     * @return string
     */
    protected function getTo(Swift_Mime_Message $message)
    {
        //                获取消息的所有联系人           在每个项目上运行map
        return collect($this->allContacts($message))->map(function ($display, $address) {
            return $display ? $display." <{$address}>" : $address;
        })->values()->implode(',');//重置基础阵列上的键->一个给定的键连接的值作为一个字符串
    }

    /**
     * Get all of the contacts for the message.
     *
     * 获取消息的所有联系人
     *
     * @param  \Swift_Mime_Message  $message
     * @return array
     */
    protected function allContacts(Swift_Mime_Message $message)
    {
        return array_merge(
            //           获取该消息的地址            获取该消息的Cc地址               获取该消息的Bcc地址
            (array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
        );
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
     * Get the domain being used by the transport.
     *
     * 获得传输使用的域
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set the domain being used by the transport.
     *
     * 设置传输使用的域
     *
     * @param  string  $domain
     * @return void
     */
    public function setDomain($domain)
    {
        $this->url = 'https://api.mailgun.net/v3/'.$domain.'/messages.mime';

        return $this->domain = $domain;
    }
}
