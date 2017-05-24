<?php

namespace Illuminate\Mail;

use Swift_Mailer;
use Swift_Message;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Queue\Factory as QueueContract;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\MailQueue as MailQueueContract;

class Mailer implements MailerContract, MailQueueContract
{
    /**
     * The view factory instance.
     *
     * 视图工厂实例
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $views;

    /**
     * The Swift Mailer instance.
     *
     * Swift Mailer实例
     *
     * @var \Swift_Mailer
     */
    protected $swift;

    /**
     * The event dispatcher instance.
     *
     * 事件调度器实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $events;

    /**
     * The global from address and name.
     *
     * 来自地址和名称的全局
     *
     * @var array
     */
    protected $from;

    /**
     * The global reply-to address and name.
     *
     * 全局回复地址和名称
     *
     * @var array
     */
    protected $replyTo;

    /**
     * The global to address and name.
     *
     * 全局的地址和名称
     *
     * @var array
     */
    protected $to;

    /**
     * The queue implementation.
     *
     * 队列的实现
     *
     * @var \Illuminate\Contracts\Queue\Queue
     */
    protected $queue;

    /**
     * Array of failed recipients.
     *
     * 一系列失败的接受者
     *
     * @var array
     */
    protected $failedRecipients = [];

    /**
     * Create a new Mailer instance.
     *
     * 创建一个新的Mailer实例
     *
     * @param  \Illuminate\Contracts\View\Factory  $views
     * @param  \Swift_Mailer  $swift
     * @param  \Illuminate\Contracts\Events\Dispatcher|null  $events
     * @return void
     */
    public function __construct(Factory $views, Swift_Mailer $swift, Dispatcher $events = null)
    {
        $this->views = $views;
        $this->swift = $swift;
        $this->events = $events;
    }

    /**
     * Set the global from address and name.
     *
     * 从地址和名称设置全局
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysFrom($address, $name = null)
    {
        $this->from = compact('address', 'name');
    }

    /**
     * Set the global reply-to address and name.
     *
     * 设置全局回复地址和名称
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysReplyTo($address, $name = null)
    {
        $this->replyTo = compact('address', 'name');
    }

    /**
     * Set the global to address and name.
     *
     * 设置全局地址和名称
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysTo($address, $name = null)
    {
        $this->to = compact('address', 'name');
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * 开始发送一个可邮件类实例的过程
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function to($users)
    {
        //                             设置消息的收件人
        return (new PendingMail($this))->to($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * 开始发送一个可邮件类实例的过程
     *
     * @param  mixed  $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function bcc($users)
    {
        //                             设置消息的接收者
        return (new PendingMail($this))->bcc($users);
    }

    /**
     * Send a new message when only a raw text part.
     *
     * 仅在原始文本部分发送一条新消息
     *
     * @param  string  $text
     * @param  mixed  $callback
     * @return void
     */
    public function raw($text, $callback)
    {
        //           使用视图发送一条新消息
        return $this->send(['raw' => $text], [], $callback);
    }

    /**
     * Send a new message when only a plain part.
     *
     * 仅在普通部分发送一条新消息
     *
     * @param  string  $view
     * @param  array  $data
     * @param  mixed  $callback
     * @return void
     */
    public function plain($view, array $data, $callback)
    {
        //           使用视图发送一条新消息
        return $this->send(['text' => $view], $data, $callback);
    }

    /**
     * Send a new message using a view.
     *
     * 使用视图发送一条新消息
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return void
     */
    public function send($view, array $data = [], $callback = null)
    {
        if ($view instanceof MailableContract) {
            //             发送给可邮寄的
            return $this->sendMailable($view);
        }

        // First we need to parse the view, which could either be a string or an array
        // containing both an HTML and plain text versions of the view which should
        // be used when sending an e-mail. We will extract both of them out here.
        //
        // 首先，我们需要解析视图，它既可以是字符串，也可以是包含HTML和纯文本版本的视图，这些视图应该在发送电子邮件时使用
        // 我们把它们都取出来
        //
        //                             解析给定的视图名或数组
        list($view, $plain, $raw) = $this->parseView($view);
        //                              创建一个新的消息实例
        $data['message'] = $message = $this->createMessage();

        // Once we have retrieved the view content for the e-mail we will set the body
        // of this message using the HTML type, which will provide a simple wrapper
        // to creating view based emails that are able to receive arrays of data.
        //
        // 一旦我们检索了电子邮件的视图内容，我们将使用HTML类型设置此消息的主体，它将提供一个简单的包装器来创建基于视图的电子邮件，这些电子邮件能够接收数据数组
        //
        // 将内容添加到给定的消息中
        $this->addContent($message, $view, $plain, $raw, $data);

        call_user_func($callback, $message);

        // If a global "to" address has been set, we will set that address on the mail
        // message. This is primarily useful during local development in which each
        // message should be delivered into a single mail address for inspection.
        //
        // 如果已经设置了一个全局“to”地址，我们将在邮件消息上设置该地址。这在本地开发中是非常有用的，在本地开发中，每个消息都应该被交付到一个单独的邮件地址进行检查
        //
        if (isset($this->to['address'])) {
            //在给定消息上设置全局“to”地址
            $this->setGlobalTo($message);
        }
        //发送一个快速消息实例            获取底层的Swift消息实例
        $this->sendSwiftMessage($message->getSwiftMessage());
    }

