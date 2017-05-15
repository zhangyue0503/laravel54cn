<?php

namespace Illuminate\Database\Query;
//查询表达式
class Expression
{
    /**
     * The value of the expression.
     *
     * 表达式的值
     *
     * @var mixed
     */
    protected $value;

    /**
     * Create a new raw query expression.
     *
     * 创建新的原始查询表达式
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get the value of the expression.
     *
     * 得到表达式的值
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the value of the expression.
     *
     * 得到表达式的值
     *
     * @return string
     */
    public function __toString()
    {
        //                   得到表达式的值
        return (string) $this->getValue();
    }
}
