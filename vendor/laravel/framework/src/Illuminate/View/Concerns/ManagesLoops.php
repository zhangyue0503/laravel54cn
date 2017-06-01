<?php

namespace Illuminate\View\Concerns;

use Countable;
use Illuminate\Support\Arr;

trait ManagesLoops
{
    /**
     * The stack of in-progress loops.
     *
     * 正在进行中的循环
     *
     * @var array
     */
    protected $loopsStack = [];

    /**
     * Add new loop to the stack.
     *
     * 在堆栈中添加新的循环
     *
     * @param  \Countable|array  $data
     * @return void
     */
    public function addLoop($data)
    {
        $length = is_array($data) || $data instanceof Countable ? count($data) : null;
        //返回经过给定的真值测试的数组中的最后一个元素
        $parent = Arr::last($this->loopsStack);

        $this->loopsStack[] = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => isset($length) ? $length : null,
            'count' => $length,
            'first' => true,
            'last' => isset($length) ? $length == 1 : null,
            'depth' => count($this->loopsStack) + 1,
            'parent' => $parent ? (object) $parent : null,
        ];
    }

    /**
     * Increment the top loop's indices.
     *
     * 增加顶部循环的索引
     *
     * @return void
     */
    public function incrementLoopIndices()
    {
        $loop = $this->loopsStack[$index = count($this->loopsStack) - 1];

        $this->loopsStack[$index] = array_merge($this->loopsStack[$index], [
            'iteration' => $loop['iteration'] + 1,
            'index' => $loop['iteration'],
            'first' => $loop['iteration'] == 0,
            'remaining' => isset($loop['count']) ? $loop['remaining'] - 1 : null,
            'last' => isset($loop['count']) ? $loop['iteration'] == $loop['count'] - 1 : null,
        ]);
    }

    /**
     * Pop a loop from the top of the loop stack.
     *
     * 从循环堆栈顶部弹出一个循环
     *
     * @return void
     */
    public function popLoop()
    {
        array_pop($this->loopsStack);
    }

    /**
     * Get an instance of the last loop in the stack.
     *
     * 获取堆栈中最后一个循环的实例
     *
     * @return \StdClass|null
     */
    public function getLastLoop()
    {
        //返回经过给定的真值测试的数组中的最后一个元素
        if ($last = Arr::last($this->loopsStack)) {
            return (object) $last;
        }
    }

    /**
     * Get the entire loop stack.
     *
     * 获取整个循环堆栈
     *
     * @return array
     */
    public function getLoopStack()
    {
        return $this->loopsStack;
    }
}