    /**
     * Send the given mailable.
     *
     * 发送给可邮寄的
     *
     * @param  MailableContract  $mailable
     * @return mixed
     */
    protected function sendMailable(MailableContract $mailable)
    {
        return $mailable instanceof ShouldQueue
        //                 对给定消息排队                使用给定的邮件发送消息
                ? $mailable->queue($this->queue) : $mailable->send($this);
    }

    /**
     * Parse the given view name or array.
     *
     * 解析给定的视图名或数组
     *
     * @param  string|array  $view
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseView($view)
    {
        if (is_string($view)) {
            return [$view, null, null];
        }

        // If the given view is an array with numeric keys, we will just assume that
        // both a "pretty" and "plain" view were provided, so we will return this
        // array as is, since must should contain both views with numeric keys.
        //
        // 如果给定的视图是一个带有数字键的数组，那么我们将假设提供了一个“漂亮”和“纯”视图，因此我们将返回这个数组，因为必须包含两个视图和数字键
        //
        if (is_array($view) && isset($view[0])) {
            return [$view[0], $view[1], null];
        }

        // If this view is an array but doesn't contain numeric keys, we will assume
        // the views are being explicitly specified and will extract them via the
        // named keys instead, allowing the developers to use one or the other.
        //
        // 如果该视图是一个数组，但不包含数字键，那么我们将假定视图是显式指定的，并将通过指定的键提取它们，从而允许开发人员使用其中一个或另一个
        //
        if (is_array($view)) {
            return [
                //使用“点”符号从数组中获取一个项
                Arr::get($view, 'html'),
                Arr::get($view, 'text'),
                Arr::get($view, 'raw'),
            ];
        }

        throw new InvalidArgumentException('Invalid view.');
    }

    /**
     * Add the content to a given message.
     *
     * 将内容添加到给定的消息中
     *
     * @param  \Illuminate\Mail\Message  $message
     * @param  string  $view
     * @param  string  $plain
     * @param  string  $raw
     * @param  array  $data
     * @return void
     */
    protected function addContent($message, $view, $plain, $raw, $data)
    {
        if (isset($view)) {
            //                   呈现给定的视图
            $message->setBody($this->renderView($view, $data), 'text/html');
        }

        if (isset($plain)) {
            $method = isset($view) ? 'addPart' : 'setBody';

            $message->$method($this->renderView($plain, $data), 'text/plain');
        }

        if (isset($raw)) {
            $method = (isset($view) || isset($plain)) ? 'addPart' : 'setBody';

            $message->$method($raw, 'text/plain');
        }
    }

    /**
     * Render the given view.
     *
     * 呈现给定的视图
     *
     * @param  string  $view
     * @param  array  $data
     * @return string
     */
    protected function renderView($view, $data)
    {
        return $view instanceof HtmlString
        //                    获取HTML字符串
                        ? $view->toHtml()
            //                   获取给定视图的评估视图内容     获取对象的评估内容
                        : $this->views->make($view, $data)->render();
    }

    /**
     * Set the global "to" address on the given message.
     *
     * 在给定消息上设置全局“to”地址
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return void
     */
    protected function setGlobalTo($message)
    {
        $message->to($this->to['address'], $this->to['name'], true);//在消息中添加收件人
        $message->cc($this->to['address'], $this->to['name'], true);//在消息中添加一个抄送拷贝
        $message->bcc($this->to['address'], $this->to['name'], true);//在消息中添加一个抄送的副本
    }

    /**
     * Queue a new e-mail message for sending.
     *
     * 为发送一个新的电子邮件消息
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string|null  $queue
     * @return mixed
     */
    public function queue($view, array $data = [], $callback = null, $queue = null)
    {
        if (! $view instanceof MailableContract) {
            throw new InvalidArgumentException('Only mailables may be queued.');
        }
        //           对给定消息排队
        return $view->queue($this->queue);
    }

    /**
     * Queue a new e-mail message for sending on the given queue.
     *
     * 队列为发送给定队列的新电子邮件消息
     *
     * @param  string  $queue
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return mixed
     */
    public function onQueue($queue, $view, array $data, $callback)
    {
        //           对给定消息排队
        return $this->queue($view, $data, $callback, $queue);
    }

