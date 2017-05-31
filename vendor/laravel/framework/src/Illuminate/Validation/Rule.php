<?php

namespace Illuminate\Validation;

use Illuminate\Support\Traits\Macroable;

class Rule
{
    use Macroable;

    /**
     * Get a dimensions constraint builder instance.
     *
     * 获得一个维度约束构建器实例
     *
     * @param  array  $constraints
     * @return \Illuminate\Validation\Rules\Dimensions
     */
    public static function dimensions(array $constraints = [])
    {
        return new Rules\Dimensions($constraints);
    }

    /**
     * Get a exists constraint builder instance.
     *
     * 获得一个存在约束构建器实例
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Illuminate\Validation\Rules\Exists
     */
    public static function exists($table, $column = 'NULL')
    {
        return new Rules\Exists($table, $column);
    }

    /**
     * Get an in constraint builder instance.
     *
     * 获得约束构建器实例
     *
     * @param  array  $values
     * @return \Illuminate\Validation\Rules\In
     */
    public static function in(array $values)
    {
        return new Rules\In($values);
    }

    /**
     * Get a not_in constraint builder instance.
     *
     * 获得一个约束构建器实例
     *
     * @param  array  $values
     * @return \Illuminate\Validation\Rules\NotIn
     */
    public static function notIn(array $values)
    {
        return new Rules\NotIn($values);
    }

    /**
     * Get a unique constraint builder instance.
     *
     * 获得一个惟一的约束构建实例
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Illuminate\Validation\Rules\Unique
     */
    public static function unique($table, $column = 'NULL')
    {
        return new Rules\Unique($table, $column);
    }
}
