<?php

namespace Illuminate\Contracts\Validation;

use Illuminate\Contracts\Support\MessageProvider;

interface Validator extends MessageProvider
{
    /**
     * Determine if the data fails the validation rules.
     *
     * 确定数据是否失败了验证规则
     *
     * @return bool
     */
    public function fails();

    /**
     * Get the failed validation rules.
     *
     * 获取失败的验证规则
     *
     * @return array
     */
    public function failed();

    /**
     * Add conditions to a given field based on a Closure.
     *
     * 基于闭包的条件添加条件
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @param  callable  $callback
     * @return $this
     */
    public function sometimes($attribute, $rules, callable $callback);

    /**
     * After an after validation callback.
     *
     * 在验证回调之后
     *
     * @param  callable|string  $callback
     * @return $this
     */
    public function after($callback);
}
