<?php

namespace Illuminate\Notifications\Messages;

use Illuminate\Notifications\Action;

class SimpleMessage
{
    /**
     * The "level" of the notification (info, success, error).
     *
     * 通知的“级别”(信息、成功、错误)
     *
     * @var string
     */
    public $level = 'info';

    /**
     * The subject of the notification.
     *
     * 通知的主题
     *
     * @var string
     */
    public $subject;

    /**
     * The notification's greeting.
     *
     * 通知的问候
     *
     * @var string
     */
    public $greeting;

    /**
     * The notification's salutation.
     *
     * 通知的称呼
     *
     * @var string
     */
    public $salutation;

    /**
     * The "intro" lines of the notification.
     *
     * 通知的“介绍”行
     *
     * @var array
     */
    public $introLines = [];

    /**
     * The "outro" lines of the notification.
     *
     * 通知的“超过”行
     *
     * @var array
     */
    public $outroLines = [];

    /**
     * The text / label for the action.
     *
     * 操作的文本/标签
     *
     * @var string
     */
    public $actionText;

    /**
     * The action URL.
     *
     * 操作url
     *
     * @var string
     */
    public $actionUrl;

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
     * Set the "level" of the notification (success, error, etc.).
     *
     * 设置通知的“级别”(成功、错误等)
     *
     * @param  string  $level
     * @return $this
     */
    public function level($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Set the subject of the notification.
     *
     * 设置通知的主题
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set the greeting of the notification.
     *
     * 设置通知的问候
     *
     * @param  string  $greeting
     * @return $this
     */
    public function greeting($greeting)
    {
        $this->greeting = $greeting;

        return $this;
    }

    /**
     * Set the salutation of the notification.
     *
     * 设置通知的问候
     *
     * @param  string  $salutation
     * @return $this
     */
    public function salutation($salutation)
    {
        $this->salutation = $salutation;

        return $this;
    }

    /**
     * Add a line of text to the notification.
     *
     * 在通知中添加一行文本
     *
     * @param  \Illuminate\Notifications\Action|string  $line
     * @return $this
     */
    public function line($line)
    {
        //在通知中添加一行文本
        return $this->with($line);
    }

    /**
     * Add a line of text to the notification.
     *
     * 在通知中添加一行文本
     *
     * @param  \Illuminate\Notifications\Action|string|array  $line
     * @return $this
     */
    public function with($line)
    {
        if ($line instanceof Action) {
            //配置“调用操作”按钮
            $this->action($line->text, $line->url);
        } elseif (! $this->actionText) {
            //                     格式化给定的文本行
            $this->introLines[] = $this->formatLine($line);
        } else {
            $this->outroLines[] = $this->formatLine($line);
        }

        return $this;
    }

    /**
     * Format the given line of text.
     *
     * 格式化给定的文本行
     *
     * @param  string|array  $line
     * @return string
     */
    protected function formatLine($line)
    {
        if (is_array($line)) {
            return implode(' ', array_map('trim', $line));
        }

        return trim(implode(' ', array_map('trim', preg_split('/\\r\\n|\\r|\\n/', $line))));
    }

    /**
     * Configure the "call to action" button.
     *
     * 配置“调用操作”按钮
     *
     * @param  string  $text
     * @param  string  $url
     * @return $this
     */
    public function action($text, $url)
    {
        $this->actionText = $text;
        $this->actionUrl = $url;

        return $this;
    }

    /**
     * Get an array representation of the message.
     *
     * 获取消息的数组表示
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'level' => $this->level,
            'subject' => $this->subject,
            'greeting' => $this->greeting,
            'salutation' => $this->salutation,
            'introLines' => $this->introLines,
            'outroLines' => $this->outroLines,
            'actionText' => $this->actionText,
            'actionUrl' => $this->actionUrl,
        ];
    }
}
