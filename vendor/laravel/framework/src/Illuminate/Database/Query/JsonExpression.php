<?php

namespace Illuminate\Database\Query;

use InvalidArgumentException;

class JsonExpression extends Expression
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
     * 创建一个新的原始查询表达式
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value)
    {
        //将给定的值转换为适当的JSON绑定参数
        $this->value = $this->getJsonBindingParameter($value);
    }

    /**
     * Translate the given value into the appropriate JSON binding parameter.
     *
     * 将给定的值转换为适当的JSON绑定参数
     *
     * @param  mixed  $value
     * @return string
     */
    protected function getJsonBindingParameter($value)
    {
        switch ($type = gettype($value)) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'integer':
            case 'double':
                return $value;
            case 'string':
                return '?';
            case 'object':
            case 'array':
                return '?';
        }

        throw new InvalidArgumentException('JSON value is of illegal type: '.$type);
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
        //               得到表达式的值
        return (string) $this->getValue();
    }
}
