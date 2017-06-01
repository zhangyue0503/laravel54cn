<?php

namespace Illuminate\View;

class ViewName
{
    /**
     * Normalize the given event name.
     *
     * 规范化给定事件名称
     *
     * @param  string  $name
     * @return string
     */
    public static function normalize($name)
    {
        $delimiter = ViewFinderInterface::HINT_PATH_DELIMITER;//提示路径分隔符值

        if (strpos($name, $delimiter) === false) {
            return str_replace('/', '.', $name);
        }

        list($namespace, $name) = explode($delimiter, $name);

        return $namespace.$delimiter.str_replace('/', '.', $name);
    }
}
