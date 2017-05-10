<?php

namespace Illuminate\Contracts\View;

use Illuminate\Contracts\Support\Renderable;

interface View extends Renderable
{
    /**
     * Get the name of the view.
     *
     * 获取视图的名称
     *
     * @return string
     */
    public function name();

    /**
     * Add a piece of data to the view.
     *
     * 将数据添加到视图中
     *
     * @param  string|array  $key
     * @param  mixed   $value
     * @return $this
     */
    public function with($key, $value = null);
}
