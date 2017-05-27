<?php

namespace Illuminate\Validation\Rules;

class In
{
    /**
     * The name of the rule.
     *
     * 规则名
     */
    protected $rule = 'in';

    /**
     * The accepted values.
     *
     * 通过的值
     *
     * @var array
     */
    protected $values;

    /**
     * Create a new in rule instance.
     *
     * 创建一个新的规则实例
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
