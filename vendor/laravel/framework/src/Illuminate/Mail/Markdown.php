<?php

namespace Illuminate\Mail;

use Parsedown;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\Factory as ViewFactory;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class Markdown
{
    /**
     * The view factory implementation.
     *
     * 视图工厂实现
     *
     * @var \Illuminate\View\Factory
     */
    protected $view;

    /**
     * The current theme being used when generating emails.
     *
     * 当前的主题是在生成电子邮件时使用
     *
     * @var string
     */
    protected $theme = 'default';

    /**
     * The registered component paths.
     *
     * 注册组件的路径
     *
     * @var array
     */
    protected $componentPaths = [];

    /**
     * Create a new Markdown renderer instance.
     *
     * 创建一个新的Markdown渲染实例
     *
     * @param  \Illuminate\View\Factory  $view
     * @param  array  $options
     * @return void
     */
    public function __construct(ViewFactory $view, array $options = [])
    {
        $this->view = $view;
        //           使用“点”符号从数组中获取一个项
        $this->theme = Arr::get($options, 'theme', 'default');
        //      注册新的邮件组件路径      使用“点”符号从数组中获取一个项
        $this->loadComponentsFrom(Arr::get($options, 'paths', []));
    }

    /**
     * Render the Markdown template into HTML.
     *
     * 将Markdown模板呈现为HTML
     *
     * @param  string  $view
     * @param  array  $data
     * @param  \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles|null  $inliner
     * @return \Illuminate\Support\HtmlString
     */
    public function render($view, array $data = [], $inliner = null)
    {
        //            刷新查找器所在的视图的缓存
        $this->view->flushFinderCache();
        //替换给定名称空间的名称空间提示
        $contents = $this->view->replaceNamespace(
            'mail', $this->htmlComponentPaths()//获取HTML组件路径
        )->make($view, $data)->render();//获取给定视图的评估视图内容->获取对象的评估内容

        return new HtmlString(with($inliner ?: new CssToInlineStyles)->convert(
            //              获取给定视图的评估视图内容                    获取对象的评估内容
            $contents, $this->view->make('mail::themes.'.$this->theme)->render()
        ));
    }

    /**
     * Render the Markdown template into HTML.
     *
     * 将Markdown模板呈现为HTML
     *
     * @param  string  $view
     * @param  array  $data
     * @return \Illuminate\Support\HtmlString
     */
    public function renderText($view, array $data = [])
    {
        //            刷新查找器所在的视图的缓存
        $this->view->flushFinderCache();
        //     创建一个新的HTML字符串实例                                           替换给定名称空间的名称空间提示
        return new HtmlString(preg_replace("/[\r\n]{2,}/", "\n\n", $this->view->replaceNamespace(
            'mail', $this->markdownComponentPaths()
        )->make($view, $data)->render()));//获取给定视图的评估视图内容获取对象的评估内容->
    }

    /**
     * Parse the given Markdown text into HTML.
     *
     * 将给定的Markdown文本解析为HTML
     *
     * @param  string  $text
     * @return string
     */
    public static function parse($text)
    {
        $parsedown = new Parsedown;

        return new HtmlString($parsedown->text($text));
    }

    /**
     * Get the HTML component paths.
     *
     * 获取HTML组件路径
     *
     * @return array
     */
    public function htmlComponentPaths()
    {
        return array_map(function ($path) {
            return $path.'/html';
        }, $this->componentPaths());//获取组件的路径
    }

    /**
     * Get the Markdown component paths.
     *
     * 获取Markdown组件路径
     *
     * @return array
     */
    public function markdownComponentPaths()
    {
        return array_map(function ($path) {
            return $path.'/markdown';
        }, $this->componentPaths());//获取组件的路径
    }

    /**
     * Get the component paths.
     *
     * 获取组件的路径
     *
     * @return array
     */
    protected function componentPaths()
    {
        return array_unique(array_merge($this->componentPaths, [
            __DIR__.'/resources/views',
        ]));
    }

    /**
     * Register new mail component paths.
     *
     * 注册新的邮件组件路径
     *
     * @param  array  $paths
     * @return void
     */
    public function loadComponentsFrom(array $paths = [])
    {
        $this->componentPaths = $paths;
    }
}
