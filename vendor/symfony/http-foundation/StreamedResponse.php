<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation;

/**
 * StreamedResponse represents a streamed HTTP response.
 *
 * StreamedResponse是一个流的HTTP响应
 *
 * A StreamedResponse uses a callback for its content.
 *
 * 一个StreamedResponse使用回调函数的内容
 *
 * The callback should use the standard PHP functions like echo
 * to stream the response back to the client. The flush() method
 * can also be used if needed.
 *
 * 回调函数应该使用标准的PHP函数，例如echo将响应流返回给客户端
 * 该方法也可以用于flush()如果需要
 *
 * @see flush()
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class StreamedResponse extends Response
{
    protected $callback;
    protected $streamed;
    private $headersSent;

    /**
     * Constructor.
     *
     * 构造函数
     *
     * @param callable|null $callback A valid PHP callback or null to set it later
     * @param int           $status   The response status code
     * @param array         $headers  An array of response headers
     */
    public function __construct(callable $callback = null, $status = 200, $headers = array())
    {
        parent::__construct(null, $status, $headers);

        if (null !== $callback) {
            $this->setCallback($callback); // 设置与此响应关联的PHP回调
        }
        $this->streamed = false;
        $this->headersSent = false;
    }

    /**
     * Factory method for chainability.
     *
     * 链式调用的工厂方法
     *
     * @param callable|null $callback A valid PHP callback or null to set it later
     * @param int           $status   The response status code
     * @param array         $headers  An array of response headers
     *
     * @return static
     */
    public static function create($callback = null, $status = 200, $headers = array())
    {
        return new static($callback, $status, $headers);
    }

    /**
     * Sets the PHP callback associated with this Response.
     *
     * 设置与此响应关联的PHP回调
     *
     * @param callable $callback A valid PHP callback
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     *
     * This method only sends the headers once.
     * 此方法只发送一次头
     */
    public function sendHeaders()
    {
        if ($this->headersSent) {
            return;
        }

        $this->headersSent = true;

        parent::sendHeaders(); //发送HTTP头部
    }

    /**
     * {@inheritdoc}
     *
     * This method only sends the content once.
     * 此方法只发送一次内容
     */
    public function sendContent()
    {
        if ($this->streamed) {
            return;
        }

        $this->streamed = true;

        if (null === $this->callback) {
            throw new \LogicException('The Response callback must not be null.');
        }

        call_user_func($this->callback);
    }

    /**
     * {@inheritdoc}
     *
     * 设置响应的内容
     *
     * @throws \LogicException when the content is not null
     */
    public function setContent($content)
    {
        if (null !== $content) {
            throw new \LogicException('The content cannot be set on a StreamedResponse instance.');
        }
    }

    /**
     * {@inheritdoc}
     *
     * 获取当前的响应内容
     *
     * @return false
     */
    public function getContent()
    {
        return false;
    }
}
