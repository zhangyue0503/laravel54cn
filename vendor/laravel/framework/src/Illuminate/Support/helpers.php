<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Debug\Dumper;
use Illuminate\Contracts\Support\Htmlable;

if (! function_exists('append_config')) {
    /**
     * Assign high numeric IDs to a config item to force appending.
     *
     * 将高数值IDs分配给配置项以强制追加
     *
     * @param  array  $array
     * @return array
     */
    function append_config(array $array)
    {
        $start = 9999;

        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                $start++;
                //               将高数值id分配给配置项以强制追加
                $array[$start] = Arr::pull($array, $key);
            }
        }

        return $array;
    }
}

if (! function_exists('array_add')) {
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
    function array_add($array, $key, $value)
    {
        //           如果不存在，使用“点”表示法将一个元素添加到数组中
        return Arr::add($array, $key, $value);
    }
}

if (! function_exists('array_collapse')) {
    /**
     * Collapse an array of arrays into a single array.
     *
     * 将多维数组折叠为单个数组
     *
     * @param  array  $array
     * @return array
     */
    function array_collapse($array)
    {
        //将多维数组折叠为单个数组
        return Arr::collapse($array);
    }
}

if (! function_exists('array_divide')) {
    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * 将数组分成两个数组
     * 一个是keys，另一个有values
     *
     * @param  array  $array
     * @return array
     */
    function array_divide($array)
    {
        return Arr::divide($array);//将数组分成两个数组。一个是全部的key，另一个是全部的value
    }
}

if (! function_exists('array_dot')) {
    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * 用点对多维关联数组进行扁平化
     *
     * @param  array   $array
     * @param  string  $prepend
     * @return array
     */
    function array_dot($array, $prepend = '')
    {
        //用点对多维关联数组进行扁平化
        return Arr::dot($array, $prepend);
    }
}

if (! function_exists('array_except')) {
    /**
     * Get all of the given array except for a specified array of items.
     *
     * 获取指定数组，除了指定的数组项
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    function array_except($array, $keys)
    {
        //获取指定数组，除了指定的数组项
        return Arr::except($array, $keys);
    }
}

if (! function_exists('array_first')) {
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
    function array_first($array, callable $callback = null, $default = null)
    {
        //通过给定的真值测试返回数组中的第一个元素
        return Arr::first($array, $callback, $default);
    }
}

if (! function_exists('array_flatten')) {
    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * 将多维数组变平为单级
     *
     * @param  array  $array
     * @param  int  $depth
     * @return array
     */
    function array_flatten($array, $depth = INF)
    {
        //将多维数组变平为单级
        return Arr::flatten($array, $depth);
    }
}

if (! function_exists('array_forget')) {
    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * 使用“点”符号从给定数组中移除一个或多个数组项
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return void
     */
    function array_forget(&$array, $keys)
    {
        // 使用“点”符号从给定数组中移除一个或多个数组项
        return Arr::forget($array, $keys);
    }
}

if (! function_exists('array_get')) {
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
    function array_get($array, $key, $default = null)
    {
        //使用“点”符号从数组中获取一个项
        return Arr::get($array, $key, $default);
    }
}

if (! function_exists('array_has')) {
    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * 使用“点”符号检查数组中的项或项是否存在
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|array  $keys
     * @return bool
     */
    function array_has($array, $keys)
    {
        return Arr::has($array, $keys);//使用“点”符号检查数组中的项或项是否存在
    }
}

if (! function_exists('array_last')) {
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
    function array_last($array, callable $callback = null, $default = null)
    {
        //返回经过给定的真值测试的数组中的最后一个元素
        return Arr::last($array, $callback, $default);
    }
}