    /**
     * Queue a new e-mail message for sending on the given queue.
     *
     * 队列为发送给定队列的新电子邮件消息
     *
     * This method didn't match rest of framework's "onQueue" phrasing. Added "onQueue".
     *
     * 这个方法与框架的“onQueue”的措辞不匹配
     * 添加“onQueue”
     *
     * @param  string  $queue
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return mixed
     */
    public function queueOn($queue, $view, array $data, $callback)
    {
        //队列为发送给定队列的新电子邮件消息
        return $this->onQueue($queue, $view, $data, $callback);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * 排队等待发送(n)秒的新电子邮件消息
     *
     * @param  int  $delay
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $view, array $data = [], $callback = null, $queue = null)
    {
        if (! $view instanceof MailableContract) {
            throw new InvalidArgumentException('Only mailables may be queued.');
        }
        //在给定的延迟之后交付队列消息
        return $view->later($delay, $this->queue);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds on the given queue.
     *
     * 队列一个新的电子邮件消息，用于在给定队列上发送(n)秒
     *
     * @param  string  $queue
     * @param  int  $delay
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return mixed
     */
    public function laterOn($queue, $delay, $view, array $data, $callback)
    {
        //在给定的延迟之后交付队列消息
        return $this->later($delay, $view, $data, $callback, $queue);
    }

    /**
     * Create a new message instance.
     *
     * 创建一个新的消息实例
     *
     * @return \Illuminate\Mail\Message
     */
    protected function createMessage()
    {
        $message = new Message(new Swift_Message);

        // If a global from address has been specified we will set it on every message
        // instances so the developer does not have to repeat themselves every time
        // they create a new message. We will just go ahead and push the address.
        //
        // 如果指定了一个全局地址，我们将在每个消息实例上设置它，这样开发人员就不必每次创建新消息时重复自己
        // 我们将继续推进这个地址
        //
        if (! empty($this->from['address'])) {
            //将“from”地址添加到消息中
            $message->from($this->from['address'], $this->from['name']);
        }

        // When a global reply address was specified we will set this on every message
        // instances so the developer does not have to repeat themselves every time
        // they create a new message. We will just go ahead and push the address.
        //
        // 当指定一个全局回复地址时，我们将在每个消息实例上设置这个，这样开发人员就不必每次创建新消息时重复自己
        // 我们将继续推进这个地址
        //
        if (! empty($this->replyTo['address'])) {
            //在消息中添加一个应答
            $message->replyTo($this->replyTo['address'], $this->replyTo['name']);
        }

        return $message;
    }

    /**
     * Send a Swift Message instance.
     *
     * 发送一个快速消息实例
     *
     * @param  \Swift_Message  $message
     * @return void
     */
    protected function sendSwiftMessage($message)
    {
        if ($this->events) {
            //      将事件触发，直到返回第一个非空响应
            $this->events->dispatch(new Events\MessageSending($message));
        }

        try {
            //            发送给定的消息，就像它会被发送到邮件客户端一样
            return $this->swift->send($message, $this->failedRecipients);
        } finally {
            //   这将防止守护进程队列中的错误
            $this->forceReconnection();
        }
    }

    /**
     * Force the transport to re-connect.
     *
     * 强迫运输重新连接
     *
     * This will prevent errors in daemon queue situations.
     *
     * 这将防止守护进程队列中的错误
     *
     * @return void
     */
    protected function forceReconnection()
    {
        //得到Swift Mailer实例   用于发送消息的传输  停止这种传输机制
        $this->getSwiftMailer()->getTransport()->stop();
    }

    /**
     * Get the view factory instance.
     *
     * 获取视图工厂实例
     *
     * @return \Illuminate\Contracts\View\Factory
     */
    public function getViewFactory()
    {
        return $this->views;
    }

    /**
     * Get the Swift Mailer instance.
     *
     * 得到Swift Mailer实例
     *
     * @return \Swift_Mailer
     */
    public function getSwiftMailer()
    {
        return $this->swift;
    }

    /**
     * Get the array of failed recipients.
     *
     * 获取失败接收者的数组
     *
     * @return array
     */
    public function failures()
    {
        return $this->failedRecipients;
    }

    /**
     * Set the Swift Mailer instance.
     *
     * 设置Swift Mailer的实例
     *
     * @param  \Swift_Mailer  $swift
     * @return void
     */
    public function setSwiftMailer($swift)
    {
        $this->swift = $swift;
    }

    /**
     * Set the queue manager instance.
     *
     * 设置队列管理器实例
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return $this
     */
    public function setQueue(QueueContract $queue)
    {
        $this->queue = $queue;

        return $this;
    }
}
