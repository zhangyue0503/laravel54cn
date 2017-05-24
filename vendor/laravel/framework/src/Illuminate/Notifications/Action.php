<?php

namespace Illuminate\Notifications;

class Action
{
    /**
     * The action text.
     *
     * 操作文本
     *
     * @var string
     */
    public $text;

    /**
     * The action URL.
     *
     * 操作URL
     *
     * @var string
     */
    public $url;

    /**
     * Create a new action instance.
     *
     * 创建一个新的操作实例
     *
     * @param  string  $text
     * @param  string  $url
     * @return void
     */
    public function __construct($text, $url)
    {
        $this->url = $url;
        $this->text = $text;
    }
}
