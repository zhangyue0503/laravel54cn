<?php

namespace Illuminate\Contracts\Support;

interface Htmlable
{
    /**
     * Get content as a string of HTML.
     *
     * 获取内容作为HTML字符串
     *
     * @return string
     */
    public function toHtml();
}