if (! function_exists('array_only')) {
    /**
     * Get a subset of the items from the given array.
     *
     * 从给定数组中获取项目的子集
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    function array_only($array, $keys)
    {
        //从给定数组中获取项目的子集
        return Arr::only($array, $keys);
    }
}

if (! function_exists('array_pluck')) {
    /**
     * Pluck an array of values from an array.
     *
     * 从数组中提取数组值
     *
     * @param  array   $array
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    function array_pluck($array, $value, $key = null)
    {
        return Arr::pluck($array, $value, $key);
    }
}

if (! function_exists('array_prepend')) {
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
    function array_prepend($array, $value, $key = null)
    {
        return Arr::prepend($array, $value, $key);//将项目推到数组的开头
    }
}

if (! function_exists('array_pull')) {
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
    function array_pull(&$array, $key, $default = null)
    {
        return Arr::pull($array, $key, $default);//从数组中获取值，并将其移除
    }
}

if (! function_exists('array_set')) {
    /**
     * Set an array item to a given value using "dot" notation.
     *
     * 使用“点”符号将数组项设置为给定值
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * 如果没有给定的方法，整个数组将被替换
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    function array_set(&$array, $key, $value)
    {
        //如果没有给定的方法，整个数组将被替换
        return Arr::set($array, $key, $value);
    }
}

if (! function_exists('array_sort')) {
    /**
     * Sort the array using the given callback.
     *
     * 使用给定的回调排序数组
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    function array_sort($array, callable $callback)
    {
        //使用给定的回调或“点”符号对数组进行排序
        return Arr::sort($array, $callback);
    }
}

if (! function_exists('array_sort_recursive')) {
    /**
     * Recursively sort an array by keys and values.
     *
     * 递归排序数组的键和值
     *
     * @param  array  $array
     * @return array
     */
    function array_sort_recursive($array)
    {
        return Arr::sortRecursive($array);//递归排序数组的键和值
    }
}

if (! function_exists('array_where')) {
    /**
     * Filter the array using the given callback.
     *
     * 使用给定的回调筛选数组
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    function array_where($array, callable $callback)
    {
        return Arr::where($array, $callback);//使用给定的回调筛选数组
    }
}

if (! function_exists('array_wrap')) {
    /**
     * If the given value is not an array, wrap it in one.
     *
     * 如果给定值不是数组，请将其包在一个数组中
     *
     * @param  mixed  $value
     * @return array
     */
    function array_wrap($value)
    {
        return ! is_array($value) ? [$value] : $value;
    }
}

if (! function_exists('camel_case')) {
    /**
     * Convert a value to camel case.
     *
     * 转换值为驼峰命名
     *
     * @param  string  $value
     * @return string
     */
    function camel_case($value)
    {
        return Str::camel($value);//转换值为驼峰命名
    }
}

