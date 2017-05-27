<?php

namespace Illuminate\Translation;

use Countable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\NamespacedItemResolver;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;

class Translator extends NamespacedItemResolver implements TranslatorContract
{
    use Macroable;

    /**
     * The loader implementation.
     *
     * 加载程序实现
     *
     * @var \Illuminate\Translation\LoaderInterface
     */
    protected $loader;

    /**
     * The default locale being used by the translator.
     *
     * 翻译器使用的默认语言环境
     *
     * @var string
     */
    protected $locale;

    /**
     * The fallback locale used by the translator.
     *
     * 翻译器使用的回退地区
     *
     * @var string
     */
    protected $fallback;

    /**
     * The array of loaded translation groups.
     *
     * 加载的翻译组的数组
     *
     * @var array
     */
    protected $loaded = [];

    /**
     * The message selector.
     *
     * 消息选择器
     *
     * @var \Illuminate\Translation\MessageSelector
     */
    protected $selector;

    /**
     * Create a new translator instance.
     *
     * 创建一个新的翻译实例
     *
     * @param  \Illuminate\Translation\LoaderInterface  $loader
     * @param  string  $locale
     * @return void
     */
    public function __construct(LoaderInterface $loader, $locale)
    {
        $this->loader = $loader;
        $this->locale = $locale;
    }

    /**
     * Determine if a translation exists for a given locale.
     *
     * 确定给定地区的翻译是否存在
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @return bool
     */
    public function hasForLocale($key, $locale = null)
    {
        //确定翻译是否存在
        return $this->has($key, $locale, false);
    }

    /**
     * Determine if a translation exists.
     *
     * 确定翻译是否存在
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return bool
     */
    public function has($key, $locale = null, $fallback = true)
    {
        //获取给定键的翻译
        return $this->get($key, [], $locale, $fallback) !== $key;
    }

    /**
     * Get the translation for a given key.
     *
     * 获取给定键的翻译
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return string|array|null
     */
    public function trans($key, array $replace = [], $locale = null)
    {
        //获取给定键的翻译
        return $this->get($key, $replace, $locale);
    }

    /**
     * Get the translation for the given key.
     *
     * 获取给定键的翻译
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array|null
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        //                                   将一个键解析为名称空间、组和项
        list($namespace, $group, $item) = $this->parseKey($key);

        // Here we will get the locale that should be used for the language line. If one
        // was not passed, we will use the default locales which was given to us when
        // the translator was instantiated. Then, we can load the lines and return.
        //
        // 在这里，我们将获得用于语言行的语言环境
        // 如果没有传递一个，我们将使用当翻译器实例化时给我们的默认区域
        // 然后，我们可以加载这些行并返回
        //
        //                         获取要检查的地区的数组
        $locales = $fallback ? $this->localeArray($locale)
                             : [$locale ?: $this->locale];

        foreach ($locales as $locale) {
            //                      检索已加载数组的语言行
            if (! is_null($line = $this->getLine(
                $namespace, $group, $locale, $item, $replace
            ))) {
                break;
            }
        }

        // If the line doesn't exist, we will return back the key which was requested as
        // that will be quick to spot in the UI if language keys are wrong or missing
        // from the application's language files. Otherwise we can return the line.
        //
        // 如果该行不存在，我们将返回所请求的键，因为如果语言键错误或从应用程序的语言文件中丢失，那么将很快在UI中发现该键
        // 否则我们就可以返回直线了
        //
        if (isset($line)) {
            return $line;
        }

        return $key;
    }

    /**
     * Get the translation for a given key from the JSON translation files.
     *
     * 从JSON翻译文件获取给定键的翻译
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string  $locale
     * @return string
     */
    public function getFromJson($key, array $replace = [], $locale = null)
    {
        $locale = $locale ?: $this->locale;

        // For JSON translations, there is only one file per locale, so we will simply load
        // that file and then we will be ready to check the array for the key. These are
        // only one level deep so we do not need to do any fancy searching through it.
        //
        // 对于JSON翻译，每个地区只有一个文件，所以我们只需要加载该文件，然后我们就可以为键检查数组了
        // 这些只是一个级别的深度，所以我们不需要进行任何复杂的搜索
        //
        //      加载指定的语言组
        $this->load('*', '*', $locale);

        $line = isset($this->loaded['*']['*'][$locale][$key])
                    ? $this->loaded['*']['*'][$locale][$key] : null;

        // If we can't find a translation for the JSON key, we will attempt to translate it
        // using the typical translation file. This way developers can always just use a
        // helper such as __ instead of having to pick between trans or __ with views.
        //
        // 如果我们找不到JSON键的翻译，我们将尝试使用典型的翻译文件来翻译它
        // 这样，开发人员就可以只使用一个助手，而不必在转换或视图之间进行选择
        //
        if (! isset($line)) {
            //                 获取给定键的翻译
            $fallback = $this->get($key, $replace, $locale);

            if ($fallback !== $key) {
                return $fallback;
            }
        }
        //在线路上做一个定位器替换
        return $this->makeReplacements($line ?: $key, $replace);
    }

