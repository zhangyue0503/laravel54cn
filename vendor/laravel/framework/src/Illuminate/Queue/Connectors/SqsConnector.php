<?php

namespace Illuminate\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Arr;
use Illuminate\Queue\SqsQueue;

class SqsConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * 建立一个队列连接
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        //                  获取SQS的默认配置
        $config = $this->getDefaultConfiguration($config);

        if ($config['key'] && $config['secret']) {
            //                        从给定数组中获取项目的子集
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }
        //创建一个新的Amazon SQS队列实例
        return new SqsQueue(
            //                                       使用“点”符号从数组中获取一个项
            new SqsClient($config), $config['queue'], Arr::get($config, 'prefix', '')
        );
    }

    /**
     * Get the default configuration for SQS.
     *
     * 获取SQS的默认配置
     *
     * @param  array  $config
     * @return array
     */
    protected function getDefaultConfiguration(array $config)
    {
        return array_merge([
            'version' => 'latest',
            'http' => [
                'timeout' => 60,
                'connect_timeout' => 60,
            ],
        ], $config);
    }
}