if (! function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     *
     * 获取类的“basename“从给定的对象/类
     *
     * @param  string|object  $class
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (! function_exists('class_uses_recursive')) {
    /**
     * Returns all traits used by a class, its subclasses and trait of their traits.
     *
     * 返回类所使用的所有特性、子类和它们的特征
     *
     * @param  object|string  $class
     * @return array
     */
    function class_uses_recursive($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_merge([$class => $class], class_parents($class)) as $class) {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (! function_exists('collect')) {
    /**
     * Create a collection from the given value.
     *
     * 通过给定的值创建集合对象
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Collection
     */
    function collect($value = null)
    {
        //         创建一个新的集合实例，如果该值不是一个准备好的
        return new Collection($value);
    }
}

if (! function_exists('data_fill')) {
    /**
     * Fill in data where it's missing.
     *
     * 填写丢失的数据
     *
     * @param  mixed   $target
     * @param  string|array  $key
     * @param  mixed  $value
     * @return mixed
     */
    function data_fill(&$target, $key, $value)
    {
        //      使用点符号在数组或对象上设置项
        return data_set($target, $key, $value, false);
    }
}

if (! function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * 使用“点”符号从数组或对象中获取项
     *
     * @param  mixed   $target
     * @param  string|array  $key
     * @param  mixed   $default
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        // 如果包含点，拆成数组，如user.id
        $key = is_array($key) ? $key : explode('.', $key);
        //循环第一个元素并删除
        while (! is_null($segment = array_shift($key))) {

            if ($segment === '*') { //如果是*
                if ($target instanceof Collection) { //如果请求的数组是laravel集合对象
                    $target = $target->all();
                } elseif (! is_array($target)) { //如果不是数组
                    return value($default); //返回值
                }
                // Illuminate\Support\Arr，从数组中提取值，循环提取
                $result = Arr::pluck($target, $key);
                // 返回，如果$key中包含*，将提取出的值折叠为单个数组，否则直接返回结果值
                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            /**
             * 假设$key = user.id
             * 原始$target = ['user'=>['id'=>'']]
             * 第一次循环，$segment = user，$target = $target['user']，$target = ['id'=>'']
             * 第二次循环，$segment = id，取到数据，$target = $target['user']
             */
            //如果 $target是可访问的数组 并且 $segment存在于$target中
            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment]; // $target变成第一级的数组
            } elseif (is_object($target) && isset($target->{$segment})) { // $target是对象 并且 $segment存在于$target中
                $target = $target->{$segment};
            } else { // 否则返回默认值
                return value($default);
            }
        }

        return $target;
    }
}

if (! function_exists('data_set')) {
    /**
     * Set an item on an array or object using dot notation.
     *
     * 使用点符号在数组或对象上设置项
     *
     * @param  mixed  $target
     * @param  string|array  $key
     * @param  mixed  $value
     * @param  bool  $overwrite
     * @return mixed
     */
    function data_set(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
            if (! Arr::accessible($target)) {//确定给定值是否是可访问数组
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (Arr::accessible($target)) {//确定给定值是否是可访问数组
            if ($segments) {
                if (! Arr::exists($target, $segment)) {//确定给定的key是否存在于提供的数组中
                    $target[$segment] = [];
                }
                //使用点符号在数组或对象上设置项
                data_set($target[$segment], $segments, $value, $overwrite);
                //                      确定给定的key是否存在于提供的数组中
            } elseif ($overwrite || ! Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (! isset($target->{$segment})) {
                    $target->{$segment} = [];
                }
                //使用点符号在数组或对象上设置项
                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || ! isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];

            if ($segments) {
                //使用点符号在数组或对象上设置项
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }
}

if (! function_exists('dd')) {
    /**
     * Dump the passed variables and end the script.
     *
     * 倾倒传递的变量并结束脚本
     *
     * @param  mixed
     * @return void
     */
    function dd()
    {
        array_map(function ($x) {
            (new Dumper)->dump($x);//简洁地倾倒一个值
        }, func_get_args());

        die(1);
    }
}

if (! function_exists('e')) {
    /**
     * Escape HTML special characters in a string.
     *
     * 编码字符串中的HTML特殊字符
     *
     * @param  \Illuminate\Contracts\Support\Htmlable|string  $value
     * @return string
     */
    function e($value)
    {
        if ($value instanceof Htmlable) {
            return $value->toHtml();//获取内容作为HTML字符串
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (! function_exists('ends_with')) {
    /**
     * Determine if a given string ends with a given substring.
     *
     * 如果一个给定的字符串结束，确定一个给定的子字符串
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function ends_with($haystack, $needles)
    {
        //    确定一个给定的字符串包含另一个字符串
        return Str::endsWith($haystack, $needles);
    }
}

if (! function_exists('head')) {
    /**
     * Get the first element of an array. Useful for method chaining.
     *
     * 获取数组的第一个元素
     * 用于方法链接
     *
     * @param  array  $array
     * @return mixed
     */
    function head($array)
    {
        return reset($array);
    }
}

if (! function_exists('kebab_case')) {
    /**
     * Convert a string to kebab case.
     *
     * 将字符串转换为串格式（短横线隔开）
     *
     * @param  string  $value
     * @return string
     */
    function kebab_case($value)
    {
        //将字符串转换为串格式（短横线隔开）
        return Str::kebab($value);
    }
}

if (! function_exists('last')) {
    /**
     * Get the last element from an array.
     *
     * 从数组中获取最后一个元素
     *
     * @param  array  $array
     * @return mixed
     */
    function last($array)
    {
        return end($array);
    }
}

if (! function_exists('object_get')) {
    /**
     * Get an item from an object using "dot" notation.
     *
     * 使用“点”符号从对象获取项目
     *
     * @param  object  $object
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function object_get($object, $key, $default = null)
    {
        if (is_null($key) || trim($key) == '') {
            return $object;
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_object($object) || ! isset($object->{$segment})) {
                return value($default);
            }

            $object = $object->{$segment};
        }

        return $object;
    }
}

if (! function_exists('preg_replace_array')) {
    /**
     * Replace a given pattern with each value in the array in sequentially.
     *
     * 按顺序替换数组中每个值的给定模式
     *
     * @param  string  $pattern
     * @param  array   $replacements
     * @param  string  $subject
     * @return string
     */
    function preg_replace_array($pattern, array $replacements, $subject)
    {
        return preg_replace_callback($pattern, function () use (&$replacements) {
            foreach ($replacements as $key => $value) {
                return array_shift($replacements);
            }
        }, $subject);
    }
}

if (! function_exists('retry')) {
    /**
     * Retry an operation a given number of times.
     *
     * 重试给定次数的操作
     *
     * @param  int  $times
     * @param  callable  $callback
     * @param  int  $sleep
     * @return mixed
     *
     * @throws \Exception
     */
    function retry($times, callable $callback, $sleep = 0)
    {
        $times--;

        beginning:
        try {
            return $callback();
        } catch (Exception $e) {
            if (! $times) {
                throw $e;
            }

            $times--;

            if ($sleep) {
                usleep($sleep * 1000);
            }

            goto beginning;
        }
    }
}

if (! function_exists('snake_case')) {
    /**
     * Convert a string to snake case.
     *
     * 转换字符串为蛇形命名
     *
     * @param  string  $value
     * @param  string  $delimiter
     * @return string
     */
    function snake_case($value, $delimiter = '_')
    {
        return Str::snake($value, $delimiter); //将字符串转换为蛇形命名
    }
}

if (! function_exists('starts_with')) {
    /**
     * Determine if a given string starts with a given substring.
     *
     * 确定给定的子字符串是否属于给定的字符串
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function starts_with($haystack, $needles)
    {
        //确定给定的子字符串是否属于给定的字符串
        return Str::startsWith($haystack, $needles);
    }
}

if (! function_exists('str_contains')) {
    /**
     * Determine if a given string contains a given substring.
     *
     * 确定给定的字符串是否包含给定的子字符串
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function str_contains($haystack, $needles)
    {
        //确定一个给定的字符串包含另一个字符串
        return Str::contains($haystack, $needles);
    }
}

if (! function_exists('str_finish')) {
    /**
     * Cap a string with a single instance of a given value.
     *
     * 使用给定的值覆盖单个实例字符串
     *
     * @param  string  $value
     * @param  string  $cap
     * @return string
     */
    function str_finish($value, $cap)
    {
        //使用给定的值覆盖单个实例字符串
        return Str::finish($value, $cap);
    }
}

if (! function_exists('str_is')) {
    /**
     * Determine if a given string matches a given pattern.
     *
     * 确定给定的字符串是否与给定的模式匹配
     *
     * @param  string  $pattern
     * @param  string  $value
     * @return bool
     */
    function str_is($pattern, $value)
    {
        //确定给定的字符串是否与给定的模式匹配
        return Str::is($pattern, $value);
    }
}

if (! function_exists('str_limit')) {
    /**
     * Limit the number of characters in a string.
     *
     * 限制字符串中字符的个数
     *
     * @param  string  $value
     * @param  int     $limit
     * @param  string  $end
     * @return string
     */
    function str_limit($value, $limit = 100, $end = '...')
    {
        //限制字符串中字符的个数
        return Str::limit($value, $limit, $end);
    }
}

if (! function_exists('str_plural')) {
    /**
     * Get the plural form of an English word.
     *
     * 获取一个英语单词的复数形式
     *
     * @param  string  $value
     * @param  int     $count
     * @return string
     */
    function str_plural($value, $count = 2)
    {
        //获取一个英语单词的复数形式
        return Str::plural($value, $count);
    }
}

if (! function_exists('str_random')) {
    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * 生成一个更真实的“随机”alpha数字字符串
     *
     * @param  int  $length
     * @return string
     *
     * @throws \RuntimeException
     */
    function str_random($length = 16)
    {
        //生成一个更真实的“随机”alpha数字字符串
        return Str::random($length);
    }
}

if (! function_exists('str_replace_array')) {
    /**
     * Replace a given value in the string sequentially with an array.
     *
     * 用数组顺序替换字符串中的给定值
     *
     * @param  string  $search
     * @param  array   $replace
     * @param  string  $subject
     * @return string
     */
    function str_replace_array($search, array $replace, $subject)
    {
        //用数组顺序替换字符串中的给定值
        return Str::replaceArray($search, $replace, $subject);
    }
}

if (! function_exists('str_replace_first')) {
    /**
     * Replace the first occurrence of a given value in the string.
     *
     * 替换字符串中第一次出现的给定值
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $subject
     * @return string
     */
    function str_replace_first($search, $replace, $subject)
    {
        //替换字符串中第一次出现的给定值
        return Str::replaceFirst($search, $replace, $subject);
    }
}

if (! function_exists('str_replace_last')) {
    /**
     * Replace the last occurrence of a given value in the string.
     *
     * 替换字符串中最后一次出现的给定值
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $subject
     * @return string
     */
    function str_replace_last($search, $replace, $subject)
    {
        //替换字符串中最后一次出现的给定值
        return Str::replaceLast($search, $replace, $subject);
    }
}

if (! function_exists('str_singular')) {
    /**
     * Get the singular form of an English word.
     *
     * 得到一个英语单词的单数形式
     *
     * @param  string  $value
     * @return string
     */
    function str_singular($value)
    {
        //得到一个英语单词的单数形式
        return Str::singular($value);
    }
}

if (! function_exists('str_slug')) {
    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * 生成一个URL友好的“slug”从一个给定的字符串
     *
     * @param  string  $title
     * @param  string  $separator
     * @return string
     */
    function str_slug($title, $separator = '-')
    {
        //生成一个URL友好的“slug”从一个给定的字符串
        return Str::slug($title, $separator);
    }
}

if (! function_exists('studly_case')) {
    /**
     * Convert a value to studly caps case.
     *
     * 将值转换为大驼峰
     *
     * @param  string  $value
     * @return string
     */
    function studly_case($value)
    {
        //将值转换为大驼峰
        return Str::studly($value);
    }
}

if (! function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
	 *
	 * 用给定的值调用给定的闭包，然后返回值
     *
     * @param  mixed  $value
     * @param  callable  $callback
     * @return mixed
     */
    function tap($value, $callback)
    {
        $callback($value);

        return $value;
    }
}

if (! function_exists('title_case')) {
    /**
     * Convert a value to title case.
     *
     * 给定字符串转换为首字母大写
     *
     * @param  string  $value
     * @return string
     */
    function title_case($value)
    {
        //给定字符串转换为首字母大写
        return Str::title($value);
    }
}

if (! function_exists('trait_uses_recursive')) {
    /**
     * Returns all traits used by a trait and its traits.
     *
     * 返回特性及其特性所使用的所有特性
     *
     * @param  string  $trait
     * @return array
     */
    function trait_uses_recursive($trait)
    {
        $traits = class_uses($trait);

        foreach ($traits as $trait) {
            //返回特性及其特性所使用的所有特性
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * 返回给定值的默认值
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value)
    {
        //如果是闭包，返回执行的闭包，否则返回值
        return $value instanceof Closure ? $value() : $value;
    }
}

if (! function_exists('windows_os')) {
    /**
     * Determine whether the current environment is Windows based.
     *
     * 确定当前环境是否是基于Windows的
     *
     * @return bool
     */
    function windows_os()
    {
        return strtolower(substr(PHP_OS, 0, 3)) === 'win';
    }
}

if (! function_exists('with')) {
    /**
     * Return the given object. Useful for chaining.
     *
     * 返回给定对象
     * 有用的链接
     *
     * @param  mixed  $object
     * @return mixed
     */
    function with($object)
    {
        return $object;
    }
}
