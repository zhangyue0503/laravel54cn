<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Psr\Log\LoggerInterface;

class LogBroadcaster extends Broadcaster
{
    /**
     * The logger implementation.
     *
     * logger实现
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Create a new broadcaster instance.
     *
     * 创建一个新的广播实例
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
     */
    public function auth($request)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function validAuthenticationResponse($request, $result)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        //                                 将通道数组格式化为字符串数组
        $channels = implode(', ', $this->formatChannels($channels));

        $payload = json_encode($payload, JSON_PRETTY_PRINT);

        $this->logger->info('Broadcasting ['.$event.'] on channels ['.$channels.'] with payload:'.PHP_EOL.$payload);
    }
}
