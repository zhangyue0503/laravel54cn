<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing;

/**
 * A Route describes a route and its parameters.
 *
 * 描述路由及其参数的路由
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Tobias Schultze <http://tobion.de>
 */
class Route implements \Serializable
{
    /**
     * @var string
     */
    private $path = '/';

    /**
     * @var string
     */
    private $host = '';

    /**
     * @var array
     */
    private $schemes = array();

    /**
     * @var array
     */
    private $methods = array();

    /**
     * @var array
     */
    private $defaults = array();

    /**
     * @var array
     */
    private $requirements = array();

    /**
     * @var array
     */
    private $options = array();

    /**
     * @var null|CompiledRoute
     */
    private $compiled;

    /**
     * @var string
     */
    private $condition = '';

    /**
     * Constructor.
     *
     * Available options:
     *
     *  * compiler_class: A class name able to compile this route instance (RouteCompiler by default)
     *  * 一个能够编译这个路由实例类的名字（routecompiler默认）
     *  * utf8:           Whether UTF-8 matching is enforced ot not
     *  *                 无论是UTF-8执行不能匹配
     *
     * @param string       $path         The path pattern to match 路径匹配模式
     * @param array        $defaults     An array of default parameter values 默认参数值数组
     * @param array        $requirements An array of requirements for parameters (regexes) 要求的参数的数组（正则表达式）
     * @param array        $options      An array of options 选项数组
     * @param string       $host         The host pattern to match 主机模式匹配
     * @param string|array $schemes      A required URI scheme or an array of restricted schemes 所需的URI方案或限制方案数组
     * @param string|array $methods      A required HTTP method or an array of restricted methods  所需的HTTP方法或数组的限制方法
     * @param string       $condition    A condition that should evaluate to true for the route to match 它应该是两个威胁评估的两个匹配的路由
     */
    public function __construct($path, array $defaults = array(), array $requirements = array(), array $options = array(), $host = '', $schemes = array(), $methods = array(), $condition = '')
    {
        $this->setPath($path);
        $this->setDefaults($defaults);
        $this->setRequirements($requirements);
        $this->setOptions($options);
        $this->setHost($host);
        $this->setSchemes($schemes);
        $this->setMethods($methods);
        $this->setCondition($condition);
    }

    /**
     * {@inheritdoc}
     * 序列化接口要实现的方法
     */
    public function serialize()
    {
        return serialize(array(
            'path' => $this->path,
            'host' => $this->host,
            'defaults' => $this->defaults,
            'requirements' => $this->requirements,
            'options' => $this->options,
            'schemes' => $this->schemes,
            'methods' => $this->methods,
            'condition' => $this->condition,
            'compiled' => $this->compiled,
        ));
    }

    /**
     * {@inheritdoc}
     * 序列化接口要实现的方法
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->path = $data['path'];
        $this->host = $data['host'];
        $this->defaults = $data['defaults'];
        $this->requirements = $data['requirements'];
        $this->options = $data['options'];
        $this->schemes = $data['schemes'];
        $this->methods = $data['methods'];

        if (isset($data['condition'])) {
            $this->condition = $data['condition'];
        }
        if (isset($data['compiled'])) {
            $this->compiled = $data['compiled'];
        }
    }

    /**
     * Returns the pattern for the path.
     *
     * 返回路径
     *
     * @return string The path pattern
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sets the pattern for the path.
     *
     * 设置路径的模式
     *
     * This method implements a fluent interface.
     *
     * 该方法实现了一个流接口
     *
     * @param string $pattern The path pattern
     *
     * @return $this
     */
    public function setPath($pattern)
    {
        // A pattern must start with a slash and must not have multiple slashes at the beginning because the
        // generated path for this route would be confused with a network path, e.g. '//domain.com/path'.
        // 模式必须以一个斜线不能有多个斜杠开始因为这条路将与网络路径容易混淆，如“/域名.com /路径”。
        $this->path = '/'.ltrim(trim($pattern), '/');
        $this->compiled = null;

        return $this;
    }

