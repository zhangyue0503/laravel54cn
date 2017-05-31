<?php

namespace Illuminate\Validation;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as FactoryContract;

class Factory implements FactoryContract
{
    /**
     * The Translator implementation.
     *
     * 翻译实现
     *
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * The Presence Verifier implementation.
     *
     * 存在验证器实现
     *
     * @var \Illuminate\Validation\PresenceVerifierInterface
     */
    protected $verifier;

    /**
     * The IoC container instance.
     *
     * IoC容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * All of the custom validator extensions.
     *
     * 所有自定义验证器扩展
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * All of the custom implicit validator extensions.
     *
     * 所有自定义的隐式验证器扩展
     *
     * @var array
     */
    protected $implicitExtensions = [];

    /**
     * All of the custom validator message replacers.
     *
     * 所有自定义验证器消息替换器
     *
     * @var array
     */
    protected $replacers = [];

    /**
     * All of the fallback messages for custom rules.
     *
     * 定制规则的所有回退消息
     *
     * @var array
     */
    protected $fallbackMessages = [];

    /**
     * The Validator resolver instance.
     *
     * 验证器解析器实例
     *
     * @var Closure
     */
    protected $resolver;

    /**
     * Create a new Validator factory instance.
	 *
	 * 创建一个新的验证工厂实例
     *
     * @param  \Illuminate\Contracts\Translation\Translator $translator
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Translator $translator, Container $container = null)
    {
        $this->container = $container;
        $this->translator = $translator;
    }

    /**
     * Create a new Validator instance.
	 *
	 * 创建一个新的验证实例
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return \Illuminate\Validation\Validator
     */
    public function make(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        // The presence verifier is responsible for checking the unique and exists data
        // for the validator. It is behind an interface so that multiple versions of
        // it may be written besides database. We'll inject it into the validator.
		//
		// 存在者负责检查独特存在数据验证
		// 它是在一个接口的后面，所以可以在数据库之外编写多个版本
		// 我们将它注入到验证器
		//
		//              解决一个新的验证实例
        $validator = $this->resolve(
            $data, $rules, $messages, $customAttributes
        );

        if (! is_null($this->verifier)) {
            //              设置实现验证器的实现
            $validator->setPresenceVerifier($this->verifier);
        }

        // Next we'll set the IoC container instance of the validator, which is used to
        // resolve out class based validator extensions. If it is not set then these
        // types of extensions will not be possible on these validation instances.
        //
        // 接下来，我们将设置验证器的IoC容器实例，它用于解析基于类的验证器扩展
        // 如果它没有设置，那么这些类型的扩展在这些验证实例中是不可能的
        //
        if (! is_null($this->container)) {
            //              设置IoC容器实例
            $validator->setContainer($this->container);
        }
        //向验证器实例添加扩展
        $this->addExtensions($validator);

        return $validator;
    }

    /**
     * Validate the given data against the provided rules.
     *
     * 根据所提供的规则验证给定的数据
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        //创建一个新的验证实例                                       向验证器实例添加扩展
        $this->make($data, $rules, $messages, $customAttributes)->validate();
    }

    /**
     * Resolve a new Validator instance.
	 *
	 * 解决一个新的验证实例
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return \Illuminate\Validation\Validator
     */
    protected function resolve(array $data, array $rules, array $messages, array $customAttributes)
    {
        if (is_null($this->resolver)) {
			//        创建一个新的验证实例
            return new Validator($this->translator, $data, $rules, $messages, $customAttributes);
        }

        return call_user_func($this->resolver, $this->translator, $data, $rules, $messages, $customAttributes);
    }

    /**
     * Add the extensions to a validator instance.
     *
     * 向验证器实例添加扩展
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    protected function addExtensions(Validator $validator)
    {
        // 注册一个自定义验证器扩展的数组
        $validator->addExtensions($this->extensions);

        // Next, we will add the implicit extensions, which are similar to the required
        // and accepted rule in that they are run even if the attributes is not in a
        // array of data that is given to a validator instances via instantiation.
        //
        // 接下来，我们将添加隐式扩展，这类似于所需要的和接受的规则，即使属性不在通过实例化提供给验证器实例的数据数组中
        //
        //            注册一组自定义的隐式验证器扩展
        $validator->addImplicitExtensions($this->implicitExtensions);
        //          注册一个自定义验证器消息替换器的数组
        $validator->addReplacers($this->replacers);
        //           为验证器设置回退消息
        $validator->setFallbackMessages($this->fallbackMessages);
    }

    /**
     * Register a custom validator extension.
     *
     * 注册一个定制的验证器扩展
     *
     * @param  string  $rule
     * @param  \Closure|string  $extension
     * @param  string  $message
     * @return void
     */
    public function extend($rule, $extension, $message = null)
    {
        $this->extensions[$rule] = $extension;

        if ($message) {
            //                   将字符串转换为蛇形命名
            $this->fallbackMessages[Str::snake($rule)] = $message;
        }
    }

    /**
     * Register a custom implicit validator extension.
     *
     * 注册一个自定义的隐式验证器扩展
     *
     * @param  string   $rule
     * @param  \Closure|string  $extension
     * @param  string  $message
     * @return void
     */
    public function extendImplicit($rule, $extension, $message = null)
    {
        $this->implicitExtensions[$rule] = $extension;

        if ($message) {
            //                   将字符串转换为蛇形命名
            $this->fallbackMessages[Str::snake($rule)] = $message;
        }
    }

    /**
     * Register a custom implicit validator message replacer.
     *
     * 注册一个定制的隐式验证器消息替换器
     *
     * @param  string   $rule
     * @param  \Closure|string  $replacer
     * @return void
     */
    public function replacer($rule, $replacer)
    {
        $this->replacers[$rule] = $replacer;
    }

    /**
     * Set the Validator instance resolver.
     *
     * 设置验证器实例解析器
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public function resolver(Closure $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get the Translator implementation.
     *
     * 获取翻译实现
     *
     * @return \Illuminate\Contracts\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Get the Presence Verifier implementation.
     *
     * 获得存在验证器的实现
     *
     * @return \Illuminate\Validation\PresenceVerifierInterface
     */
    public function getPresenceVerifier()
    {
        return $this->verifier;
    }

    /**
     * Set the Presence Verifier implementation.
     *
     * 设置实现验证器的实现
     *
     * @param  \Illuminate\Validation\PresenceVerifierInterface  $presenceVerifier
     * @return void
     */
    public function setPresenceVerifier(PresenceVerifierInterface $presenceVerifier)
    {
        $this->verifier = $presenceVerifier;
    }
}
