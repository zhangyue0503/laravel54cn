<?php

namespace Illuminate\View\Compilers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BladeCompiler extends Compiler implements CompilerInterface
{
    use Concerns\CompilesAuthorizations,
        Concerns\CompilesComments,
        Concerns\CompilesComponents,
        Concerns\CompilesConditionals,
        Concerns\CompilesEchos,
        Concerns\CompilesIncludes,
        Concerns\CompilesInjections,
        Concerns\CompilesLayouts,
        Concerns\CompilesLoops,
        Concerns\CompilesRawPhp,
        Concerns\CompilesStacks,
        Concerns\CompilesTranslations;

    /**
     * All of the registered extensions.
     *
     * 所有注册的扩展
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * All custom "directive" handlers.
     *
     * 所有自定义“指令”处理程序
     *
     * This was implemented as a more usable "extend" in 5.1.
     *
     * 这在5.1中被实现为一个更有用的“扩展”
     *
     * @var array
     */
    protected $customDirectives = [];

    /**
     * The file currently being compiled.
     *
     * 当前正在编译的文件
     *
     * @var string
     */
    protected $path;

    /**
     * All of the available compiler functions.
     *
     * 所有可用的编译器函数
     *
     * @var array
     */
    protected $compilers = [
        'Comments',
        'Extensions',
        'Statements',
        'Echos',
    ];

    /**
     * Array of opening and closing tags for raw echos.
     *
     * 原始echos的开闭标签阵列
     *
     * @var array
     */
    protected $rawTags = ['{!!', '!!}'];

    /**
     * Array of opening and closing tags for regular echos.
     *
     * 常规echos的开闭标签阵列
     *
     * @var array
     */
    protected $contentTags = ['{{', '}}'];

    /**
     * Array of opening and closing tags for escaped echos.
     *
     * 用于转义的回声的打开和关闭标签的数组
     *
     * @var array
     */
    protected $escapedTags = ['{{{', '}}}'];

    /**
     * The "regular" / legacy echo string format.
     *
     * “常规”/遗留的echo字符串格式。
     *
     * @var string
     */
    protected $echoFormat = 'e(%s)';

    /**
     * Array of footer lines to be added to template.
     *
     * 添加到模板的页脚行数组
     *
     * @var array
     */
    protected $footer = [];

    /**
     * Placeholder to temporary mark the position of verbatim blocks.
     *
     * 占位符临时标记逐字块的位置
     *
     * @var string
     */
    protected $verbatimPlaceholder = '@__verbatim__@';

    /**
     * Array to temporary store the verbatim blocks found in the template.
     *
     * 临时存储在模板中找到的逐字块
     *
     * @var array
     */
    protected $verbatimBlocks = [];

    /**
     * Compile the view at the given path.
     *
     * 在给定的路径上编译视图
     *
     * @param  string  $path
     * @return void
     */
    public function compile($path = null)
    {
        if ($path) {
            $this->setPath($path);
        }

        if (! is_null($this->cachePath)) {
            $contents = $this->compileString($this->files->get($this->getPath()));

            $this->files->put($this->getCompiledPath($this->getPath()), $contents);
        }
    }

    /**
     * Get the path currently being compiled.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the path currently being compiled.
     *
     * @param  string  $path
     * @return void
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Compile the given Blade template contents.
     *
     * 编译给定的Blade模板内容
     *
     * @param  string  $value
     * @return string
     */
    public function compileString($value)
    {
        $result = '';

        if (strpos($value, '@verbatim') !== false) {
            //           将逐字块存储并替换为临时占位符
            $value = $this->storeVerbatimBlocks($value);
        }

        $this->footer = [];

        // Here we will loop through all of the tokens returned by the Zend lexer and
        // parse each one into the corresponding valid PHP. We will then have this
        // template as the correctly rendered PHP that can be rendered natively.
        //
        // 在这里，我们将遍历Zend lexer返回的所有令牌，并将每个标记解析为相应的有效PHP
        // 然后，我们将使用该模板作为正确呈现的PHP，可以在本地呈现
        //
        foreach (token_get_all($value) as $token) {
            //                             从模板中解析标记
            $result .= is_array($token) ? $this->parseToken($token) : $token;
        }

        if (! empty($this->verbatimBlocks)) {
            //                   将原始的占位符替换为原始代码中存储的原始代码
            $result = $this->restoreVerbatimBlocks($result);
        }

        // If there are any footer lines that need to get added to a template we will
        // add them here at the end of the template. This gets used mainly for the
        // template inheritance via the extends keyword that should be appended.
        //
        // 如果有任何需要添加到模板的页脚行，我们将在模板的末尾添加它们。这主要用于模板继承，通过扩展关键字应该被追加
        //
        if (count($this->footer) > 0) {
            //将存储的页脚添加到给定的内容中
            $result = $this->addFooters($result);
        }

        return $result;
    }

