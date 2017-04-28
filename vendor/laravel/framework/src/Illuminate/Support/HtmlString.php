<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Support\Htmlable;
//HTML字符串
class HtmlString implements Htmlable
{
    /**
     * The HTML string.
     *
     * HTML字符串
     *
     * @var string
     */
    protected $html;

    /**
     * Create a new HTML string instance.
     *
     * 创建一个新的HTML字符串实例
     *
     * @param  string  $html
     * @return void
     */
    public function __construct($html)
    {
        $this->html = $html;
    }

    /**
     * Get the HTML string.
     *
     * 获取HTML字符串
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->html;
    }

    /**
     * Get the HTML string.
     *
     * 获取HTML字符串
     *
     * @return string
     */
    public function __toString()
    {
        //获取HTML字符串
        return $this->toHtml();
    }
}