    /**
     * Returns the pattern for the host.
     *
     * 返回主机的模式
     *
     * @return string The host pattern
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the pattern for the host.
     *
     * 设置主机的模式
     *
     * This method implements a fluent interface.
     *
     * 该方法实现了一个流接口
     *
     * @param string $pattern The host pattern
     *
     * @return $this
     */
    public function setHost($pattern)
    {
        $this->host = (string) $pattern;
        $this->compiled = null;

        return $this;
    }

    /**
     * Returns the lowercased schemes this route is restricted to.
     *
     * 返回小写格式的限制路由
     *
     * So an empty array means that any scheme is allowed.
     *
     * 空数组意味着任何方案是允许的
     *
     * @return array The schemes
     */
    public function getSchemes()
    {
        return $this->schemes;
    }

    /**
     * Sets the schemes (e.g. 'https') this route is restricted to.
     *
     * 设置受限制的计划（如“HTTPS”）
     *
     * So an empty array means that any scheme is allowed.
     * 空数组意味着任何方案是允许的
     *
     *
     * This method implements a fluent interface.
     *
     * 该方法实现了流接口
     *
     * @param string|array $schemes The scheme or an array of schemes
     *
     * @return $this
     */
    public function setSchemes($schemes)
    {
        $this->schemes = array_map('strtolower', (array) $schemes);
        $this->compiled = null;

        return $this;
    }

    /**
     * Checks if a scheme requirement has been set.
     *
     * 检查计划的要求是否已设置
     *
     * @param string $scheme
     *
     * @return bool true if the scheme requirement exists, otherwise false 如果计划要求存在返回true，否则返回false
     */
    public function hasScheme($scheme)
    {
        return in_array(strtolower($scheme), $this->schemes, true);
    }

    /**
     * Returns the uppercased HTTP methods this route is restricted to.
     *
     * 返回受限制的大写的HTTP方法路线
     *
     * So an empty array means that any method is allowed.
     *
     * 空数组意味着任何方案是允许的
     *
     * @return array The methods
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Sets the HTTP methods (e.g. 'POST') this route is restricted to.
     *
     * 设置受限制的路由HTTP方法（如“POST”）
     *
     * So an empty array means that any method is allowed.
     *
     * 空数组意味着任何方案是允许的
     *
     * This method implements a fluent interface.
     *
     * 该方法实现了流接口
     *
     * @param string|array $methods The method or an array of methods
     *
     * @return $this
     */
    public function setMethods($methods)
    {
        $this->methods = array_map('strtoupper', (array) $methods);
        $this->compiled = null;

        return $this;
    }

    /**
     * Returns the options.
     *
     * 返回选项
     *
     * @return array The options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Sets the options.
     *
     * 设置选项
     *
     * This method implements a fluent interface.
     *
     * 空数组意味着任何方案是允许的
     *
     * @param array $options The options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = array(
            'compiler_class' => 'Symfony\\Component\\Routing\\RouteCompiler',
        );

        return $this->addOptions($options);
    }

    /**
     * Adds options.
     *
     * 添加选项
     *
     * This method implements a fluent interface.
     *
     * 该方法实现了流接口
     *
     * @param array $options The options
     *
     * @return $this
     */
    public function addOptions(array $options)
    {
        foreach ($options as $name => $option) {
            $this->options[$name] = $option;
        }
        $this->compiled = null;

        return $this;
    }

    /**
     * Sets an option value.
     *
     * 设置一个选项的值
     *
     * This method implements a fluent interface.
     *
     * 该方法实现了流接口
     *
     * @param string $name  An option name
     * @param mixed  $value The option value
     *
     * @return $this
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        $this->compiled = null;

        return $this;
    }

    /**
     * Get an option value.
     *
     * 获取一个选项的值
     *
     * @param string $name An option name
     *
     * @return mixed The option value or null when not given
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * Checks if an option has been set.
     *
     * 检查一个选项是否被设置
     *
     * @param string $name An option name
     *
     * @return bool true if the option is set, false otherwise
     */
    public function hasOption($name)
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * Returns the defaults.
     *
     * 返回默认数组
     *
     * @return array The defaults
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Sets the defaults.
     *
     * 设置默认数组
     *
     * This method implements a fluent interface.
     *
     * 该方法实现了流接口
     *
     * @param array $defaults The defaults
     *
     * @return $this
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = array();

