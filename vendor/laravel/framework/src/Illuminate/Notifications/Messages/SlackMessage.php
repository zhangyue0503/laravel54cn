<?php

namespace Illuminate\Notifications\Messages;

use Closure;

class SlackMessage
{
    /**
     * The "level" of the notification (info, success, warning, error).
     *
     * 通知的“级别”(信息、成功、警告、错误)
     *
     * @var string
     */
    public $level = 'info';

    /**
     * The username to send the message from.
     *
     * 发送消息的用户名
     *
     * @var string|null
     */
    public $username;

    /**
     * The user emoji icon for the message.
     *
     * 该消息的用户表情符号图标
     *
     * @var string|null
     */
    public $icon;

    /**
     * The user image icon for the message.
     *
     * 消息的用户图像图标
     *
     * @var string|null
     */
    public $image;

    /**
     * The channel to send the message on.
     *
     * 发送消息的频道
     *
     * @var string|null
     */
    public $channel;

    /**
     * The text content of the message.
     *
     * 消息的文本内容
     *
     * @var string
     */
    public $content;

    /**
     * The message's attachments.
     *
     * 消息的附件
     *
     * @var array
     */
    public $attachments = [];

    /**
     * Additional request options for the Guzzle HTTP client.
     *
     * 为Guzzle HTTP客户端提供的附加请求选项
     *
     * @var array
     */
    public $http = [];

    /**
     * Indicate that the notification gives information about a successful operation.
     *
     * 表明通知提供了成功操作的信息
     *
     * @return $this
     */
    public function success()
    {
        $this->level = 'success';

        return $this;
    }

    /**
     * Indicate that the notification gives information about a warning.
     *
     * 表明通知提供了关于警告的信息
     *
     * @return $this
     */
    public function warning()
    {
        $this->level = 'warning';

        return $this;
    }

    /**
     * Indicate that the notification gives information about an error.
     *
     * 表明通知提供了关于错误的信息
     *
     * @return $this
     */
    public function error()
    {
        $this->level = 'error';

        return $this;
    }

    /**
     * Set a custom username and optional emoji icon for the Slack message.
     *
     * 为Slack消息设置一个自定义用户名和可选的表情符号图标
     *
     * @param  string  $username
     * @param  string|null  $icon
     * @return $this
     */
    public function from($username, $icon = null)
    {
        $this->username = $username;

        if (! is_null($icon)) {
            $this->icon = $icon;
        }

        return $this;
    }

    /**
     * Set a custom image icon the message should use.
     *
     * 设置消息应该使用的自定义图像图标
     *
     * @param  string $channel
     * @return $this
     */
    public function image($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Set the Slack channel the message should be sent to.
     *
     * 设置Slack通道，消息应该被发送到
     *
     * @param  string $channel
     * @return $this
     */
    public function to($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Set the content of the Slack message.
     *
     * 设置Slack消息的内容
     *
     * @param  string  $content
     * @return $this
     */
    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Define an attachment for the message.
     *
     * 为消息定义附件
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function attachment(Closure $callback)
    {
        $this->attachments[] = $attachment = new SlackAttachment;

        $callback($attachment);

        return $this;
    }

    /**
     * Get the color for the message.
     *
     * 获取消息的颜色
     *
     * @return string
     */
    public function color()
    {
        switch ($this->level) {
            case 'success':
                return 'good';
            case 'error':
                return 'danger';
            case 'warning':
                return 'warning';
        }
    }

    /**
     * Set additional request options for the Guzzle HTTP client.
     *
     * 为Guzzle HTTP客户端设置额外的请求选项
     *
     * @param  array  $options
     * @return $this
     */
    public function http(array $options)
    {
        $this->http = $options;

        return $this;
    }
}
