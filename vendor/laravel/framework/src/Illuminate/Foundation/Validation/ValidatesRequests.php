<?php

namespace Illuminate\Foundation\Validation;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

trait ValidatesRequests
{
    /**
     * The default error bag.
     *
     * 默认错误包
     *
     * @var string
     */
    protected $validatesRequestErrorBag;

    /**
     * Run the validation routine against the given validator.
     *
     * 对给定的验证器运行验证例程
     *
     * @param  \Illuminate\Contracts\Validation\Validator|array  $validator
     * @param  \Illuminate\Http\Request|null  $request
     * @return void
     */
    public function validateWith($validator, Request $request = null)
    {
        $request = $request ?: app('request');

        if (is_array($validator)) {
            //             获得一个验证工厂实例->创建一个新的验证实例
            $validator = $this->getValidationFactory()->make($request->all(), $validator);
        }
        //确定数据是否失败了验证规则
        if ($validator->fails()) {
            //抛出失败的验证异常
            $this->throwValidationException($request, $validator);
        }
    }

    /**
     * Validate the given request with the given rules.
     *
     * 使用给定的规则验证给定的请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     */
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        //             获得一个验证工厂实例->创建一个新的验证实例
        $validator = $this->getValidationFactory()->make($request->all(), $rules, $messages, $customAttributes);
        //确定数据是否失败了验证规则
        if ($validator->fails()) {
            //抛出失败的验证异常
            $this->throwValidationException($request, $validator);
        }
    }

    /**
     * Validate the given request with the given rules.
     *
     * 使用给定的规则验证给定的请求
     *
     * @param  string  $errorBag
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateWithBag($errorBag, Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        //使用一个给定的错误包作为默认包来执行一个闭包
        $this->withErrorBag($errorBag, function () use ($request, $rules, $messages, $customAttributes) {
            //使用给定的规则验证给定的请求
            $this->validate($request, $rules, $messages, $customAttributes);
        });
    }

    /**
     * Execute a Closure within with a given error bag set as the default bag.
     *
     * 使用一个给定的错误包作为默认包来执行一个闭包
     *
     * @param  string  $errorBag
     * @param  callable  $callback
     * @return void
     */
    protected function withErrorBag($errorBag, callable $callback)
    {
        $this->validatesRequestErrorBag = $errorBag;

        call_user_func($callback);

        $this->validatesRequestErrorBag = null;
    }

    /**
     * Throw the failed validation exception.
     *
     * 抛出失败的验证异常
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwValidationException(Request $request, $validator)
    {
        //                                                当请求失败时，创建响应
        throw new ValidationException($validator, $this->buildFailedValidationResponse(
            //对返回的验证错误进行格式化
            $request, $this->formatValidationErrors($validator)
        ));
    }

    /**
     * Create the response for when a request fails validation.
     *
     * 当请求失败时，创建响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $errors
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        //确定当前请求是否可能需要JSON响应
        if ($request->expectsJson()) {
            return new JsonResponse($errors, 422);
        }
        //               为给定路径创建新的重定向响应(获取我们应该重定向到的URL)
        return redirect()->to($this->getRedirectUrl())
                        ->withInput($request->input())//在会话中闪存输入的数组(从请求中检索输入项)
                        ->withErrors($errors, $this->errorBag());//将错误的容器闪存到会话中(,获取用于视图错误包的关键字)
    }

    /**
     * Format the validation errors to be returned.
     *
     * 对返回的验证错误进行格式化
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return array
     */
    protected function formatValidationErrors(Validator $validator)
    {
        //            消息容器的另一种语义快捷方式->获取属性和规则的验证消息
        return $validator->errors()->getMessages();
    }

    /**
     * Get the key to be used for the view error bag.
     *
     * 获取用于视图错误包的关键字
     *
     * @return string
     */
    protected function errorBag()
    {
        return $this->validatesRequestErrorBag ?: 'default';
    }

    /**
     * Get the URL we should redirect to.
     *
     * 获取我们应该重定向到的URL
     *
     * @return string
     */
    protected function getRedirectUrl()
    {
        return app(UrlGenerator::class)->previous();//获取之前请求的URL
    }

    /**
     * Get a validation factory instance.
     *
     * 获得一个验证工厂实例
     *
     * @return \Illuminate\Contracts\Validation\Factory
     */
    protected function getValidationFactory()
    {
        return app(Factory::class);
    }
}
