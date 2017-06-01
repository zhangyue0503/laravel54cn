<?php

namespace Illuminate\View\Engines;

class FileEngine implements EngineInterface
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
    public function get($path, array $data = [])
    {
        return file_get_contents($path);
    }
}
