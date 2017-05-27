<?php

namespace Illuminate\Validation\Concerns;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FormatsMessages
{
    use ReplacesAttributes;

    /**
     * The size related validation rules.
     *
     * 与大小相关的验证规则
     *
     * @var array
     */
    protected $sizeRules = ['Size', 'Between', 'Min', 'Max'];

    /**
     * The numeric related validation rules.
     *
     * 与数字相关的验证规则
     *
     * @var array
     */
    protected $numericRules = ['Numeric', 'Integer'];

    /**
     * Get the validation message for an attribute and rule.
     *
     * 获取属性和规则的验证消息
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return string
     */
    protected function getMessage($attribute, $rule)
    {
        //如果存在规则，就获取一条规则的内联消息
        $inlineMessage = $this->getFromLocalArray(
            //                  将字符串转换为蛇形命名
            $attribute, $lowerRule = Str::snake($rule)
        );

        // First we will retrieve the custom message for the validation rule if one
        // exists. If a custom validation message is being used we'll return the
        // custom message, otherwise we'll keep searching for a valid message.
        //
        // 首先，如果存在，我们将检索验证规则的自定义消息。如果正在使用自定义验证消息，我们将返回自定义消息，否则我们将继续搜索有效消息
        //
        if (! is_null($inlineMessage)) {
            return $inlineMessage;
        }
        //                  从翻译器中获取自定义错误消息
        $customMessage = $this->getCustomMessageFromTranslator(
            $customKey = "validation.custom.{$attribute}.{$lowerRule}"
        );

        // First we check for a custom defined validation message for the attribute
        // and rule. This allows the developer to specify specific messages for
        // only some attributes and rules that need to get specially formed.
        //
        // 首先，我们检查为属性和规则定义的自定义验证消息
        // 这允许开发人员为特定的消息指定特定的消息，而这些属性和规则需要特别的形成
        //
        if ($customMessage !== $customKey) {
            return $customMessage;
        }

        // If the rule being validated is a "size" rule, we will need to gather the
        // specific error message for the type of attribute being validated such
        // as a number, file or string which all have different message types.
        //
        // 如果被验证的规则是一个“大小”规则，我们将需要为被验证的属性类型收集特定的错误消息，例如一个数字、文件或字符串，它们都有不同的消息类型
        //
        elseif (in_array($rule, $this->sizeRules)) {
            //为属性和大小规则获取适当的错误消息
            return $this->getSizeMessage($attribute, $rule);
        }

        // Finally, if no developer specified messages have been set, and no other
        // special messages apply for this rule, we will just pull the default
        // messages out of the translator service for this validation rule.
        //
        // 最后，如果没有设置开发人员指定的消息，并且没有其他特殊消息应用于此规则，那么我们只需要将缺省消息从翻译服务中取出来，用于这个验证规则
        //
        $key = "validation.{$lowerRule}";
        //                                翻译给定的信息
        if ($key != ($value = $this->translator->trans($key))) {
            return $value;
        }
        //如果存在规则，就获取一条规则的内联消息
        return $this->getFromLocalArray(
            $attribute, $lowerRule, $this->fallbackMessages
        ) ?: $key;
    }

    /**
     * Get the inline message for a rule if it exists.
     *
     * 如果存在规则，就获取一条规则的内联消息
     *
     * @param  string  $attribute
     * @param  string  $lowerRule
     * @param  array   $source
     * @return string|null
     */
    protected function getFromLocalArray($attribute, $lowerRule, $source = null)
    {
        $source = $source ?: $this->customMessages;

        $keys = ["{$attribute}.{$lowerRule}", $lowerRule];

        // First we will check for a custom message for an attribute specific rule
        // message for the fields, then we will check for a general custom line
        // that is not attribute specific. If we find either we'll return it.
        //
        // 首先，我们将检查用于字段的属性特定规则消息的自定义消息，然后我们将检查非属性特定的通用定制行
        // 如果我们找到了，我们会返回它
        //
        foreach ($keys as $key) {
            foreach (array_keys($source) as $sourceKey) {
                //确定给定的字符串是否与给定的模式匹配
                if (Str::is($sourceKey, $key)) {
                    return $source[$sourceKey];
                }
            }
        }
    }

