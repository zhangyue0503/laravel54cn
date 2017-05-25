<?php

namespace Illuminate\Queue;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class SqsQueue extends Queue implements QueueContract
{
    /**
     * The Amazon SQS instance.
     *
     * Amazon SQS实例
     *
     * @var \Aws\Sqs\SqsClient
     */
    protected $sqs;

    /**
     * The name of the default queue.
     *
     * 默认队列的名称
     *
     * @var string
     */
    protected $default;

    /**
     * The queue URL prefix.
     *
     * 队列的URL前缀
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new Amazon SQS queue instance.
     *
     * 创建一个新的Amazon SQS队列实例
     *
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  string  $default
     * @param  string  $prefix
     * @return void
     */
    public function __construct(SqsClient $sqs, $default, $prefix = '')
    {
        $this->sqs = $sqs;
        $this->prefix = $prefix;
        $this->default = $default;
    }

    /**
     * Get the size of the queue.
     *
     * 获取队列的大小
     *
     * @param  string  $queue
     * @return int
     */
    public function size($queue = null)
    {
        $response = $this->sqs->getQueueAttributes([
            'QueueUrl' => $this->getQueue($queue),//获取队列或返回默认值
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ]);

        $attributes = $response->get('Attributes');

        return (int) $attributes['ApproximateNumberOfMessages'];
    }

    /**
     * Push a new job onto the queue.
     *
     * 把新工作推到队列上
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        //将原始有效负载推到队列中          从给定的作业和数据创建有效载荷字符串
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * 将原始有效负载推到队列中
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->sqs->sendMessage([
            //               获取队列或返回默认值
            'QueueUrl' => $this->getQueue($queue), 'MessageBody' => $payload,
        ])->get('MessageId');
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * 在延迟之后将新作业推到队列上
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->sqs->sendMessage([
            'QueueUrl' => $this->getQueue($queue),//获取队列或返回默认值
            'MessageBody' => $this->createPayload($job, $data),//从给定的作业和数据创建有效载荷字符串
            'DelaySeconds' => $this->secondsUntil($delay),//在给定的DateTime之前获得秒数
        ])->get('MessageId');
    }

    /**
     * Pop the next job off of the queue.
     * 从队列中取出下一个作业
     *
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue = $this->getQueue($queue),//获取队列或返回默认值
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (count($response['Messages']) > 0) {
            return new SqsJob(
                $this->container, $this->sqs, $response['Messages'][0],
                $this->connectionName, $queue
            );
        }
    }

    /**
     * Get the queue or return the default.
     *
     * 获取队列或返回默认值
     *
     * @param  string|null  $queue
     * @return string
     */
    public function getQueue($queue)
    {
        $queue = $queue ?: $this->default;

        return filter_var($queue, FILTER_VALIDATE_URL) === false
                        ? rtrim($this->prefix, '/').'/'.$queue : $queue;
    }

    /**
     * Get the underlying SQS instance.
     *
     * 获取底层的SQS实例
     *
     * @return \Aws\Sqs\SqsClient
     */
    public function getSqs()
    {
        return $this->sqs;
    }
}
