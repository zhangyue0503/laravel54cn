<?php

namespace Illuminate\Validation\Rules;

class NotIn
{
    /**
     * The name of the rule.
     *
     * 规则名
     */
    protected $rule = 'not_in';

    /**
     * The accepted values.
     *
     * 通过的值
     *
     * @var array
     */
    protected $values;

    /**
     * Create a new "not in" rule instance.
     *
     * 创建一个新的“not in”规则实例
     *
     * @param  array  $values
     * @return void
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Convert the rule to a validation string.
     *
     * 将规则转换为一个验证字符串
     *
     * @return string
     */
    public function __toString()
    {
        return $this->rule.':'.implode(',', $this->values);
    }
}
