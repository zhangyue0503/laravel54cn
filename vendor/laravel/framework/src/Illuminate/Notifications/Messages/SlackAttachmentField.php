<?php

namespace Illuminate\Notifications\Messages;

class SlackAttachmentField
{
    /**
     * The title field of the attachment field.
     *
     * 附件字段的标题字段
     *
     * @var string
     */
    protected $title;

    /**
     * The content of the attachment field.
     *
     * 附件字段的内容
     *
     * @var string
     */
    protected $content;

    /**
     * Whether the content is short.
     *
     * 内容是否简短
     *
     * @var bool
     */
    protected $short = true;

    /**
     * Set the title of the field.
     *
     * 设置字段的标题
     *
     * @param  string $title
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the content of the field.
     *
     * 设置字段的内容
     *
     * @param  string $content
     * @return $this
     */
    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Indicates that the content should not be displayed side-by-side with other fields.
     *
     * 表明内容不应该与其他字段并排显示
     *
     * @return $this
     */
    public function long()
    {
        $this->short = false;

        return $this;
    }

    /**
     * Get the array representation of the attachment field.
     *
     * 获取附件字段的数组表示
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'title' => $this->title,
            'value' => $this->content,
            'short' => $this->short,
        ];
    }
}
