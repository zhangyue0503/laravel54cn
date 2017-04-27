<?php

namespace Illuminate\Support;

use ArrayAccess;
use Illuminate\Support\Traits\Macroable;

class Arr
{
    use Macroable;

    /**
     * Determine whether the given value is array accessible.
     *
     * 确定给定值是否是可访问数组
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     *
     * 如果不存在，使用“点”表示法将一个元素添加到数组中
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    public static function add($array, $key, $value)
    {
        if (is_null(static::get($array, $key))) {//使用“点”符号从数组中获取一个项
            static::set($array, $key, $value);//使用“点”符号将数组项设置为给定值
        }

        return $array;
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * 将多维数组折叠为单个数组
     *
     * @param  array  $array
     * @return array
     */
    public static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();//获取集合中的所有项目
            } elseif (! is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * 将数组分成两个数组。一个是全部的key，另一个是全部的value
     *
     * @param  array  $array
     * @return array
     */
    public static function divide($array)
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * 用点对多维关联数组进行扁平化
     *
     * @param  array   $array
     * @param  string  $prepend
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                //                                      用点对多维关联数组进行扁平化
                $results = array_merge($results, static::dot($value, $prepend.$key.'.'));
            } else {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Get all of the given array except for a specified array of items.
     *
     * 获取指定数组，除了指定的数组项
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function except($array, $keys)
    {
        static::forget($array, $keys);//使用“点”符号从给定数组中移除一个或多个数组项

        return $array;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * 确定给定的key是否存在于提供的数组中
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int  $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Return the first element in an array passing a given truth test.
     *
     * 通过给定的真值测试返回数组中的第一个元素
     *
     * @param  array  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public static function first($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return value($default);
            }

            foreach ($array as $item) {
                return $item;
            }
        }

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * 返回经过给定的真值测试的数组中的最后一个元素
     *
     * @param  array  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public static function last($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? value($default) : end($array);
        }
        //通过给定的真值测试返回数组中的第一个元素
        return static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * 将多维数组变平为单级
     *
     * @param  array  $array
     * @param  int  $depth
     * @return array
     */
    public static function flatten($array, $depth = INF)
    {
        return array_reduce($array, function ($result, $item) use ($depth) {
            //                                       获取集合中的所有项目
            $item = $item instanceof Collection ? $item->all() : $item;

            if (! is_array($item)) {
                return array_merge($result, [$item]);
            } elseif ($depth === 1) {
                return array_merge($result, array_values($item));
            } else {
                //                            将多维数组变平为单级
                return array_merge($result, static::flatten($item, $depth - 1));
            }
        }, []);
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * 使用“点”符号从给定数组中移除一个或多个数组项
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return void
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            // 如果确定键存在于顶层，删除它
            if (static::exists($array, $key)) {//确定给定的key是否存在于提供的数组中
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            // 每次循环前清理
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * 使用“点”符号从数组中获取一个项
     *
     * @param  \ArrayAccess|array  $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (! static::accessible($array)) { // 确定给定值是否是可访问数组
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) { // 确定$key在数组中是否存在
            return $array[$key];
        }
        // 循环最终获取值
        foreach (explode('.', $key) as $segment) {
            //确定给定值是否是可访问数组              确定给定的key是否存在于提供的数组中
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * 使用“点”符号检查数组中的项或项是否存在
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|array  $keys
     * @return bool
     */
    public static function has($array, $keys)
    {
        if (is_null($keys)) {
            return false;
        }

        $keys = (array) $keys;

        if (! $array) {
            return false;
        }

        if ($keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (static::exists($array, $key)) {//确定给定的key是否存在于提供的数组中
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                //确定给定值是否是可访问数组              确定给定的key是否存在于提供的数组中
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determines if an array is associative.
     *
     * 确定数组是否为关联
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * 数组是“关联”的，如果它没有连续的数字键开始为零
     *
     * @param  array  $array
     * @return bool
     */
    public static function isAssoc(array $array)
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    /**
     * Get a subset of the items from the given array.
     *
     * 从给定数组中获取项目的子集
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function only($array, $keys)
    {
        //比较数组，返回交集（只比较键名）        交换数组中的键和值
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Pluck an array of values from an array.
     *
     * 从数组中提取数组值
     *
     * @param  array  $array
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    public static function pluck($array, $value, $key = null)
    {
        $results = [];
        //                      将包含.的“value”和“key”拆分提取成多维数组
        list($value, $key) = static::explodePluckParameters($value, $key);

        foreach ($array as $item) {
            $itemValue = data_get($item, $value);

            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            //
            // 如果key是“null”，我们将值追加到数组并继续循环。否则，我们将使用从开发者接收的key的值来返回最终数组形式。
            //
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = data_get($item, $key);

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     *
     * 将包含.的“value”和“key”拆分提取成多维数组
     *
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    protected static function explodePluckParameters($value, $key)
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * Push an item onto the beginning of an array.
     *
     * 将项目推到数组的开头
     *
     * @param  array  $array
     * @param  mixed  $value
     * @param  mixed  $key
     * @return array
     */
    public static function prepend($array, $value, $key = null)
    {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    /**
     * Get a value from the array, and remove it.
     *
     * 从数组中获取值，并将其移除
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);//使用“点”符号从数组中获取一个项

        static::forget($array, $key);//使用“点”符号从给定数组中移除一个或多个数组项

        return $value;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * 使用“点”符号将数组项设置为给定值
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * 如果没有给定key的方法，整个数组将被替换
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            //
            // 如果key不存在于这个深度，我们将创建一个空数组来保存下一个值，这样我们就可以创建数组以在正确的深度保存最终值
            // 然后我们将继续挖掘数组
            //
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Shuffle the given array and return the result.
     *
     * 对给定数组进行洗牌并返回结果
     *
     * @param  array  $array
     * @return array
     */
    public static function shuffle($array)
    {
        shuffle($array);

        return $array;
    }

    /**
     * Sort the array using the given callback or "dot" notation.
     *
     * 使用给定的回调或“点”符号对数组进行排序
     *
     * @param  array  $array
     * @param  callable|string  $callback
     * @return array
     */
    public static function sort($array, $callback)
    {
        //创建一个新的集合实例，如果该值不是一个准备好的->使用给定的回调排序集合->获取集合中的所有项目
        return Collection::make($array)->sortBy($callback)->all();
    }

    /**
     * Recursively sort an array by keys and values.
     *
     * 递归排序数组的键和值
     *
     * @param  array  $array
     * @return array
     */
    public static function sortRecursive($array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                //           递归排序数组的键和值
                $value = static::sortRecursive($value);
            }
        }

        if (static::isAssoc($array)) {//确定数组是否为关联
            ksort($array);
        } else {
            sort($array);
        }

        return $array;
    }

    /**
     * Filter the array using the given callback.
     *
     * 使用给定的回调筛选数组
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    public static function where($array, callable $callback)
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }
}
