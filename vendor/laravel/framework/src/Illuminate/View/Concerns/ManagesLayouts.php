<?php

namespace Illuminate\View\Concerns;

use InvalidArgumentException;

trait ManagesLayouts
{
    /**
     * All of the finished, captured sections.
     *
     * 所有完成的，捕获的部分
     *
     * @var array
     */
    protected $sections = [];

    /**
     * The stack of in-progress sections.
     *
     * 正在进行中的一组
     *
     * @var array
     */
    protected $sectionStack = [];

    /**
     * The parent placeholder for the request.
     *
     * 请求的父占位符
     *
     * @var string
     */
    protected static $parentPlaceholder = [];

    /**
     * Start injecting content into a section.
     *
     * 开始向一个部分中注入内容
     *
     * @param  string  $section
     * @param  string|null  $content
     * @return void
     */
    public function startSection($section, $content = null)
    {
        if ($content === null) {
            if (ob_start()) {
                $this->sectionStack[] = $section;
            }
        } else {
            //将内容附加到给定的部分
            $this->extendSection($section, e($content));
        }
    }

    /**
     * Inject inline content into a section.
     *
     * 将内联内容注入到一个部分中
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function inject($section, $content)
    {
        //开始向一个部分中注入内容
        return $this->startSection($section, $content);
    }

    /**
     * Stop injecting content into a section and return its contents.
     *
     * 停止向一个部分中注入内容并返回其内容
     *
     * @return string
     */
    public function yieldSection()
    {
        if (empty($this->sectionStack)) {
            return '';
        }
        //获取一个分段的字符串内容            停止向一节中注入内容
        return $this->yieldContent($this->stopSection());
    }

    /**
     * Stop injecting content into a section.
     *
     * 停止向一节中注入内容
     *
     * @param  bool  $overwrite
     * @return string
     * @throws \InvalidArgumentException
     */
    public function stopSection($overwrite = false)
    {
        if (empty($this->sectionStack)) {
            throw new InvalidArgumentException('Cannot end a section without first starting one.');
        }

        $last = array_pop($this->sectionStack);

        if ($overwrite) {
            $this->sections[$last] = ob_get_clean();
        } else {
            // 将内容附加到给定的部分
            $this->extendSection($last, ob_get_clean());
        }

        return $last;
    }

    /**
     * Stop injecting content into a section and append it.
     *
     * 停止向一个部分注入内容并附加它
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function appendSection()
    {
        if (empty($this->sectionStack)) {
            throw new InvalidArgumentException('Cannot end a section without first starting one.');
        }

        $last = array_pop($this->sectionStack);

        if (isset($this->sections[$last])) {
            $this->sections[$last] .= ob_get_clean();
        } else {
            $this->sections[$last] = ob_get_clean();
        }

        return $last;
    }

    /**
     * Append content to a given section.
     *
     * 将内容附加到给定的部分
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    protected function extendSection($section, $content)
    {
        if (isset($this->sections[$section])) {
            //                     获取当前请求的父占位符
            $content = str_replace(static::parentPlaceholder($section), $content, $this->sections[$section]);
        }

        $this->sections[$section] = $content;
    }

    /**
     * Get the string contents of a section.
     *
     * 获取一个分段的字符串内容
     *
     * @param  string  $section
     * @param  string  $default
     * @return string
     */
    public function yieldContent($section, $default = '')
    {
        $sectionContent = $default;

        if (isset($this->sections[$section])) {
            $sectionContent = $this->sections[$section];
        }

        $sectionContent = str_replace('@@parent', '--parent--holder--', $sectionContent);

        return str_replace(
            //                                      获取当前请求的父占位符
            '--parent--holder--', '@parent', str_replace(static::parentPlaceholder($section), '', $sectionContent)
        );
    }

    /**
     * Get the parent placeholder for the current request.
     *
     * 获取当前请求的父占位符
     *
     * @param  string  $section
     * @return string
     */
    public static function parentPlaceholder($section = '')
    {
        if (! isset(static::$parentPlaceholder[$section])) {
            static::$parentPlaceholder[$section] = '##parent-placeholder-'.sha1($section).'##';
        }

        return static::$parentPlaceholder[$section];
    }

    /**
     * Check if section exists.
     *
     * 检查是否存在部分
     *
     * @param  string  $name
     * @return bool
     */
    public function hasSection($name)
    {
        return array_key_exists($name, $this->sections);
    }

    /**
     * Get the entire array of sections.
     *
     * 获取全部的分段
     *
     * @return array
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * Flush all of the sections.
     *
     * 刷新所有的部分
     *
     * @return void
     */
    public function flushSections()
    {
        $this->sections = [];
        $this->sectionStack = [];
    }
}
