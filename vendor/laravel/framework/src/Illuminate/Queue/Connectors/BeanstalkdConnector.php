<?php

namespace Illuminate\Queue\Connectors;

use Pheanstalk\Pheanstalk;
use Illuminate\Support\Arr;
use Pheanstalk\PheanstalkInterface;
use Illuminate\Queue\BeanstalkdQueue;

class BeanstalkdConnector implements ConnectorInterface
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
        //        使用“点”符号从数组中获取一个项
        $retryAfter = Arr::get($config, 'retry_after', Pheanstalk::DEFAULT_TTR);
        //创建一个新的Beanstalkd队列实例        创建一个Pheanstalk实例
        return new BeanstalkdQueue($this->pheanstalk($config), $config['queue'], $retryAfter);
    }

    /**
     * Create a Pheanstalk instance.
     *
     * 创建一个Pheanstalk实例
     *
     * @param  array  $config
     * @return \Pheanstalk\Pheanstalk
     */
    protected function pheanstalk(array $config)
    {
        //  使用“点”符号从数组中获取一个项
        $port = Arr::get($config, 'port', PheanstalkInterface::DEFAULT_PORT);

        return new Pheanstalk($config['host'], $port);
    }
}
