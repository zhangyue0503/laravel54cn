<?php

namespace Illuminate\Validation;

/**
 * Provides default implementation of ValidatesWhenResolved contract.
 */
trait ValidatesWhenResolvedTrait
{
    /**
     * Validate the class instance.
     *
     * 验证类实例
     *
     * @return void
     */
    public function validate()
    {
        $this->prepareForValidation();//为验证准备数据

        $instance = $this->getValidatorInstance();//获取请求的验证器实例
        //        确定请求是否通过了授权检查
        if (! $this->passesAuthorization()) {
            $this->failedAuthorization();//确定请求是否通过了授权检查
        } elseif (! $instance->passes()) {//确定数据是否通过验证规则
            $this->failedValidation($instance);//处理失败的验证尝试
        }
    }

    /**
     * Prepare the data for validation.
     *
     * 为验证准备数据
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // no default action
    }

    /**
     * Get the validator instance for the request.
     *
     * 获取请求的验证器实例
     *
     * @return \Illuminate\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        return $this->validator();
    }

    /**
     * Handle a failed validation attempt.
     *
     * 处理失败的验证尝试
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator);
    }

    /**
     * Determine if the request passes the authorization check.
     *
     * 确定请求是否通过了授权检查
     *
     * @return bool
     */
    protected function passesAuthorization()
    {
        if (method_exists($this, 'authorize')) {
            return $this->authorize();
        }

        return true;
    }

    /**
     * Handle a failed authorization attempt.
     *
     * 处理失败的授权尝试
     *
     * @return void
     *
     * @throws \Illuminate\Validation\UnauthorizedException
     */
    protected function failedAuthorization()
    {
        throw new UnauthorizedException;
    }
}
