<?php

namespace Illuminate\Http\Concerns;
//与Flash数据交互
trait InteractsWithFlashData
{
    /**
     * Retrieve an old input item.
     *
     * 检索旧输入项
     *
     * @param  string  $key
     * @param  string|array|null  $default
     * @return string|array
     */
    public function old($key = null, $default = null)
    {
        //获取与请求关联的会话          从闪存的输入数组中获取所请求的项
        return $this->session()->getOldInput($key, $default);
    }

    /**
     * Flash the input for the current request to the session.
     *
     * 将当前请求输入到会话中
     *
     * @return void
     */
    public function flash()
    {
        //获取与请求关联的会话  在会话中输入一个输入数组（从请求中检索输入项）
        $this->session()->flashInput($this->input());
    }

    /**
     * Flash only some of the input to the session.
     *
     * 只有一些Flash输入到会话中
     *
     * @param  array|mixed  $keys
     * @return void
     */
    public function flashOnly($keys)
    {
        //获取与请求关联的会话  在会话中输入一个输入数组
        $this->session()->flashInput(
            //获取包含来自输入数据的值的所提供键的子集
            $this->only(is_array($keys) ? $keys : func_get_args())
        );
    }

    /**
     * Flash only some of the input to the session.
     *
     * 只有一些Flash输入到会话中
     *
     * @param  array|mixed  $keys
     * @return void
     */
    public function flashExcept($keys)
    {
        //获取与请求关联的会话  在会话中输入一个输入数组
        $this->session()->flashInput(
            //获取除指定数组项之外的所有输入
            $this->except(is_array($keys) ? $keys : func_get_args())
        );
    }

    /**
     * Flush all of the old input from the session.
     *
     * 刷新会话中的所有旧输入
     *
     * @return void
     */
    public function flush()
    {
        //获取与请求关联的会话  在会话中输入一个输入数组
        $this->session()->flashInput([]);
    }
}
