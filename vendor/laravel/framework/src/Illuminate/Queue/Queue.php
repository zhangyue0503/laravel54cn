<?php

namespace Illuminate\Queue;

use Illuminate\Container\Container;

abstract class Queue
{
    use InteractsWithTime;

    /**
     * The IoC container instance.
     *
     * IoC容器实例
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The encrypter implementation.
     *
     * 加密的实现
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The connection name for the queue.
     *
     * 队列的连接名称
     *
     * @var string
     */
    protected $connectionName;

    /**
     * Push a new job onto the queue.
     *
     * 将新工作推到队列上
     *
     * @param  string  $queue
     * @param  string  $job
     * @param  mixed   $data
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * 在延迟之后将新作业推到队列上
     *
     * @param  string  $queue
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->later($delay, $job, $data, $queue);
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * 将一系列作业推到队列中
     *
     * @param  array   $jobs
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ((array) $jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * Create a payload string from the given job and data.
	 *
	 * 从给定的作业和数据创建有效载荷字符串
	 * * 通过给定的消息和数据创建一个载荷字符串
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return string
     *
     * @throws \Illuminate\Queue\InvalidPayloadException
     */
    protected function createPayload($job, $data = '', $queue = null)
    {
		//                       从给定的作业和数据创建有效载荷数组
        $payload = json_encode($this->createPayloadArray($job, $data, $queue));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidPayloadException;
        }

        return $payload;
    }

    /**
     * Create a payload array from the given job and data.
	 *
	 * 从给定的作业和数据创建有效载荷数组
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return array
     */
    protected function createPayloadArray($job, $data = '', $queue = null)
    {
        return is_object($job)
                    ? $this->createObjectPayload($job)//为基于对象的队列处理程序创建有效负载
                    : $this->createStringPayload($job, $data);//创建一个典型的基于字符串的队列有效负载数组
    }

    /**
     * Create a payload for an object-based queue handler.
     *
     * 为基于对象的队列处理程序创建有效负载
     *
     * @param  mixed  $job
     * @return array
     */
    protected function createObjectPayload($job)
    {
        return [
            'displayName' => $this->getDisplayName($job),//获取给定作业的显示名称
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'maxTries' => isset($job->tries) ? $job->tries : null,
            'timeout' => isset($job->timeout) ? $job->timeout : null,
            'data' => [
                'commandName' => get_class($job),
                'command' => serialize(clone $job),
            ],
        ];
    }

    /**
     * Get the display name for the given job.
	 *
	 * 获取给定作业的显示名称
     *
     * @param  mixed  $job
     * @return string
     */
    protected function getDisplayName($job)
    {
        return method_exists($job, 'displayName')
        //              获取队列作业的显示名称
                        ? $job->displayName() : get_class($job);
    }

    /**
     * Create a typical, string based queue payload array.
     *
     * 创建一个典型的基于字符串的队列有效负载数组
     *
     * @param  string  $job
     * @param  mixed  $data
     * @return array
     */
    protected function createStringPayload($job, $data)
    {
        return [
            'displayName' => is_string($job) ? explode('@', $job)[0] : null,
            'job' => $job, 'maxTries' => null,
            'timeout' => null, 'data' => $data,
        ];
    }

    /**
     * Get the connection name for the queue.
     *
     * 获取队列的连接名称
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Set the connection name for the queue.
     *
     * 设置队列的连接名称
     *
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName($name)
    {
        $this->connectionName = $name;

        return $this;
    }

    /**
     * Set the IoC container instance.
     *
     * 设置IoC容器实例
     *
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}
