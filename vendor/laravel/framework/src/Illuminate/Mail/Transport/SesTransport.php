<?php

namespace Illuminate\Mail\Transport;

use Aws\Ses\SesClient;
use Swift_Mime_Message;

class SesTransport extends Transport
{
    /**
     * The Amazon SES instance.
     *
     * Amazon SES实例
     *
     * @var \Aws\Ses\SesClient
     */
    protected $ses;

    /**
     * Create a new SES transport instance.
     *
     * 创建一个新的SES传输实例
     *
     * @param  \Aws\Ses\SesClient  $ses
     * @return void
     */
    public function __construct(SesClient $ses)
    {
        $this->ses = $ses;
    }

    /**
     * {@inheritdoc}
     * 发送
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        //遍历已注册的插件并执行插件的方法
        $this->beforeSendPerformed($message);
        //在这个Mime实体中获取头部的集合
        $headers = $message->getHeaders();
        //添加一个新的基本文本标题，$name和$value
        $headers->addTextHeader('X-SES-Message-ID', $this->ses->sendRawEmail([
            //                获取该消息的发送方地址             获取该消息的地址(es)
            'Source' => key($message->getSender() ?: $message->getFrom()),
            'RawMessage' => [
                'Data' => $message->toString(),//将整个实体以字符串形式进行
            ],
        ])->get('MessageId'));
        //遍历已注册的插件并执行插件的方法
        $this->sendPerformed($message);
        //       获得受助者的数量
        return $this->numberOfRecipients($message);
    }
}
