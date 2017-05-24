<?php

namespace Illuminate\Notifications\Messages;

use Carbon\Carbon;

class SlackAttachment
{
    /**
     * The attachment's title.
     *
     * 附件的标题
     *
     * @var string
     */
    public $title;

    /**
     * The attachment's URL.
     *
     * 附件的URL
     *
     * @var string
     */
    public $url;

    /**
     * The attachment's text content.
     *
     * 附件的文本内容
     *
     * @var string
     */
    public $content;

    /**
     * A plain-text summary of the attachment.
     *
     * 附件的纯文本摘要
     *
     * @var string
     */
    public $fallback;

    /**
     * The attachment's color.
     *
     * 附件的颜色
     *
     * @var string
     */
    public $color;

    /**
     * The attachment's fields.
     *
     * 附件的字段
     *
     * @var array
     */
    public $fields;

    /**
     * The fields containing markdown.
     *
     * 字段包含markdown
     *
     * @var array
     */
    public $markdown;

    /**
     * The attachment's footer.
     *
     * 附件的底部
     *
     * @var string
     */
    public $footer;

    /**
     * The attachment's footer icon.
     *
     * 附件的底部icon
     *
     * @var string
     */
    public $footerIcon;

    /**
     * The attachment's timestamp.
     *
     * 附件的时间戳
     *
     * @var int
     */
    public $timestamp;

    /**
     * Set the title of the attachment.
     *
     * 设置附件的标题
     *
     * @param  string  $title
     * @param  string  $url
     * @return $this
     */
    public function title($title, $url = null)
    {
        $this->title = $title;
        $this->url = $url;

        return $this;
    }

    /**
     * Set the content (text) of the attachment.
     *
     * 设置附件的内容(文本)
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
     * A plain-text summary of the attachment.
     *
     * 附件的纯文本摘要
     *
     * @param  string  $fallback
     * @return $this
     */
    public function fallback($fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }

    /**
     * Set the color of the attachment.
     *
     * 设置附件的颜色
     *
     * @param  string  $color
     * @return $this
     */
    public function color($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Add a field to the attachment.
     *
     * 添加附件的字段
     *
     * @param  \Closure|string $title
     * @param  string $content
     * @return $this
     */
    public function field($title, $content = '')
    {
        if (is_callable($title)) {
            $callback = $title;

            $callback($attachmentField = new SlackAttachmentField);

            $this->fields[] = $attachmentField;

            return $this;
        }

        $this->fields[$title] = $content;

        return $this;
    }

    /**
     * Set the fields of the attachment.
     *
     * 设置附件的字段
     *
     * @param  array  $fields
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Set the fields containing markdown.
     *
     * 设置包含markdown的字段
     *
     * @param  array  $fields
     * @return $this
     */
    public function markdown(array $fields)
    {
        $this->markdown = $fields;

        return $this;
    }

    /**
     * Set the footer content.
     *
     * 设置底部内容
     *
     * @param  string  $footer
     * @return $this
     */
    public function footer($footer)
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * Set the footer icon.
     *
     * 设置底部icon
     *
     * @param  string $icon
     * @return $this
     */
    public function footerIcon($icon)
    {
        $this->footerIcon = $icon;

        return $this;
    }

    /**
     * Set the timestamp.
     *
     * 设置时间戳
     *
     * @param  Carbon  $timestamp
     * @return $this
     */
    public function timestamp(Carbon $timestamp)
    {
        $this->timestamp = $timestamp->getTimestamp();

        return $this;
    }
}
