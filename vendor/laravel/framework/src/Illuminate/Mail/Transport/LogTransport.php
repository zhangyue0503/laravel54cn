<?php

namespace Illuminate\Mail\Transport;

use Swift_Mime_Message;
use Swift_Mime_MimeEntity;
use Psr\Log\LoggerInterface;

class LogTransport extends Transport
{
    /**
     * The Logger instance.
     *
     * Logger实例
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Create a new log transport instance.
     *
     * 创建一个新的日志传输实例
     *
     * @param  \Psr\Log\LoggerInterface  $logger
     * @return void
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     * 发送消息
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        //遍历已注册的插件并执行插件的方法
        $this->beforeSendPerformed($message);
        //详细的调试信息                从Swiftmailer的实体中获得一个可记录的字符串
        $this->logger->debug($this->getMimeEntityString($message));
        //遍历已注册的插件并执行插件的方法
        $this->sendPerformed($message);
        //获得受助者的数量
        return $this->numberOfRecipients($message);
    }

    /**
     * Get a loggable string out of a Swiftmailer entity.
     *
     * 从Swiftmailer的实体中获得一个可记录的字符串
     *
     * @param  \Swift_Mime_MimeEntity $entity
     * @return string
     */
    protected function getMimeEntityString(Swift_Mime_MimeEntity $entity)
    {
        //                 在这个Mime实体中获取头部的集合       将该实体的主体内容作为字符串进行
        $string = (string) $entity->getHeaders().PHP_EOL.$entity->getBody();
        //让所有的子嵌套在这个实体中
        foreach ($entity->getChildren() as $children) {
            //                                从Swiftmailer的实体中获得一个可记录的字符串
            $string .= PHP_EOL.PHP_EOL.$this->getMimeEntityString($children);
        }

        return $string;
    }
}