    /**
     * Get the custom error message from translator.
     *
     * 从翻译器中获取自定义错误消息
     *
     * @param  string  $key
     * @return string
     */
    protected function getCustomMessageFromTranslator($key)
    {
        //                       翻译给定的信息
        if (($message = $this->translator->trans($key)) !== $key) {
            return $message;
        }

        // If an exact match was not found for the key, we will collapse all of these
        // messages and loop through them and try to find a wildcard match for the
        // given key. Otherwise, we will simply return the key's value back out.
        //
        // 如果没有找到密钥的精确匹配，我们将崩溃所有这些消息并循环遍历它们，并尝试为给定的键找到一个通配符匹配
        // 否则，我们将简单地返回键的值
        //
        $shortKey = preg_replace(
            '/^validation\.custom\./', '', $key
        );
        //            检查一个通配符键的给定消息
        return $this->getWildcardCustomMessages(Arr::dot(
            //                   翻译给定的信息
            (array) $this->translator->trans('validation.custom')
        ), $shortKey, $key);
    }

    /**
     * Check the given messages for a wildcard key.
     *
     * 检查一个通配符键的给定消息
     *
     * @param  array  $messages
     * @param  string  $search
     * @param  string  $default
     * @return string
     */
    protected function getWildcardCustomMessages($messages, $search, $default)
    {
        foreach ($messages as $key => $message) {
            //                   确定一个给定的字符串包含另一个字符串       确定给定的字符串是否与给定的模式匹配
            if ($search === $key || (Str::contains($key, ['*']) && Str::is($key, $search))) {
                return $message;
            }
        }

        return $default;
    }

    /**
     * Get the proper error message for an attribute and size rule.
     *
     * 为属性和大小规则获取适当的错误消息
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return string
     */
    protected function getSizeMessage($attribute, $rule)
    {
        //        将字符串转换为蛇形命名
        $lowerRule = Str::snake($rule);

        // There are three different types of size validations. The attribute may be
        // either a number, file, or string so we will check a few things to know
        // which type of value it is and return the correct line for that type.
        //
        // 大小验证有三种不同的类型。属性可能是一个数字、文件或字符串，因此我们将检查一些东西，以确定它是哪种类型的值，并返回该类型的正确行
        //
        //             获取给定属性的数据类型
        $type = $this->getAttributeType($attribute);

        $key = "validation.{$lowerRule}.{$type}";
        //                    翻译给定的信息
        return $this->translator->trans($key);
    }

    /**
     * Get the data type of the given attribute.
     *
     * 获取给定属性的数据类型
     *
     * @param  string  $attribute
     * @return string
     */
    protected function getAttributeType($attribute)
    {
        // We assume that the attributes present in the file array are files so that
        // means that if the attribute does not have a numeric rule and the files
        // list doesn't have it we'll just consider it a string by elimination.
        //
        // 我们假设文件数组中呈现的属性是文件，这意味着如果属性没有一个数字规则，而文件列表没有它，那么我们只需要将它看作是一个通过消除的字符串
        //
        //         确定给定属性在给定的集合中是否有规则
        if ($this->hasRule($attribute, $this->numericRules)) {
            return 'numeric';
        } elseif ($this->hasRule($attribute, ['Array'])) {
            return 'array';
            //        获取给定属性的值
        } elseif ($this->getValue($attribute) instanceof UploadedFile) {
            return 'file';
        }

        return 'string';
    }