        return $this->addDefaults($defaults);
    }

    /**
     * Adds defaults.
     *
     * 添加默认数组
     *
     * This method implements a fluent interface.
     *
     * 该方法实现了流接口
     *
     * @param array $defaults The defaults
     *
     * @return $this
     */
    public function addDefaults(array $defaults)
    {
        foreach ($defaults as $name => $default) {
            $this->defaults[$name] = $default;
        }
        $this->compiled = null;

        return $this;
    }

    /**
     * Gets a default value.
     *
     * 获取默认的值
     *
     * @param string $name A variable name
     *
     * @return mixed The default value or null when not given
     */
    public function getDefault($name)
    {
        return isset($this->defaults[$name]) ? $this->defaults[$name] : null;
    }

    /**
     * Checks if a default value is set for the given variable.
     *
     * 检查给定变量是否设置默认值
     *
     * @param string $name A variable name
     *
     * @return bool true if the default value is set, false otherwise
     */
    public function hasDefault($name)
    {
        return array_key_exists($name, $this->defaults);
    }

    /**
     * Sets a default value.
     *
     * 设置默认值
     *
     * @param string $name    A variable name
     * @param mixed  $default The default value
     *
     * @return $this
     */
    public function setDefault($name, $default)
    {
        $this->defaults[$name] = $default;
        $this->compiled = null;

        return $this;
    }

    /**
     * Returns the requirements.
     *
     *
     *
     * @return array The requirements
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * Sets the requirements.
     *
     * This method implements a fluent interface.
     *
     * @param array $requirements The requirements
     *
     * @return $this
     */
    public function setRequirements(array $requirements)
    {
        $this->requirements = array();

        return $this->addRequirements($requirements);
    }

    /**
     * Adds requirements.
     *
     * This method implements a fluent interface.
     *
     * @param array $requirements The requirements
     *
     * @return $this
     */
    public function addRequirements(array $requirements)
    {
        foreach ($requirements as $key => $regex) {
            $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);
        }
        $this->compiled = null;

        return $this;
    }

    /**
     * Returns the requirement for the given key.
     *
     * @param string $key The key
     *
     * @return string|null The regex or null when not given
     */
    public function getRequirement($key)
    {
        return isset($this->requirements[$key]) ? $this->requirements[$key] : null;
    }

    /**
     * Checks if a requirement is set for the given key.
     *
     * @param string $key A variable name
     *
     * @return bool true if a requirement is specified, false otherwise
     */
    public function hasRequirement($key)
    {
        return array_key_exists($key, $this->requirements);
    }

    /**
     * Sets a requirement for the given key.
     *
     * @param string $key   The key
     * @param string $regex The regex
     *
     * @return $this
     */
    public function setRequirement($key, $regex)
    {
        $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);
        $this->compiled = null;

        return $this;
    }

    /**
     * Returns the condition.
     *
     * @return string The condition
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Sets the condition.
     *
     * This method implements a fluent interface.
     *
     * @param string $condition The condition
     *
     * @return $this
     */
    public function setCondition($condition)
    {
        $this->condition = (string) $condition;
        $this->compiled = null;

        return $this;
    }

    /**
     * Compiles the route.
     *
     * 编译路由
     *
     * @return CompiledRoute A CompiledRoute instance 编译后的编译路由实例
     *
     * @throws \LogicException If the Route cannot be compiled because the
     *                         path or host pattern is invalid
     *
     * @see RouteCompiler which is responsible for the compilation process
     */
    public function compile()
    {
        if (null !== $this->compiled) {
            return $this->compiled;
        }

        $class = $this->getOption('compiler_class');

        return $this->compiled = $class::compile($this);
    }

    private function sanitizeRequirement($key, $regex)
    {
        if (!is_string($regex)) {
            throw new \InvalidArgumentException(sprintf('Routing requirement for "%s" must be a string.', $key));
        }

        if ('' !== $regex && '^' === $regex[0]) {
            $regex = (string) substr($regex, 1); // returns false for a single character
        }

        if ('$' === substr($regex, -1)) {
            $regex = substr($regex, 0, -1);
        }

        if ('' === $regex) {
            throw new \InvalidArgumentException(sprintf('Routing requirement for "%s" cannot be empty.', $key));
        }

        return $regex;
    }
}
