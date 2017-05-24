<?php

namespace Illuminate\Notifications\Messages;

class MailMessage extends SimpleMessage
{
    /**
     * The view to be rendered.
     *
     * 呈现的视图
     *
     * @var array|string
     */
    public $view;

    /**
     * The view data for the message.
     *
     * 消息的视图数据
     *
     * @var array
     */
    public $viewData = [];

    /**
     * The Markdown template to render (if applicable).
     *
     * 用于呈现的Markdown模板(如果适用的话)
     *
     * @var string|null
     */
    public $markdown = 'notifications::email';

    /**
     * The "from" information for the message.
     *
     * 信息的“来自”信息
     *
     * @var array
     */
    public $from = [];

    /**
     * The "reply to" information for the message.
     *
     * 消息的“回复”信息
     *
     * @var array
     */
    public $replyTo = [];

    /**
     * The attachments for the message.
     *
     * 消息的附件
     *
     * @var array
     */
    public $attachments = [];

    /**
     * The raw attachments for the message.
     *
     * 消息的原始附件
     *
     * @var array
     */
    public $rawAttachments = [];

    /**
     * Priority level of the message.
     *
     * 消息的优先级
     *
     * @var int
     */
    public $priority;

    /**
     * Set the view for the mail message.
     *
     * 设置邮件消息的视图
     *
     * @param  array|string  $view
     * @param  array  $data
     * @return $this
     */
    public function view($view, array $data = [])
    {
        $this->view = $view;
        $this->viewData = $data;

        $this->markdown = null;

        return $this;
    }

    /**
     * Set the Markdown template for the notification.
     *
     * 为通知设置Markdown模板
     *
     * @param  string  $view
     * @param  array  $data
     * @return $this
     */
    public function markdown($view, array $data = [])
    {
        $this->markdown = $view;
        $this->viewData = $data;

        $this->view = null;

        return $this;
    }

    /**
     * Set the from address for the mail message.
     *
     * 设置邮件消息的地址
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function from($address, $name = null)
    {
        $this->from = [$address, $name];

        return $this;
    }

    /**
     * Set the "reply to" address of the message.
     *
     * 设置消息的“回复”地址
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function replyTo($address, $name = null)
    {
        $this->replyTo = [$address, $name];

        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * 将文件附加到消息中
     *
     * @param  string  $file
     * @param  array  $options
     * @return $this
     */
    public function attach($file, array $options = [])
    {
        $this->attachments[] = compact('file', 'options');

        return $this;
    }

    /**
     * Attach in-memory data as an attachment.
     *
     * 将内存中的数据附加在附件中
     *
     * @param  string  $data
     * @param  string  $name
     * @param  array  $options
     * @return $this
     */
    public function attachData($data, $name, array $options = [])
    {
        $this->rawAttachments[] = compact('data', 'name', 'options');

        return $this;
    }

    /**
     * Set the priority of this message.
     *
     * 设置此消息的优先级
     *
     * The value is an integer where 1 is the highest priority and 5 is the lowest.
     *
     * 值是一个整数，其中1是最高的优先级，5是最低的
     *
     * @param  int  $level
     * @return $this
     */
    public function priority($level)
    {
        $this->priority = $level;

        return $this;
    }

    /**
     * Get the data array for the mail message.
     *
     * 获取邮件消息的数据数组
     *
     * @return array
     */
    public function data()
    {
        //                  获取消息的数组表示
        return array_merge($this->toArray(), $this->viewData);
    }
}