    /**
     * Replace all error message place-holders with actual values.
     *
     * 用实际值替换所有错误消息占位符
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    public function makeReplacements($message, $attribute, $rule, $parameters)
    {
        //在给定消息中替换:属性占位符
        $message = $this->replaceAttributePlaceholder(
            //              获取属性的可显示名称
            $message, $this->getDisplayableAttribute($attribute)
        );
        //                        将字符串转换为蛇形命名
        if (isset($this->replacers[Str::snake($rule)])) {
            //        调用自定义验证器消息替换器
            return $this->callReplacer($message, $attribute, Str::snake($rule), $parameters);
        } elseif (method_exists($this, $replacer = "replace{$rule}")) {
            return $this->$replacer($message, $attribute, $rule, $parameters);
        }

        return $message;
    }

    /**
     * Get the displayable name of the attribute.
     *
     * 获取属性的可显示名称
     *
     * @param  string  $attribute
     * @return string
     */
    protected function getDisplayableAttribute($attribute)
    {
        //                         获得主属性名
        $primaryAttribute = $this->getPrimaryAttribute($attribute);

        $expectedAttributes = $attribute != $primaryAttribute
                    ? [$attribute, $primaryAttribute] : [$attribute];

        foreach ($expectedAttributes as $name) {
            // The developer may dynamically specify the array of custom attributes on this
            // validator instance. If the attribute exists in this array it is used over
            // the other ways of pulling the attribute name for this given attributes.
            //
            // 开发人员可以动态地在这个确认器实例上指定自定义属性的数组
            // 如果该属性存在于这个数组中，那么它将被用于为这个给定的属性拉取属性名的其他方法
            //
            if (isset($this->customAttributes[$name])) {
                return $this->customAttributes[$name];
            }

            // We allow for a developer to specify language lines for any attribute in this
            // application, which allows flexibility for displaying a unique displayable
            // version of the attribute name instead of the name used in an HTTP POST.
            //
            // 我们允许开发人员为该应用程序中的任何属性指定语言行，这允许灵活地显示属性名的惟一可显示版本，而不是在HTTP POST中使用的名称
            //
            //                   从属性转换获得给定属性
            if ($line = $this->getAttributeFromTranslations($name)) {
                return $line;
            }
        }

        // When no language line has been specified for the attribute and it is also
        // an implicit attribute we will display the raw attribute's name and not
        // modify it with any of these replacements before we display the name.
        //
        // 当没有为属性指定语言行，并且它也是一个隐式属性时，我们将显示原始属性的名称，并且在显示名称之前不使用这些替换来修改它
        //
        if (isset($this->implicitAttributes[$primaryAttribute])) {
            return $attribute;
        }
        //                          将字符串转换为蛇形命名
        return str_replace('_', ' ', Str::snake($attribute));
    }

    /**
     * Get the given attribute from the attribute translations.
     *
     * 从属性转换获得给定属性
     *
     * @param  string  $name
     * @return string
     */
    protected function getAttributeFromTranslations($name)
    {
        //使用“点”符号从数组中获取一个项         翻译给定的信息
        return Arr::get($this->translator->trans('validation.attributes'), $name);
    }

    /**
     * Replace the :attribute placeholder in the given message.
     *
     * 在给定消息中替换:属性占位符
     *
     * @param  string  $message
     * @param  string  $value
     * @return string
     */
    protected function replaceAttributePlaceholder($message, $value)
    {
        return str_replace(
            [':attribute', ':ATTRIBUTE', ':Attribute'],
        //将给定的字符串转换为大写              使字符串的第一个字符大写
            [$value, Str::upper($value), Str::ucfirst($value)],
            $message
        );
    }

    /**
     * Get the displayable name of the value.
     *
     * 获取该值的可显示名称
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return string
     */
    public function getDisplayableValue($attribute, $value)
    {
        if (isset($this->customValues[$attribute][$value])) {
            return $this->customValues[$attribute][$value];
        }

        $key = "validation.values.{$attribute}.{$value}";
        //                        翻译给定的信息
        if (($line = $this->translator->trans($key)) !== $key) {
            return $line;
        }

        return $value;
    }

    /**
     * Transform an array of attributes to their displayable form.
     *
     * 将一个属性数组转换为可显示的表单
     *
     * @param  array  $values
     * @return array
     */
    protected function getAttributeList(array $values)
    {
        $attributes = [];

        // For each attribute in the list we will simply get its displayable form as
        // this is convenient when replacing lists of parameters like some of the
        // replacement functions do when formatting out the validation message.
        //
        // 对于列表中的每个属性，我们将简单地得到它的可显示表单，因为在替换验证消息时，替换一些替换函数的列表会很方便
        //
        foreach ($values as $key => $value) {
            //                       获取属性的可显示名称
            $attributes[$key] = $this->getDisplayableAttribute($value);
        }

        return $attributes;
    }

    /**
     * Call a custom validator message replacer.
     *
     * 调用自定义验证器消息替换器
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string|null
     */
    protected function callReplacer($message, $attribute, $rule, $parameters)
    {
        $callback = $this->replacers[$rule];

        if ($callback instanceof Closure) {
            return call_user_func_array($callback, func_get_args());
        } elseif (is_string($callback)) {
            //调用基于类的验证器消息替换器
            return $this->callClassBasedReplacer($callback, $message, $attribute, $rule, $parameters);
        }
    }

    /**
     * Call a class based validator message replacer.
     *
     * 调用基于类的验证器消息替换器
     *
     * @param  string  $callback
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function callClassBasedReplacer($callback, $message, $attribute, $rule, $parameters)
    {
        //                          解析 类@方法 类型回调到类和方法
        list($class, $method) = Str::parseCallback($callback, 'replace');
        //                                    从容器中解析给定类型
        return call_user_func_array([$this->container->make($class), $method], array_slice(func_get_args(), 1));
    }
}
