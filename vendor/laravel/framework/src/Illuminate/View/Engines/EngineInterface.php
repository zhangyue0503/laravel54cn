<?php

namespace Illuminate\View\Engines;

interface EngineInterface
{
    /**
     * Get the evaluated contents of the view.
     *
     * 获取视图的评估内容
     *
     * @param  string  $path
     * @param  array   $data
     * @return string
     */
    public function get($path, array $data = []);
}