    /**
     * Get a translation according to an integer value.
     *
     * 根据整数值得到一个翻译
     *
     * @param  string  $key
     * @param  int|array|\Countable  $number
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function transChoice($key, $number, array $replace = [], $locale = null)
    {
        //根据一个整数值得到一个翻译
        return $this->choice($key, $number, $replace, $locale);
    }

    /**
     * Get a translation according to an integer value.
     *
     * 根据一个整数值得到一个翻译
     *
     * @param  string  $key
     * @param  int|array|\Countable  $number
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function choice($key, $number, array $replace = [], $locale = null)
    {
        //获取给定键的翻译
        $line = $this->get(
            //                              为选择操作获得适当的场所
            $key, $replace, $locale = $this->localeForChoice($locale)
        );

        // If the given "number" is actually an array or countable we will simply count the
        // number of elements in an instance. This allows developers to pass an array of
        // items without having to count it on their end first which gives bad syntax.
        //
        // 如果给定的“number”实际上是一个数组或可数的数，那么我们只需要计算一个实例中元素的数量
        // 这使得开发人员可以通过一系列的项目，而不必在第一个结束时将其计算为糟糕的语法
        //
        if (is_array($number) || $number instanceof Countable) {
            $number = count($number);
        }

        $replace['count'] = $number;
        //         在线路上做一个定位器替换
        return $this->makeReplacements(
            //获取消息选择器实例->根据给定的数字选择适当的翻译字符串
            $this->getSelector()->choose($line, $number, $locale), $replace
        );
    }

    /**
     * Get the proper locale for a choice operation.
     *
     * 为选择操作获得适当的场所
     *
     * @param  string|null  $locale
     * @return string
     */
    protected function localeForChoice($locale)
    {
        return $locale ?: $this->locale ?: $this->fallback;
    }

    /**
     * Retrieve a language line out the loaded array.
     *
     * 检索已加载数组的语言行
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @param  string  $item
     * @param  array   $replace
     * @return string|array|null
     */
    protected function getLine($namespace, $group, $locale, $item, array $replace)
    {
        //加载指定的语言组
        $this->load($namespace, $group, $locale);
        //使用“点”符号从数组中获取一个项
        $line = Arr::get($this->loaded[$namespace][$group][$locale], $item);

        if (is_string($line)) {
            //          在线路上做一个定位器替换
            return $this->makeReplacements($line, $replace);
        } elseif (is_array($line) && count($line) > 0) {
            return $line;
        }
    }

    /**
     * Make the place-holder replacements on a line.
     *
     * 在线路上做一个定位器替换
     *
     * @param  string  $line
     * @param  array   $replace
     * @return string
     */
    protected function makeReplacements($line, array $replace)
    {
        //                   替换数组排序
        $replace = $this->sortReplacements($replace);

        foreach ($replace as $key => $value) {
            $line = str_replace(
                //            将给定的字符串转换为大写       使字符串的第一个字符大写
                [':'.$key, ':'.Str::upper($key), ':'.Str::ucfirst($key)],
                [$value, Str::upper($value), Str::ucfirst($value)],
                $line
            );
        }

        return $line;
    }

