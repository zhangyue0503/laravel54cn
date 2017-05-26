<?php

namespace Illuminate\Routing;

use Illuminate\Support\Arr;

class RouteGroup
{
    /**
     * Merge route groups into a new array.
     *
     * 将路由组合并到新数组中
     *
     * @param  array  $new
     * @param  array  $old
     * @return array
     */
    public static function merge($new, $old)
    {
        if (isset($new['domain'])) {
            unset($old['domain']);
        }
        //                   格式化新组属性的“as”子句
        $new = array_merge(static::formatAs($new, $old), [
            'namespace' => static::formatNamespace($new, $old),//为新组属性格式化命名空间
            'prefix' => static::formatPrefix($new, $old),//为新组属性格式化命名空间
            'where' => static::formatWhere($new, $old),//对新组属性的“wheres”进行格式化
        ]);
        //                             获取指定数组，除了指定的数组项
        return array_merge_recursive(Arr::except(
            $old, ['namespace', 'prefix', 'where', 'as']
        ), $new);
    }

    /**
     * Format the namespace for the new group attributes.
     *
     * 为新组属性格式化命名空间
     *
     * @param  array  $new
     * @param  array  $old
     * @return string|null
     */
    protected static function formatNamespace($new, $old)
    {
        if (isset($new['namespace'])) {
            return isset($old['namespace'])
                    ? trim($old['namespace'], '\\').'\\'.trim($new['namespace'], '\\')
                    : trim($new['namespace'], '\\');
        }

        return isset($old['namespace']) ? $old['namespace'] : null;
    }

    /**
     * Format the prefix for the new group attributes.
     *
     * 为新组属性格式化命名空间
     *
     * @param  array  $new
     * @param  array  $old
     * @return string|null
     */
    protected static function formatPrefix($new, $old)
    {
        //使用“点”符号从数组中获取一个项
        $old = Arr::get($old, 'prefix');

        return isset($new['prefix']) ? trim($old, '/').'/'.trim($new['prefix'], '/') : $old;
    }

    /**
     * Format the "wheres" for the new group attributes.
     *
     * 对新组属性的“wheres”进行格式化
     *
     * @param  array  $new
     * @param  array  $old
     * @return array
     */
    protected static function formatWhere($new, $old)
    {
        return array_merge(
            isset($old['where']) ? $old['where'] : [],
            isset($new['where']) ? $new['where'] : []
        );
    }

    /**
     * Format the "as" clause of the new group attributes.
     *
     * 格式化新组属性的“as”子句
     *
     * @param  array  $new
     * @param  array  $old
     * @return array
     */
    protected static function formatAs($new, $old)
    {
        if (isset($old['as'])) {
            //                      使用“点”符号从数组中获取一个项
            $new['as'] = $old['as'].Arr::get($new, 'as', '');
        }

        return $new;
    }
}
