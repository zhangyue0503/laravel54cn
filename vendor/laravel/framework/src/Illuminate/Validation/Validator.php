<?php

namespace Illuminate\Validation;

use Closure;
use RuntimeException;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Fluent;
use Illuminate\Support\MessageBag;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class Validator implements ValidatorContract
{
    use Concerns\FormatsMessages,
        Concerns\ValidatesAttributes;

    /**
     * The Translator implementation.
     *
     * 翻译实现
     *
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * The container instance.
     *
     * 容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The Presence Verifier implementation.
     *
     * 存在验证器实现
     *
     * @var \Illuminate\Validation\PresenceVerifierInterface
     */
    protected $presenceVerifier;

    /**
     * The failed validation rules.
     *
     * 失败的验证规则
     *
     * @var array
     */
    protected $failedRules = [];

    /**
     * The message bag instance.
     *
     * 消息包实例
     *
     * @var \Illuminate\Support\MessageBag
     */
    protected $messages;

    /**
     * The data under validation.
     *
     * 下的数据验证
     *
     * @var array
     */
    protected $data;

    /**
     * The initial rules provided.
     *
     * 提供的初始规则
     *
     * @var array
     */
    protected $initialRules;

    /**
     * The rules to be applied to the data.
     *
     * 应用于数据的规则
     *
     * @var array
     */
    protected $rules;

    /**
     * The current rule that is validating.
     *
     * 当前的规则是验证
     *
     * @var string
     */
    protected $currentRule;

    /**
     * The array of wildcard attributes with their asterisks expanded.
     *
     * 带有它们的小行星扩展的通配符属性的数组
     *
     * @var array
     */
    protected $implicitAttributes = [];

    /**
     * All of the registered "after" callbacks.
     *
     * 所有注册后的“after”回调
     *
     * @var array
     */
    protected $after = [];

    /**
     * The array of custom error messages.
     *
     * 定制错误消息的数组
     *
     * @var array
     */
    public $customMessages = [];

    /**
     * The array of fallback error messages.
     *
     * 返回错误消息的数组
     *
     * @var array
     */
    public $fallbackMessages = [];

    /**
     * The array of custom attribute names.
     *
     * 定制属性名的数组
     *
     * @var array
     */
    public $customAttributes = [];

    /**
     * The array of custom displayable values.
     *
     * 定制可显示值的数组
     *
     * @var array
     */
    public $customValues = [];

    /**
     * All of the custom validator extensions.
     *
     * 所有自定义验证器扩展
     *
     * @var array
     */
    public $extensions = [];

    /**
     * All of the custom replacer extensions.
     *
     * 所有自定义的替换扩展
     *
     * @var array
     */
    public $replacers = [];

    /**
     * The validation rules that may be applied to files.
     *
     * 可以应用于文件的验证规则
     *
     * @var array
     */
    protected $fileRules = [
        'File', 'Image', 'Mimes', 'Mimetypes', 'Min',
        'Max', 'Size', 'Between', 'Dimensions',
    ];

    /**
     * The validation rules that imply the field is required.
     *
     * 需要字段的验证规则是必需的
     *
     * @var array
     */
    protected $implicitRules = [
        'Required', 'Filled', 'RequiredWith', 'RequiredWithAll', 'RequiredWithout',
        'RequiredWithoutAll', 'RequiredIf', 'RequiredUnless', 'Accepted', 'Present',
    ];

    /**
     * The validation rules which depend on other fields as parameters.
     *
     * 依赖于其他字段作为参数的验证规则
     *
     * @var array
     */
    protected $dependentRules = [
        'RequiredWith', 'RequiredWithAll', 'RequiredWithout', 'RequiredWithoutAll',
        'RequiredIf', 'RequiredUnless', 'Confirmed', 'Same', 'Different', 'Unique',
        'Before', 'After', 'BeforeOrEqual', 'AfterOrEqual',
    ];

    /**
     * Create a new Validator instance.
	 *
	 * 创建一个新的验证实例
     *
     * @param  \Illuminate\Contracts\Translation\Translator  $translator
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     */
    public function __construct(Translator $translator, array $data, array $rules,
                                array $messages = [], array $customAttributes = [])
    {
        $this->initialRules = $rules;
        $this->translator = $translator;
        $this->customMessages = $messages;
        $this->data = $this->parseData($data);//解析数据数组，将点转换为->
        $this->customAttributes = $customAttributes;
        //设置验证规则
        $this->setRules($rules);
    }

    /**
     * Parse the data array, converting dots to ->.
     *
     * 解析数据数组，将点转换为->
     *
     * @param  array  $data
     * @return array
     */
    public function parseData(array $data)
    {
        $newData = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->parseData($value);//解析数据数组，将点转换为->
            }

            // If the data key contains a dot, we will replace it with another character
            // sequence so it doesn't interfere with dot processing when working with
            // array based validation rules and array_dot later in the validations.
            //
            // 如果数据键包含一个点，我们将用另一个字符序列来替换它，这样它就不会在处理基于数组的验证规则和数组后面的数组时干扰点处理
            //
            if (Str::contains($key, '.')) {//确定一个给定的字符串包含另一个字符串
                $newData[str_replace('.', '->', $key)] = $value;
            } else {
                $newData[$key] = $value;
            }
        }

        return $newData;
    }

    /**
     * Add an after validation callback.
     *
     * 添加一个验证后的回调
     *
     * @param  callable|string  $callback
     * @return $this
     */
    public function after($callback)
    {
        $this->after[] = function () use ($callback) {
            return call_user_func_array($callback, [$this]);
        };

        return $this;
    }

    /**
     * Determine if the data passes the validation rules.
	 *
	 * 确定数据是否通过验证规则
     *
     * @return bool
     */
    public function passes()
    {
        $this->messages = new MessageBag;

        // We'll spin through each rule, validating the attributes attached to that
        // rule. Any error messages will be added to the containers with each of
        // the other error messages, returning true if we don't have messages.
        //
        // 我们将对每个规则进行旋转，验证与该规则相关的属性
        // 任何错误消息都将被添加到容器中，并使用其他错误消息，如果没有消息，则返回true
        //
        foreach ($this->rules as $attribute => $rules) {
            $attribute = str_replace('\.', '->', $attribute);

            foreach ($rules as $rule) {
                $this->validateAttribute($attribute, $rule);//根据规则验证给定属性
                //    检查是否应该停止对给定属性的进一步验证
                if ($this->shouldStopValidating($attribute)) {
                    break;
                }
            }
        }

        // Here we will spin through all of the "after" hooks on this validator and
        // fire them off. This gives the callbacks a chance to perform all kinds
        // of other validation that needs to get wrapped up in this operation.
        //
        // 在这里，我们将对这个验证器的所有“after”钩子进行旋转，并将它们关闭
        // 这给回调提供了执行所有需要在此操作中完成的其他验证的机会
        //
        foreach ($this->after as $after) {
            call_user_func($after);
        }
        //                   确定消息包是否有任何消息
        return $this->messages->isEmpty();
    }

    /**
     * Determine if the data fails the validation rules.
	 *
	 * 确定数据是否验证规则失败
	 * * 判断数据是否符合验证规则
     *
     * @return bool
     */
    public function fails()
    {
        return ! $this->passes();//确定数据是否通过验证规则
    }

    /**
     * Run the validator's rules against its data.
     *
     * 向验证器实例添加扩展
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate()
    {
        // 确定数据是否验证规则失败
        if ($this->fails()) {
            throw new ValidationException($this);
        }
    }

    /**
     * Validate a given attribute against a rule.
	 *
	 * 根据规则验证给定属性
	 * * 根据一个准则验证一个给定的属性
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return void
     */
    protected function validateAttribute($attribute, $rule)
    {
        $this->currentRule = $rule;
        //                                     从规则中提取规则名和参数
        list($rule, $parameters) = ValidationRuleParser::parse($rule);

        if ($rule == '') {
            return;
        }

        // First we will get the correct keys for the given attribute in case the field is nested in
        // an array. Then we determine if the given rule accepts other field names as parameters.
        // If so, we will replace any asterisks found in the parameters with the correct keys.
        //
        // 首先，我们将获得给定属性的正确键，以防字段嵌套在数组中
        // 然后，我们确定给定的规则是否接受其他字段名作为参数
        // 如果是这样，我们将用正确的键替换在参数中找到的任何小行星
        //
        //          从带有点标记的属性中获取显式键
        if (($keys = $this->getExplicitKeys($attribute)) &&
            //确定给定的规则是否依赖于其他字段
            $this->dependsOnOtherFields($rule)) {
            //                 用给定的键替换每个字段参数
            $parameters = $this->replaceAsterisksInParameters($parameters, $keys);
        }
        //           获取给定属性的值
        $value = $this->getValue($attribute);

        // If the attribute is a file, we will verify that the file upload was actually successful
        // and if it wasn't we will add a failure for the attribute. Files may not successfully
        // upload if they are too large based on PHP's settings so we will bail in this case.
        //
        // 如果属性是一个文件，我们将验证文件上传是否成功，如果不是，我们将为该属性添加一个失败
        // 如果文件太大，基于PHP的设置，文件可能无法成功上传，因此我们将在这个案例中进行保释
        //
        //                                         返回文件是否成功上传
        if ($value instanceof UploadedFile && ! $value->isValid() &&
            // 确定给定属性在给定的集合中是否有规则
            $this->hasRule($attribute, array_merge($this->fileRules, $this->implicitRules))
        ) {
            //          向集合中添加一条失败的规则和错误消息
            return $this->addFailure($attribute, 'uploaded', []);
        }

        // If we have made it this far we will make sure the attribute is validatable and if it is
        // we will call the validation method with the attribute. If a method returns false the
        // attribute is invalid and we will add a failure message for this failing attribute.
        //
        // 如果我们已经做到了这一点，我们将确保属性是可验证的，如果是的话，我们将使用属性调用验证方法
        // 如果一个方法返回false，属性是无效的，我们将为这个失败的属性添加一个失败消息
        //
        //                     确定属性是否为确认性
        $validatable = $this->isValidatable($rule, $attribute, $value);

        $method = "validate{$rule}";

        if ($validatable && ! $this->$method($attribute, $value, $parameters, $this)) {
            //向集合中添加一条失败的规则和错误消息
            $this->addFailure($attribute, $rule, $parameters);
        }
    }

    /**
     * Determine if the given rule depends on other fields.
     *
     * 确定给定的规则是否依赖于其他字段
     *
     * @param  string  $rule
     * @return bool
     */
    protected function dependsOnOtherFields($rule)
    {
        return in_array($rule, $this->dependentRules);
    }

    /**
     * Get the explicit keys from an attribute flattened with dot notation.
     *
     * 从带有点标记的属性中获取显式键
     *
     * E.g. 'foo.1.bar.spark.baz' -> [1, 'spark'] for 'foo.*.bar.*.baz'
     *
     * @param  string  $attribute
     * @return array
     */
    protected function getExplicitKeys($attribute)
    {
        //                                                           获得主属性名
        $pattern = str_replace('\*', '([^\.]+)', preg_quote($this->getPrimaryAttribute($attribute), '/'));

        if (preg_match('/^'.$pattern.'/', $attribute, $keys)) {
            array_shift($keys);

            return $keys;
        }

        return [];
    }

    /**
     * Get the primary attribute name.
     *
     * 获得主属性名
     *
     * For example, if "name.0" is given, "name.*" will be returned.
     *
     * @param  string  $attribute
     * @return string
     */
    protected function getPrimaryAttribute($attribute)
    {
        foreach ($this->implicitAttributes as $unparsed => $parsed) {
            if (in_array($attribute, $parsed)) {
                return $unparsed;
            }
        }

        return $attribute;
    }

    /**
     * Replace each field parameter which has asterisks with the given keys.
     *
     * 用给定的键替换每个字段参数
     *
     * @param  array  $parameters
     * @param  array  $keys
     * @return array
     */
    protected function replaceAsterisksInParameters(array $parameters, array $keys)
    {
        return array_map(function ($field) use ($keys) {
            return vsprintf(str_replace('*', '%s', $field), $keys);
        }, $parameters);
    }

    /**
     * Determine if the attribute is validatable.
     *
     * 确定属性是否为确认性
     *
     * @param  string  $rule
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function isValidatable($rule, $attribute, $value)
    {
        return $this->presentOrRuleIsImplicit($rule, $attribute, $value) &&//确定字段是否存在，或者规则是否需要
               $this->passesOptionalCheck($attribute) &&//确定该属性是否通过了任何可选的检查
               $this->isNotNullIfMarkedAsNullable($attribute, $value) &&//确定属性是否失败了可空检查
               $this->hasNotFailedPreviousRuleIfPresenceRule($rule, $attribute);//确定这是不是必要的存在验证
    }

    /**
     * Determine if the field is present, or the rule implies required.
     *
     * 确定字段是否存在，或者规则是否需要
     *
     * @param  string  $rule
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function presentOrRuleIsImplicit($rule, $attribute, $value)
    {
        if (is_string($value) && trim($value) === '') {
            return $this->isImplicit($rule);//确定一个给定的规则是否意味着该属性是必需的
        }
        //验证一个属性是否存在，即使没有填充                       确定一个给定的规则是否意味着该属性是必需的
        return $this->validatePresent($attribute, $value) || $this->isImplicit($rule);
    }

    /**
     * Determine if a given rule implies the attribute is required.
     *
     * 确定一个给定的规则是否意味着该属性是必需的
     *
     * @param  string  $rule
     * @return bool
     */
    protected function isImplicit($rule)
    {
        return in_array($rule, $this->implicitRules);
    }

    /**
     * Determine if the attribute passes any optional check.
     *
     * 确定该属性是否通过了任何可选的检查
     *
     * @param  string  $attribute
     * @return bool
     */
    protected function passesOptionalCheck($attribute)
    {
        //确定给定属性在给定的集合中是否有规则
        if (! $this->hasRule($attribute, ['Sometimes'])) {
            return true;
        }

        $data = ValidationData::initializeAndGatherData($attribute, $this->data);

        return array_key_exists($attribute, $data)
                    || in_array($attribute, array_keys($this->data));
    }

    /**
     * Determine if the attribute fails the nullable check.
     *
     * 确定属性是否失败了可空检查
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    protected function isNotNullIfMarkedAsNullable($attribute, $value)
    {
        //确定给定属性在给定的集合中是否有规则
        if (! $this->hasRule($attribute, ['Nullable'])) {
            return true;
        }
        //          使用“点”符号从数组中获取一个项
        return ! is_null(Arr::get($this->data, $attribute, 0));
    }

    /**
     * Determine if it's a necessary presence validation.
     *
     * 确定这是不是必要的存在验证
     *
     * This is to avoid possible database type comparison errors.
     *
     * 这是为了避免可能出现的数据库类型比较错误
     *
     * @param  string  $rule
     * @param  string  $attribute
     * @return bool
     */
    protected function hasNotFailedPreviousRuleIfPresenceRule($rule, $attribute)
    {
        //                                                       确定所有给定的键是否存在消息
        return in_array($rule, ['Unique', 'Exists']) ? ! $this->messages->has($attribute) : true;
    }

    /**
     * Check if we should stop further validations on a given attribute.
     *
     * 检查是否应该停止对给定属性的进一步验证
     *
     * @param  string  $attribute
     * @return bool
     */
    protected function shouldStopValidating($attribute)
    {
        //确定给定属性在给定的集合中是否有规则
        if ($this->hasRule($attribute, ['Bail'])) {
            //               确定所有给定的键是否存在消息
            return $this->messages->has($attribute);
        }

        if (isset($this->failedRules[$attribute]) &&
            in_array('uploaded', array_keys($this->failedRules[$attribute]))) {
            return true;
        }

        // In case the attribute has any rule that indicates that the field is required
        // and that rule already failed then we should stop validation at this point
        // as now there is no point in calling other rules with this field empty.
        //
        // 如果属性有任何规则表明该字段是必需的，并且该规则已经失败，那么此时我们应该停止验证，就像现在没有必要调用该字段为空的其他规则
        //
        return $this->hasRule($attribute, $this->implicitRules) &&
               isset($this->failedRules[$attribute]) &&
               array_intersect(array_keys($this->failedRules[$attribute]), $this->implicitRules);
    }

    /**
     * Add a failed rule and error message to the collection.
     *
     * 向集合中添加一条失败的规则和错误消息
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return void
     */
    protected function addFailure($attribute, $rule, $parameters)
    {
        //             向包中添加信息              用实际值替换所有错误消息占位符
        $this->messages->add($attribute, $this->makeReplacements(
            //获取属性和规则的验证消息
            $this->getMessage($attribute, $rule), $attribute, $rule, $parameters
        ));

        $this->failedRules[$attribute][$rule] = $parameters;
    }

    /**
     * Returns the data which was valid.
     *
     * 返回有效的数据
     *
     * @return array
     */
    public function valid()
    {
        if (! $this->messages) {
            //确定数据是否通过验证规则
            $this->passes();
        }

        return array_diff_key(
            //                生成具有消息的所有属性的数组
            $this->data, $this->attributesThatHaveMessages()
        );
    }

    /**
     * Returns the data which was invalid.
     *
     * 返回无效的数据
     *
     * @return array
     */
    public function invalid()
    {
        if (! $this->messages) {
            //确定数据是否通过验证规则
            $this->passes();
        }

        return array_intersect_key(
            //                   生成具有消息的所有属性的数组
            $this->data, $this->attributesThatHaveMessages()
        );
    }

    /**
     * Generate an array of all attributes that have messages.
     *
     * 生成具有消息的所有属性的数组
     *
     * @return array
     */
    protected function attributesThatHaveMessages()
    {
        //               获取验证器的消息容器    将实例作为数组获取  在每个项目上运行map
        return collect($this->messages()->toArray())->map(function ($message, $key) {
            return explode('.', $key)[0];
        })->unique()->flip()->all();//只返回集合数组中的唯一项->在集合中翻转项目->获取集合中的所有项目
    }

    /**
     * Get the failed validation rules.
     *
     * 获取失败的验证规则
     *
     * @return array
     */
    public function failed()
    {
        return $this->failedRules;
    }

    /**
     * Get the message container for the validator.
     *
     * 获取验证器的消息容器
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function messages()
    {
        if (! $this->messages) {
            //确定数据是否通过验证规则
            $this->passes();
        }

        return $this->messages;
    }

    /**
     * An alternative more semantic shortcut to the message container.
     *
     * 消息容器的另一种语义快捷方式
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function errors()
    {
        //       获取验证器的消息容器
        return $this->messages();
    }

    /**
     * Get the messages for the instance.
     *
     * 获取实例的消息
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getMessageBag()
    {
        //         获取验证器的消息容器
        return $this->messages();
    }

    /**
     * Determine if the given attribute has a rule in the given set.
     *
     * 确定给定属性在给定的集合中是否有规则
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return bool
     */
    public function hasRule($attribute, $rules)
    {
        //                 获取给定属性的规则及其参数
        return ! is_null($this->getRule($attribute, $rules));
    }

    /**
     * Get a rule and its parameters for a given attribute.
     *
     * 获取给定属性的规则及其参数
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return array|null
     */
    protected function getRule($attribute, $rules)
    {
        if (! array_key_exists($attribute, $this->rules)) {
            return;
        }

        $rules = (array) $rules;

        foreach ($this->rules[$attribute] as $rule) {
            //                                           从规则中提取规则名和参数
            list($rule, $parameters) = ValidationRuleParser::parse($rule);

            if (in_array($rule, $rules)) {
                return [$rule, $parameters];
            }
        }
    }

    /**
     * Get the data under validation.
     *
     * 在验证中获取数据
     *
     * @return array
     */
    public function attributes()
    {
        //在验证中获取数据
        return $this->getData();
    }

    /**
     * Get the data under validation.
     *
     * 在验证中获取数据
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the data under validation.
     *
     * 在验证中设置数据
     *
     * @param  array  $data
     * @return $this
     */
    public function setData(array $data)
    {
        //                    解析数据数组，将点转换为->
        $this->data = $this->parseData($data);
        //设置验证规则
        $this->setRules($this->initialRules);

        return $this;
    }

    /**
     * Get the value of a given attribute.
     *
     * 获取给定属性的值
     *
     * @param  string  $attribute
     * @return mixed
     */
    protected function getValue($attribute)
    {
        //使用“点”符号从数组中获取一个项
        return Arr::get($this->data, $attribute);
    }

    /**
     * Get the validation rules.
     *
     * 得到验证规则
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Set the validation rules.
     *
     * 设置验证规则
     *
     * @param  array  $rules
     * @return $this
     */
    public function setRules(array $rules)
    {
        $this->initialRules = $rules;

        $this->rules = [];
        //解析给定的规则并将它们合并到当前规则中
        $this->addRules($rules);

        return $this;
    }

    /**
     * Parse the given rules and merge them into current rules.
     *
     * 解析给定的规则并将它们合并到当前规则中
     *
     * @param  array  $rules
     * @return void
     */
    public function addRules($rules)
    {
        // The primary purpose of this parser is to expand any "*" rules to the all
        // of the explicit rules needed for the given data. For example the rule
        // names.* would get expanded to names.0, names.1, etc. for this data.
        //
        // 这个解析器的主要目的是将任何“规则”扩展到给定数据所需的所有显式规则。例如规则名。会被扩展到名字。0,名字。对于这个数据，等等
        //
        $response = (new ValidationRuleParser($this->data))
                            ->explode($rules);//将友好的规则解析为验证器的完整规则数组

        $this->rules = array_merge_recursive(
            $this->rules, $response->rules
        );

        $this->implicitAttributes = array_merge(
            $this->implicitAttributes, $response->implicitAttributes
        );
    }

    /**
     * Add conditions to a given field based on a Closure.
     *
     * 基于闭包的条件添加条件
     *
     * @param  string|array  $attribute
     * @param  string|array  $rules
     * @param  callable  $callback
     * @return $this
     */
    public function sometimes($attribute, $rules, callable $callback)
    {
        //                 在验证中获取数据
        $payload = new Fluent($this->getData());

        if (call_user_func($callback, $payload)) {
            foreach ((array) $attribute as $key) {
                //    解析给定的规则并将它们合并到当前规则中
                $this->addRules([$key => $rules]);
            }
        }

        return $this;
    }

    /**
     * Register an array of custom validator extensions.
     *
     * 注册一个自定义验证器扩展的数组
     *
     * @param  array  $extensions
     * @return void
     */
    public function addExtensions(array $extensions)
    {
        if ($extensions) {
            $keys = array_map('\Illuminate\Support\Str::snake', array_keys($extensions));

            $extensions = array_combine($keys, array_values($extensions));
        }

        $this->extensions = array_merge($this->extensions, $extensions);
    }

    /**
     * Register an array of custom implicit validator extensions.
     *
     * 注册一组自定义的隐式验证器扩展
     *
     * @param  array  $extensions
     * @return void
     */
    public function addImplicitExtensions(array $extensions)
    {
        //注册一个自定义验证器扩展的数组
        $this->addExtensions($extensions);

        foreach ($extensions as $rule => $extension) {
            //                         将值转换为大驼峰
            $this->implicitRules[] = Str::studly($rule);
        }
    }

    /**
     * Register a custom validator extension.
     *
     * 注册一个定制的验证器扩展
     *
     * @param  string  $rule
     * @param  \Closure|string  $extension
     * @return void
     */
    public function addExtension($rule, $extension)
    {
        //         将字符串转换为蛇形命名
        $this->extensions[Str::snake($rule)] = $extension;
    }

    /**
     * Register a custom implicit validator extension.
     *
     * 注册一个自定义的隐式验证器扩展
     *
     * @param  string   $rule
     * @param  \Closure|string  $extension
     * @return void
     */
    public function addImplicitExtension($rule, $extension)
    {
        //注册一个自定义验证器扩展的数组
        $this->addExtension($rule, $extension);
        //                     将值转换为大驼峰
        $this->implicitRules[] = Str::studly($rule);
    }

    /**
     * Register an array of custom validator message replacers.
     *
     * 注册一个自定义验证器消息替换器的数组
     *
     * @param  array  $replacers
     * @return void
     */
    public function addReplacers(array $replacers)
    {
        if ($replacers) {
            $keys = array_map('\Illuminate\Support\Str::snake', array_keys($replacers));

            $replacers = array_combine($keys, array_values($replacers));
        }

        $this->replacers = array_merge($this->replacers, $replacers);
    }

    /**
     * Register a custom validator message replacer.
     *
     * 注册一个自定义验证器消息替换器
     *
     * @param  string  $rule
     * @param  \Closure|string  $replacer
     * @return void
     */
    public function addReplacer($rule, $replacer)
    {
        //        将字符串转换为蛇形命名
        $this->replacers[Str::snake($rule)] = $replacer;
    }

    /**
     * Set the custom messages for the validator.
     *
     * 为验证器设置定制消息
     *
     * @param  array  $messages
     * @return void
     */
    public function setCustomMessages(array $messages)
    {
        $this->customMessages = array_merge($this->customMessages, $messages);
    }

    /**
     * Set the custom attributes on the validator.
     *
     * 在验证器上设置定制属性
     *
     * @param  array  $attributes
     * @return $this
     */
    public function setAttributeNames(array $attributes)
    {
        $this->customAttributes = $attributes;

        return $this;
    }

    /**
     * Add custom attributes to the validator.
     *
     * 向验证器添加自定义属性
     *
     * @param  array  $customAttributes
     * @return $this
     */
    public function addCustomAttributes(array $customAttributes)
    {
        $this->customAttributes = array_merge($this->customAttributes, $customAttributes);

        return $this;
    }

    /**
     * Set the custom values on the validator.
     *
     * 在验证器上设置定制值
     *
     * @param  array  $values
     * @return $this
     */
    public function setValueNames(array $values)
    {
        $this->customValues = $values;

        return $this;
    }

    /**
     * Add the custom values for the validator.
     *
     * 为验证器添加定制值
     *
     * @param  array  $customValues
     * @return $this
     */
    public function addCustomValues(array $customValues)
    {
        $this->customValues = array_merge($this->customValues, $customValues);

        return $this;
    }

    /**
     * Set the fallback messages for the validator.
     *
     * 为验证器设置回退消息
     *
     * @param  array  $messages
     * @return void
     */
    public function setFallbackMessages(array $messages)
    {
        $this->fallbackMessages = $messages;
    }

    /**
     * Get the Presence Verifier implementation.
     *
     * 获得存在验证器的实现
     *
     * @return \Illuminate\Validation\PresenceVerifierInterface
     *
     * @throws \RuntimeException
     */
    public function getPresenceVerifier()
    {
        if (! isset($this->presenceVerifier)) {
            throw new RuntimeException('Presence verifier has not been set.');
        }

        return $this->presenceVerifier;
    }

    /**
     * Get the Presence Verifier implementation.
     *
     * 获得存在验证器的实现
     *
     * @param  string  $connection
     * @return \Illuminate\Validation\PresenceVerifierInterface
     *
     * @throws \RuntimeException
     */
    protected function getPresenceVerifierFor($connection)
    {
        //用给定的值调用给定的闭包，然后返回值   获得存在验证器的实现
        return tap($this->getPresenceVerifier(), function ($verifier) use ($connection) {
            $verifier->setConnection($connection);
        });
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
        $this->presenceVerifier = $presenceVerifier;
    }

    /**
     * Get the Translator implementation.
     *
     * 得到翻译实现
     *
     * @return \Illuminate\Contracts\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Set the Translator implementation.
     *
     * 设置翻译实现
     *
     * @param  \Illuminate\Contracts\Translation\Translator  $translator
     * @return void
     */
    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Set the IoC container instance.
     *
     * 设置IoC容器实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Call a custom validator extension.
     *
     * 调用自定义验证器扩展
     *
     * @param  string  $rule
     * @param  array   $parameters
     * @return bool|null
     */
    protected function callExtension($rule, $parameters)
    {
        $callback = $this->extensions[$rule];

        if ($callback instanceof Closure) {
            return call_user_func_array($callback, $parameters);
        } elseif (is_string($callback)) {
            //            调用基于类的验证器扩展
            return $this->callClassBasedExtension($callback, $parameters);
        }
    }

    /**
     * Call a class based validator extension.
     *
     * 调用基于类的验证器扩展
     *
     * @param  string  $callback
     * @param  array   $parameters
     * @return bool
     */
    protected function callClassBasedExtension($callback, $parameters)
    {
        //                           解析 类@方法 类型回调到类和方法
        list($class, $method) = Str::parseCallback($callback, 'validate');
        //                                  从容器中解析给定类型
        return call_user_func_array([$this->container->make($class), $method], $parameters);
    }

    /**
     * Handle dynamic calls to class methods.
     *
     * 处理类方法的动态调用
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        //将字符串转换为蛇形命名
        $rule = Str::snake(substr($method, 8));

        if (isset($this->extensions[$rule])) {
            //           调用自定义验证器扩展
            return $this->callExtension($rule, $parameters);
        }

        throw new BadMethodCallException("Method [$method] does not exist.");
    }
}