    /**
     * Sort the replacements array.
     *
     * 替换数组排序
     *
     * @param  array  $replace
     * @return array
     */
    protected function sortReplacements(array $replace)
    {
        //                                 使用给定的回调排序集合
        return (new Collection($replace))->sortBy(function ($value, $key) {
            return mb_strlen($key) * -1;
        })->all();//获取集合中的所有项目
    }

    /**
     * Add translation lines to the given locale.
     *
     * 在给定的语言环境中添加翻译行
     *
     * @param  array  $lines
     * @param  string  $locale
     * @param  string  $namespace
     * @return void
     */
    public function addLines(array $lines, $locale, $namespace = '*')
    {
        foreach ($lines as $key => $value) {
            list($group, $item) = explode('.', $key, 2);
            //如果没有给定key的方法，整个数组将被替换
            Arr::set($this->loaded, "$namespace.$group.$locale.$item", $value);
        }
    }

    /**
     * Load the specified language group.
     *
     * 加载指定的语言组
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return void
     */
    public function load($namespace, $group, $locale)
    {
        //确定是否已经加载了给定的组
        if ($this->isLoaded($namespace, $group, $locale)) {
            return;
        }

        // The loader is responsible for returning the array of language lines for the
        // given namespace, group, and locale. We'll set the lines in this array of
        // lines that have already been loaded so that we can easily access them.
        //
        // 加载器负责返回给定名称空间、组和场所的语言行数组
        // 我们将在已经加载的这些行中设置行，这样我们就可以很容易地访问它们
        //
        //               为给定的地区加载消息
        $lines = $this->loader->load($locale, $group, $namespace);

        $this->loaded[$namespace][$group][$locale] = $lines;
    }

    /**
     * Determine if the given group has been loaded.
     *
     * 确定是否已经加载了给定的组
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return bool
     */
    protected function isLoaded($namespace, $group, $locale)
    {
        return isset($this->loaded[$namespace][$group][$locale]);
    }

    /**
     * Add a new namespace to the loader.
     *
     * 向加载程序添加一个新的名称空间
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        //向加载程序添加一个新的名称空间
        $this->loader->addNamespace($namespace, $hint);
    }

    /**
     * Parse a key into namespace, group, and item.
     *
     * 将一个键解析为名称空间、组和项
     *
     * @param  string  $key
     * @return array
     */
    public function parseKey($key)
    {
        //将关键字解析为命名空间、组和项
        $segments = parent::parseKey($key);

        if (is_null($segments[0])) {
            $segments[0] = '*';
        }

        return $segments;
    }

    /**
     * Get the array of locales to be checked.
     *
     * 获取要检查的地区的数组
     *
     * @param  string|null  $locale
     * @return array
     */
    protected function localeArray($locale)
    {
        return array_filter([$locale ?: $this->locale, $this->fallback]);
    }

    /**
     * Get the message selector instance.
     *
     * 获取消息选择器实例
     *
     * @return \Illuminate\Translation\MessageSelector
     */
    public function getSelector()
    {
        if (! isset($this->selector)) {
            $this->selector = new MessageSelector;
        }

        return $this->selector;
    }

    /**
     * Set the message selector instance.
     *
     * 设置消息选择器实例
     *
     * @param  \Illuminate\Translation\MessageSelector  $selector
     * @return void
     */
    public function setSelector(MessageSelector $selector)
    {
        $this->selector = $selector;
    }

    /**
     * Get the language line loader implementation.
     *
     * 获取语言行加载器实现
     *
     * @return \Illuminate\Translation\LoaderInterface
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Get the default locale being used.
     *
     * 使用默认的语言环境
     *
     * @return string
     */
    public function locale()
    {
        return $this->getLocale();
    }

    /**
     * Get the default locale being used.
     *
     * 使用默认的语言环境
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the default locale.
     *
     * 设置默认语言环境
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get the fallback locale being used.
     *
     * 获取正在使用的回退区域
     *
     * @return string
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * Set the fallback locale being used.
     *
     * 设置使用的回退地区
     *
     * @param  string  $fallback
     * @return void
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;
    }
}
