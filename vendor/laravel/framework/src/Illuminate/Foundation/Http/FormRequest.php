<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidatesWhenResolvedTrait;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class FormRequest extends Request implements ValidatesWhenResolved
{
    use ValidatesWhenResolvedTrait;

    /**
     * The container instance.
     *
     * 容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The redirector instance.
     *
     * 重定向器实例
     *
     * @var \Illuminate\Routing\Redirector
     */
    protected $redirector;

    /**
     * The URI to redirect to if validation fails.
     *
     * 如果验证失败，将重定向到的URI
     *
     * @var string
     */
    protected $redirect;

    /**
     * The route to redirect to if validation fails.
     *
     * 如果验证失败，将重定向到的路由
     *
     * @var string
     */
    protected $redirectRoute;

    /**
     * The controller action to redirect to if validation fails.
     *
     * 如果验证失败，将重定向到的控制器
     *
     * @var string
     */
    protected $redirectAction;

    /**
     * The key to be used for the view error bag.
     *
     * 用于视图错误包的关键字
     *
     * @var string
     */
    protected $errorBag = 'default';

    /**
     * The input keys that should not be flashed on redirect.
     *
     * 不应该在重定向上显示的输入键
     *
     * @var array
     */
    protected $dontFlash = ['password', 'password_confirmation'];

    /**
     * Get the validator instance for the request.
     *
     * 获取请求的验证器实例
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        //                       从容器中解析给定类型
        $factory = $this->container->make(ValidationFactory::class);

        if (method_exists($this, 'validator')) {
            //调用给定的闭包/类@方法并注入它的依赖项
            $validator = $this->container->call([$this, 'validator'], compact('factory'));
        } else {
            //创建默认的验证器实例
            $validator = $this->createDefaultValidator($factory);
        }

        if (method_exists($this, 'withValidator')) {
            $this->withValidator($validator);
        }

        return $validator;
    }

    /**
     * Create the default validator instance.
     *
     * 创建默认的验证器实例
     *
     * @param  \Illuminate\Contracts\Validation\Factory  $factory
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function createDefaultValidator(ValidationFactory $factory)
    {
        //从容器中解析给定类型
        return $factory->make(
            //从请求中获取数据                调用给定的闭包/类@方法并注入它的依赖项
            $this->validationData(), $this->container->call([$this, 'rules']),
            //为确认器错误获取自定义消息  获取验证器错误的自定义属性
            $this->messages(), $this->attributes()
        );
    }

    /**
     * Get data to be validated from the request.
     *
     * 从请求中获取数据
     *
     * @return array
     */
    protected function validationData()
    {
        //获取请求的所有输入和文件
        return $this->all();
    }

    /**
     * Handle a failed validation attempt.
     *
     * 处理失败的验证尝试
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        //                                           为请求获得正确的失败验证响应
        throw new ValidationException($validator, $this->response(
            $this->formatErrors($validator)//从给定的验证器实例中格式化错误
        ));
    }

    /**
     * Get the proper failed validation response for the request.
     *
     * 为请求获得正确的失败验证响应
     *
     * @param  array  $errors
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function response(array $errors)
    {
        //确定当前请求是否可能需要JSON响应
        if ($this->expectsJson()) {
            return new JsonResponse($errors, 422); //json响应
        }
        //             为给定路径创建新的重定向响应(让URL重定向到验证错误)
        return $this->redirector->to($this->getRedirectUrl())
                                        ->withInput($this->except($this->dontFlash))//在会话中闪存输入的数组(获取所有输入，除了指定的项目数组)
                                        ->withErrors($errors, $this->errorBag);//将错误的容器闪存到会话中
    }

    /**
     * Format the errors from the given Validator instance.
     *
     * 从给定的验证器实例中格式化错误
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return array
     */
    protected function formatErrors(Validator $validator)
    {
        //获取实例的消息->将实例作为数组
        return $validator->getMessageBag()->toArray();
    }

    /**
     * Get the URL to redirect to on a validation error.
     *
     * 让URL重定向到验证错误
     *
     * @return string
     */
    protected function getRedirectUrl()
    {
        //获取URL生成器实例
        $url = $this->redirector->getUrlGenerator();

        if ($this->redirect) {
            return $url->to($this->redirect);//生成给定路径的绝对URL
        } elseif ($this->redirectRoute) {
            return $url->route($this->redirectRoute);//获取指定路由的URL
        } elseif ($this->redirectAction) {
            return $url->action($this->redirectAction);//获取控制器动作的URL
        }

        return $url->previous();//获取之前请求的URL
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
            return $this->container->call([$this, 'authorize']);//调用给定的闭包/类@方法并注入它的依赖项
        }

        return false;
    }

    /**
     * Handle a failed authorization attempt.
     *
     * 处理失败的授权尝试
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException('This action is unauthorized.');
    }

    /**
     * Get custom messages for validator errors.
     *
     * 为确认器错误获取自定义消息
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * 获取验证器错误的自定义属性
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Set the Redirector instance.
     *
     * 设置Redirector实例
     *
     * @param  \Illuminate\Routing\Redirector  $redirector
     * @return $this
     */
    public function setRedirector(Redirector $redirector)
    {
        $this->redirector = $redirector;

        return $this;
    }

    /**
     * Set the container implementation.
     *
     * 设置容器实现
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }
}
