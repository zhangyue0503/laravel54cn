<?php

namespace Illuminate\Database\Eloquent\Concerns;

trait HidesAttributes
{
    /**
     * The attributes that should be hidden for serialization.
     *
     * 应该隐藏用于序列化的属性
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be visible in serialization.
     *
     * 在序列化中应该可见的属性
     *
     * @var array
     */
    protected $visible = [];

    /**
     * Get the hidden attributes for the model.
     *
     * 获取模型的隐藏属性
     *
     * @return array
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the hidden attributes for the model.
     *
     * 设置模型的隐藏属性
     *
     * @param  array  $hidden
     * @return $this
     */
    public function setHidden(array $hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Add hidden attributes for the model.
     *
     * 为模型添加隐藏属性
     *
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addHidden($attributes = null)
    {
        $this->hidden = array_merge(
            $this->hidden, is_array($attributes) ? $attributes : func_get_args()
        );
    }

    /**
     * Get the visible attributes for the model.
     *
     * 获取模型的可见属性
     *
     * @return array
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set the visible attributes for the model.
     *
     * 为模型设置可见的属性
     *
     * @param  array  $visible
     * @return $this
     */
    public function setVisible(array $visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Add visible attributes for the model.
     *
     * 为模型添加可见的属性
     *
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addVisible($attributes = null)
    {
        $this->visible = array_merge(
            $this->visible, is_array($attributes) ? $attributes : func_get_args()
        );
    }

    /**
     * Make the given, typically hidden, attributes visible.
     *
     * 让给定的，通常隐藏的属性可见
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function makeVisible($attributes)
    {
        $this->hidden = array_diff($this->hidden, (array) $attributes);

        if (! empty($this->visible)) {
            $this->addVisible($attributes);//为模型添加可见的属性
        }

        return $this;
    }

    /**
     * Make the given, typically visible, attributes hidden.
     *
     * 使给定的、通常可见的属性隐藏起来
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function makeHidden($attributes)
    {
        $attributes = (array) $attributes;

        $this->visible = array_diff($this->visible, $attributes);

        $this->hidden = array_unique(array_merge($this->hidden, $attributes));

        return $this;
    }
}
