<?php

namespace Illuminate\Contracts\Validation;

interface ValidatesWhenResolved
{
    /**
     * Validate the given class instance.
     *
     * 验证给定的类实例
     *
     * @return void
     */
    public function validate();
}
