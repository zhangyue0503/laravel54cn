<?php

namespace Illuminate\Contracts\Support;

interface Renderable
{
    /**
     * Get the evaluated contents of the object.
     *
     * 获取对象的评价内容
     *
     * @return string
     */
    public function render();
}