    /**
     * Store the verbatim blocks and replace them with a temporary placeholder.
     *
     * 将逐字块存储并替换为临时占位符
     *
     * @param  string  $value
     * @return string
     */
    protected function storeVerbatimBlocks($value)
    {
        return preg_replace_callback('/(?<!@)@verbatim(.*?)@endverbatim/s', function ($matches) {
            $this->verbatimBlocks[] = $matches[1];

            return $this->verbatimPlaceholder;
        }, $value);
    }

    /**
     * Replace the raw placeholders with the original code stored in the raw blocks.
     *
     * 将原始的占位符替换为原始代码中存储的原始代码
     *
     * @param  string  $result
     * @return string
     */
    protected function restoreVerbatimBlocks($result)
    {
        $result = preg_replace_callback('/'.preg_quote($this->verbatimPlaceholder).'/', function () {
            return array_shift($this->verbatimBlocks);
        }, $result);

        $this->verbatimBlocks = [];

        return $result;
    }

    /**
     * Add the stored footers onto the given content.
     *
     * 将存储的页脚添加到给定的内容中
     *
     * @param  string  $result
     * @return string
     */
    protected function addFooters($result)
    {
        return ltrim($result, PHP_EOL)
                .PHP_EOL.implode(PHP_EOL, array_reverse($this->footer));
    }

    /**
     * Parse the tokens from the template.
     *
     * 从模板中解析标记
     *
     * @param  array  $token
     * @return string
     */
    protected function parseToken($token)
    {
        list($id, $content) = $token;

        if ($id == T_INLINE_HTML) {
            foreach ($this->compilers as $type) {
                $content = $this->{"compile{$type}"}($content);
            }
        }

        return $content;
    }

    /**
     * Execute the user defined extensions.
     *
     * 执行用户定义的扩展
     *
     * @param  string  $value
     * @return string
     */
    protected function compileExtensions($value)
    {
        foreach ($this->extensions as $compiler) {
            $value = call_user_func($compiler, $value, $this);
        }

        return $value;
    }

    /**
     * Compile Blade statements that start with "@".
     *
     * 编译从“@”开始的Blade语句
     *
     * @param  string  $value
     * @return string
     */
    protected function compileStatements($value)
    {
        return preg_replace_callback(
            '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', function ($match) {
                //编译单个Blade@语句
                return $this->compileStatement($match);
            }, $value
        );
    }

    /**
     * Compile a single Blade @ statement.
     *
     * 编译单个Blade@语句
     *
     * @param  array  $match
     * @return string
     */
    protected function compileStatement($match)
    {
        //确定一个给定的字符串包含另一个字符串
        if (Str::contains($match[1], '@')) {
            $match[0] = isset($match[3]) ? $match[1].$match[3] : $match[1];
        } elseif (isset($this->customDirectives[$match[1]])) {
            //             用给定的值调用给定的指令                使用“点”符号从数组中获取一个项
            $match[0] = $this->callCustomDirective($match[1], Arr::get($match, 3));
        } elseif (method_exists($this, $method = 'compile'.ucfirst($match[1]))) {
            $match[0] = $this->$method(Arr::get($match, 3));
        }

        return isset($match[3]) ? $match[0] : $match[0].$match[2];
    }

    /**
     * Call the given directive with the given value.
     *
     * 用给定的值调用给定的指令
     *
     * @param  string  $name
     * @param  string|null  $value
     * @return string
     */
    protected function callCustomDirective($name, $value)
    {
        //确定给定的子字符串是否属于给定的字符串     确定给定的字符串的结束是否是给定的子字符串
        if (Str::startsWith($value, '(') && Str::endsWith($value, ')')) {
            //返回由开始和长度参数指定的字符串的一部分
            $value = Str::substr($value, 1, -1);
        }

        return call_user_func($this->customDirectives[$name], trim($value));
    }

    /**
     * Strip the parentheses from the given expression.
     *
     * 从给定表达式中去掉括号
     *
     * @param  string  $expression
     * @return string
     */
    public function stripParentheses($expression)
    {
        //确定给定的子字符串是否属于给定的字符串
        if (Str::startsWith($expression, '(')) {
            $expression = substr($expression, 1, -1);
        }

        return $expression;
    }

    /**
     * Register a custom Blade compiler.
     *
     * 注册一个自定义的Blade编译器
     *
     * @param  callable  $compiler
     * @return void
     */
    public function extend(callable $compiler)
    {
        $this->extensions[] = $compiler;
    }

    /**
     * Get the extensions used by the compiler.
     *
     * 获取编译器使用的扩展
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Register a handler for custom directives.
     *
     * 为定制指令注册一个处理程序
     *
     * @param  string  $name
     * @param  callable  $handler
     * @return void
     */
    public function directive($name, callable $handler)
    {
        $this->customDirectives[$name] = $handler;
    }

    /**
     * Get the list of custom directives.
     *
     * 获取定制指令的列表
     *
     * @return array
     */
    public function getCustomDirectives()
    {
        return $this->customDirectives;
    }

    /**
     * Set the echo format to be used by the compiler.
     *
     * 设置编译器使用的echo格式
     *
     * @param  string  $format
     * @return void
     */
    public function setEchoFormat($format)
    {
        $this->echoFormat = $format;
    }
}
