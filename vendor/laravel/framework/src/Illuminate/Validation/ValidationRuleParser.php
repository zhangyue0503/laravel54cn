<?php

namespace Illuminate\Validation;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class ValidationRuleParser
{
    /**
     * The data being validated.
     *
     * 被验证的数据
     *
     * @var array
     */
    public $data;

    /**
     * The implicit attributes.
     *
     * 隐式属性
     *
     * @var array
     */
    public $implicitAttributes = [];

    /**
     * Create a new validation rule parser.
     *
     * 创建一个新的验证规则解析器
     *
     * @param  array  $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Parse the human-friendly rules into a full rules array for the validator.
     *
     * 将友好的规则解析为验证器的完整规则数组
     *
     * @param  array  $rules
     * @return \StdClass
     */
    public function explode($rules)
    {
        $this->implicitAttributes = [];

        $rules = $this->explodeRules($rules);//将规则引爆到一系列明确的规则中

        return (object) [
            'rules' => $rules,
            'implicitAttributes' => $this->implicitAttributes,
        ];
    }

    /**
     * Explode the rules into an array of explicit rules.
     *
     * 将规则引爆到一系列明确的规则中
     *
     * @param  array  $rules
     * @return array
     */
    protected function explodeRules($rules)
    {
        foreach ($rules as $key => $rule) {
            if (Str::contains($key, '*')) {//确定一个给定的字符串包含另一个字符串
                $rules = $this->explodeWildcardRules($rules, $key, [$rule]);// 定义一组规则，这些规则适用于数组属性中的每个元素

                unset($rules[$key]);
            } else {
                $rules[$key] = $this->explodeExplicitRule($rule);//如果需要，将显式规则放入数组中
            }
        }

        return $rules;
    }

    /**
     * Explode the explicit rule into an array if necessary.
     *
     * 如果需要，将显式规则放入数组中
     *
     * @param  mixed  $rule
     * @return array
     */
    protected function explodeExplicitRule($rule)
    {
        if (is_string($rule)) {
            return explode('|', $rule);
        } elseif (is_object($rule)) {
            return [$this->prepareRule($rule)];//为验证器准备给定的规则
        } else {
            return array_map([$this, 'prepareRule'], $rule);
        }
    }

    /**
     * Prepare the given rule for the Validator.
     *
     * 为验证器准备给定的规则
     *
     * @param  mixed  $rule
     * @return mixed
     */
    protected function prepareRule($rule)
    {
        if (! is_object($rule) ||
            ($rule instanceof Exists && $rule->queryCallbacks()) ||//获取规则的自定义查询回调
            ($rule instanceof Unique && $rule->queryCallbacks())) {
            return $rule;
        }

        return strval($rule);
    }

    /**
     * Define a set of rules that apply to each element in an array attribute.
     *
     * 定义一组规则，这些规则适用于数组属性中的每个元素
     *
     * @param  array  $results
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return array
     */
    protected function explodeWildcardRules($results, $attribute, $rules)
    {
        $pattern = str_replace('\*', '[^\.]*', preg_quote($attribute));

        $data = ValidationData::initializeAndGatherData($attribute, $this->data);

        foreach ($data as $key => $value) {
            //确定给定的子字符串是否属于给定的字符串
            if (Str::startsWith($key, $attribute) || (bool) preg_match('/^'.$pattern.'\z/', $key)) {
                foreach ((array) $rules as $rule) {
                    $this->implicitAttributes[$attribute][] = $key;
                    //将附加规则合并到一个给定的属性(s)
                    $results = $this->mergeRules($results, $key, $rule);
                }
            }
        }

        return $results;
    }

    /**
     * Merge additional rules into a given attribute(s).
     *
     * 将附加规则合并到一个给定的属性(s)
     *
     * @param  array  $results
     * @param  string|array  $attribute
     * @param  string|array  $rules
     * @return array
     */
    public function mergeRules($results, $attribute, $rules = [])
    {
        if (is_array($attribute)) {
            foreach ((array) $attribute as $innerAttribute => $innerRules) {
                //          将附加规则合并到给定属性中
                $results = $this->mergeRulesForAttribute($results, $innerAttribute, $innerRules);
            }

            return $results;
        }

        return $this->mergeRulesForAttribute(
            $results, $attribute, $rules
        );
    }

    /**
     * Merge additional rules into a given attribute.
     *
     * 将附加规则合并到给定属性中
     *
     * @param  array  $results
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return array
     */
    protected function mergeRulesForAttribute($results, $attribute, $rules)
    {
        // 将规则引爆到一系列明确的规则中
        $merge = head($this->explodeRules([$rules]));

        $results[$attribute] = array_merge(
            //                               如果需要，将显式规则放入数组中
            isset($results[$attribute]) ? $this->explodeExplicitRule($results[$attribute]) : [], $merge
        );

        return $results;
    }

    /**
     * Extract the rule name and parameters from a rule.
     *
     * 从规则中提取规则名和参数
     *
     * @param  array|string  $rules
     * @return array
     */
    public static function parse($rules)
    {
        if (is_array($rules)) {
            $rules = static::parseArrayRule($rules);//解析基于数组的规则
        } else {
            $rules = static::parseStringRule($rules);//解析基于字符串的规则
        }

        $rules[0] = static::normalizeRule($rules[0]);//规范化一个规则，这样我们就可以接受短类型的规则

        return $rules;
    }

    /**
     * Parse an array based rule.
     *
     * 解析基于数组的规则
     *
     * @param  array  $rules
     * @return array
     */
    protected static function parseArrayRule(array $rules)
    {
        //     将值转换为大驼峰     使用“点”符号从数组中获取一个项
        return [Str::studly(trim(Arr::get($rules, 0))), array_slice($rules, 1)];
    }

    /**
     * Parse a string based rule.
     *
     * 解析基于字符串的规则
     *
     * @param  string  $rules
     * @return array
     */
    protected static function parseStringRule($rules)
    {
        $parameters = [];

        // The format for specifying validation rules and parameters follows an
        // easy {rule}:{parameters} formatting convention. For instance the
        // rule "Max:3" states that the value may only be three letters.
        //
        // 指定验证规则和参数的格式遵循一个简单的规则:参数格式化约定
        // 例如，“Max:3”的规则表明，这个值可能只有三个字母。
        //
        if (strpos($rules, ':') !== false) {
            list($rules, $parameter) = explode(':', $rules, 2);
            //                     解析参数列表
            $parameters = static::parseParameters($rules, $parameter);
        }
        //         将值转换为大驼峰
        return [Str::studly(trim($rules)), $parameters];
    }

    /**
     * Parse a parameter list.
     *
     * 解析参数列表
     *
     * @param  string  $rule
     * @param  string  $parameter
     * @return array
     */
    protected static function parseParameters($rule, $parameter)
    {
        if (strtolower($rule) == 'regex') {
            return [$parameter];
        }

        return str_getcsv($parameter);
    }

    /**
     * Normalizes a rule so that we can accept short types.
     *
     * 规范化一个规则，这样我们就可以接受短类型的规则
     *
     * @param  string  $rule
     * @return string
     */
    protected static function normalizeRule($rule)
    {
        switch ($rule) {
            case 'Int':
                return 'Integer';
            case 'Bool':
                return 'Boolean';
            default:
                return $rule;
        }
    }
}
