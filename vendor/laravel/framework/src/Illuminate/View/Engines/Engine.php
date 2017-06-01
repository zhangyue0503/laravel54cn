<?php

namespace Illuminate\View\Engines;

abstract class Engine
{
    /**
     * The view that was last to be rendered.
     *
     * 最后要呈现的视图
     *
     * @var string
     */
    protected $lastRendered;

    /**
     * Get the last view that was rendered.
     *
     * 得到渲染的最后一个视图
     *
     * @return string
     */
    public function getLastRendered()
    {
        return $this->lastRendered;
    }
}
