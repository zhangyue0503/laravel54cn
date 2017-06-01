<?php

namespace Illuminate\View\Concerns;

use InvalidArgumentException;

trait ManagesStacks
{
    /**
     * All of the finished, captured push sections.
     *
     * 所有已完成的，被捕获的推进部分
     *
     * @var array
     */
    protected $pushes = [];

    /**
     * All of the finished, captured prepend sections.
     *
     * 所有完成的，捕获的预部分
     *
     * @var array
     */
    protected $prepends = [];

    /**
     * The stack of in-progress push sections.
     *
     * 正在推进的推挤部分
     *
     * @var array
     */
    protected $pushStack = [];

    /**
     * Start injecting content into a push section.
     *
     * 开始将内容注入到一个推送的部分
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function startPush($section, $content = '')
    {
        if ($content === '') {
            if (ob_start()) {
                $this->pushStack[] = $section;
            }
        } else {
            //将内容附加到给定的推部分
            $this->extendPush($section, $content);
        }
    }

    /**
     * Stop injecting content into a push section.
     *
     * 停止向推入部分注入内容
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function stopPush()
    {
        if (empty($this->pushStack)) {
            throw new InvalidArgumentException('Cannot end a push stack without first starting one.');
        }
        //用给定的值调用给定的闭包，然后返回值
        return tap(array_pop($this->pushStack), function ($last) {
            //将内容附加到给定的推部分
            $this->extendPush($last, ob_get_clean());
        });
    }

    /**
     * Append content to a given push section.
     *
     * 将内容附加到给定的推部分
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    protected function extendPush($section, $content)
    {
        if (! isset($this->pushes[$section])) {
            $this->pushes[$section] = [];
        }

        if (! isset($this->pushes[$section][$this->renderCount])) {
            $this->pushes[$section][$this->renderCount] = $content;
        } else {
            $this->pushes[$section][$this->renderCount] .= $content;
        }
    }

    /**
     * Start prepending content into a push section.
     *
     * 在一个推的部分开始预挂内容
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function startPrepend($section, $content = '')
    {
        if ($content === '') {
            if (ob_start()) {
                $this->pushStack[] = $section;
            }
        } else {
            //预先对给定堆栈的内容进行预处理
            $this->extendPrepend($section, $content);
        }
    }

    /**
     * Stop prepending content into a push section.
     *
     * 停止预待内容到一个推区
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function stopPrepend()
    {
        if (empty($this->pushStack)) {
            throw new InvalidArgumentException('Cannot end a prepend operation without first starting one.');
        }
        //用给定的值调用给定的闭包，然后返回值
        return tap(array_pop($this->pushStack), function ($last) {
            //预先对给定堆栈的内容进行预处理
            $this->extendPrepend($last, ob_get_clean());
        });
    }

    /**
     * Prepend content to a given stack.
     *
     * 预先对给定堆栈的内容进行预处理
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    protected function extendPrepend($section, $content)
    {
        if (! isset($this->prepends[$section])) {
            $this->prepends[$section] = [];
        }

        if (! isset($this->prepends[$section][$this->renderCount])) {
            $this->prepends[$section][$this->renderCount] = $content;
        } else {
            $this->prepends[$section][$this->renderCount] = $content.$this->prepends[$section][$this->renderCount];
        }
    }

    /**
     * Get the string contents of a push section.
     *
     * 获取一个push部分的字符串内容
     *
     * @param  string  $section
     * @param  string  $default
     * @return string
     */
    public function yieldPushContent($section, $default = '')
    {
        if (! isset($this->pushes[$section]) && ! isset($this->prepends[$section])) {
            return $default;
        }

        $output = '';

        if (isset($this->prepends[$section])) {
            $output .= implode(array_reverse($this->prepends[$section]));
        }

        if (isset($this->pushes[$section])) {
            $output .= implode($this->pushes[$section]);
        }

        return $output;
    }

    /**
     * Flush all of the stacks.
     *
     * 把所有的堆栈都刷新
     *
     * @return void
     */
    public function flushStacks()
    {
        $this->pushes = [];
        $this->prepends = [];
        $this->pushStack = [];
    }
}
