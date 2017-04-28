<?php

namespace Illuminate\Support;

use Doctrine\Common\Inflector\Inflector;
//复数
class Pluralizer
{
    /**
     * Uncountable word forms.
     *
     * 不可复数的单词
     *
     * @var array
     */
    public static $uncountable = [
        'audio',
        'bison',
        'chassis',
        'compensation',
        'coreopsis',
        'data',
        'deer',
        'education',
        'emoji',
        'equipment',
        'evidence',
        'feedback',
        'fish',
        'furniture',
        'gold',
        'information',
        'jedi',
        'knowledge',
        'love',
        'metadata',
        'money',
        'moose',
        'nutrition',
        'offspring',
        'plankton',
        'pokemon',
        'police',
        'rain',
        'rice',
        'series',
        'sheep',
        'species',
        'swine',
        'traffic',
        'wheat',
    ];

    /**
     * Get the plural form of an English word.
	 *
	 * 获取一个英语单词的复数形式
     *
     * @param  string  $value
     * @param  int     $count
     * @return string
     */
    public static function plural($value, $count = 2)
    {
        if ((int) $count === 1 || static::uncountable($value)) { //确定给定值是否不复数
            return $value;
        }

        $plural = Inflector::pluralize($value);//以复数形式返回一个单词

        return static::matchCase($plural, $value);//尝试匹配两个字符串的情况
    }

    /**
     * Get the singular form of an English word.
     *
     * 得到一个英语单词的单数形式
     *
     * @param  string  $value
     * @return string
     */
    public static function singular($value)
    {
        $singular = Inflector::singularize($value);//以单数形式返回一个单词

        return static::matchCase($singular, $value);//尝试匹配两个字符串的情况
    }

    /**
     * Determine if the given value is uncountable.
     *
     * 确定给定值是否不复数
     *
     * @param  string  $value
     * @return bool
     */
    protected static function uncountable($value)
    {
        return in_array(strtolower($value), static::$uncountable);
    }

    /**
     * Attempt to match the case on two strings.
     *
     * 尝试匹配两个字符串的情况
     *
     * @param  string  $value
     * @param  string  $comparison
     * @return string
     */
    protected static function matchCase($value, $comparison)
    {
        $functions = ['mb_strtolower', 'mb_strtoupper', 'ucfirst', 'ucwords'];

        foreach ($functions as $function) {
            if (call_user_func($function, $comparison) === $comparison) {
                return call_user_func($function, $value);
            }
        }

        return $value;
    }
}
