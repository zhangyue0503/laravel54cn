<?php

namespace Illuminate\Validation;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ValidationData
{
    public static function initializeAndGatherData($attribute, $masterData)
    {
        //用点对多维关联数组进行扁平化    收集带有任何缺失属性的属性数据的副本
        $data = Arr::dot(static::initializeAttributeOnData($attribute, $masterData));
        //                            获取给定通配符属性的所有精确属性值
        return array_merge($data, static::extractValuesForWildcards(
            $masterData, $data, $attribute
        ));
    }

    /**
     * Gather a copy of the attribute data filled with any missing attributes.
     *
     * 收集带有任何缺失属性的属性数据的副本
     *
     * @param  string  $attribute
     * @param  array  $masterData
     * @return array
     */
    protected static function initializeAttributeOnData($attribute, $masterData)
    {
        //                   获取属性名的显式部分
        $explicitPath = static::getLeadingExplicitAttributePath($attribute);
        //               根据给定的点指定路径提取数据
        $data = static::extractDataFromPath($explicitPath, $masterData);
        //确定一个给定的字符串包含另一个字符串          确定给定的字符串的结束是否是给定的子字符串
        if (! Str::contains($attribute, '*') || Str::endsWith($attribute, '*')) {
            return $data;
        }

        return data_set($data, $attribute, null, true);
    }

    /**
     * Get all of the exact attribute values for a given wildcard attribute.
     *
     * 获取给定通配符属性的所有精确属性值
     *
     * @param  array  $masterData
     * @param  array  $data
     * @param  string  $attribute
     * @return array
     */
    protected static function extractValuesForWildcards($masterData, $data, $attribute)
    {
        $keys = [];

        $pattern = str_replace('\*', '[^\.]+', preg_quote($attribute));

        foreach ($data as $key => $value) {
            if ((bool) preg_match('/^'.$pattern.'/', $key, $matches)) {
                $keys[] = $matches[0];
            }
        }

        $keys = array_unique($keys);

        $data = [];

        foreach ($keys as $key) {
            //             使用“点”符号从数组中获取一个项
            $data[$key] = array_get($masterData, $key);
        }

        return $data;
    }

    /**
     * Extract data based on the given dot-notated path.
     *
     * 根据给定的点指定路径提取数据
     *
     * Used to extract a sub-section of the data for faster iteration.
     *
     * 用于为更快的迭代提取数据的子部分
     *
     * @param  string  $attribute
     * @param  array  $masterData
     * @return array
     */
    public static function extractDataFromPath($attribute, $masterData)
    {
        $results = [];
        //使用“点”符号从数组中获取一个项
        $value = Arr::get($masterData, $attribute, '__missing__');

        if ($value != '__missing__') {
            //使用“点”符号将数组项设置为给定值
            Arr::set($results, $attribute, $value);
        }

        return $results;
    }

    /**
     * Get the explicit part of the attribute name.
     *
     * 获取属性名的显式部分
     *
     * E.g. 'foo.bar.*.baz' -> 'foo.bar'
     *
     * Allows us to not spin through all of the flattened data for some operations.
     *
     * 允许我们在一些操作中不旋转所有的平化数据
     *
     * @param  string  $attribute
     * @return string
     */
    public static function getLeadingExplicitAttributePath($attribute)
    {
        return rtrim(explode('*', $attribute)[0], '.') ?: null;
    }
}
