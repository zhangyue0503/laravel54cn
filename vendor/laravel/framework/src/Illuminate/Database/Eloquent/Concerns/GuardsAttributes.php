<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Str;

trait GuardsAttributes
{
    /**
     * The attributes that are mass assignable.
     *
     * 可分配的属性
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that aren't mass assignable.
     *
     * 这些属性不是可分配的
     *
     * @var array
     */
    protected $guarded = ['*'];

    /**
     * Indicates if all mass assignment is enabled.
     *
     * 指示是否启用所有批量赋值
     *
     * @var bool
     */
    protected static $unguarded = false;

    /**
     * Get the fillable attributes for the model.
     *
     * 为模型获取可以标记的属性
     *
     * @return array
     */
    public function getFillable()
    {
        return $this->fillable;
    }

    /**
     * Set the fillable attributes for the model.
     *
     * 为模型设置可以标记的属性
     *
     * @param  array  $fillable
     * @return $this
     */
    public function fillable(array $fillable)
    {
        $this->fillable = $fillable;

        return $this;
    }

    /**
     * Get the guarded attributes for the model.
     *
     * 获取模型的保护属性
     *
     * @return array
     */
    public function getGuarded()
    {
        return $this->guarded;
    }

    /**
     * Set the guarded attributes for the model.
     *
     * 为模型设置保护属性
     *
     * @param  array  $guarded
     * @return $this
     */
    public function guard(array $guarded)
    {
        $this->guarded = $guarded;

        return $this;
    }

    /**
     * Disable all mass assignable restrictions.
     *
     * 禁用所有批量赋值限制
     *
     * @param  bool  $state
     * @return void
     */
    public static function unguard($state = true)
    {
        static::$unguarded = $state;
    }

    /**
     * Enable the mass assignment restrictions.
     *
     * 启用质量分配限制
     *
     * @return void
     */
    public static function reguard()
    {
        static::$unguarded = false;
    }

    /**
     * Determine if current state is "unguarded".
     *
     * 确定当前状态是“未监护”
     *
     * @return bool
     */
    public static function isUnguarded()
    {
        return static::$unguarded;
    }

    /**
     * Run the given callable while being unguarded.
     *
     * 当无守护时运行给定调用
     *
     * @param  callable  $callback
     * @return mixed
     */
    public static function unguarded(callable $callback)
    {
        if (static::$unguarded) {
            return $callback();
        }
        //禁用所有批量赋值限制
        static::unguard();

        try {
            return $callback();
        } finally {
            //启用质量分配限制
            static::reguard();
        }
    }

    /**
     * Determine if the given attribute may be mass assigned.
     *
     * 确定给定的属性是否可以被批量赋值
     *
     * @param  string  $key
     * @return bool
     */
    public function isFillable($key)
    {
        if (static::$unguarded) {
            return true;
        }

        // If the key is in the "fillable" array, we can of course assume that it's
        // a fillable attribute. Otherwise, we will check the guarded array when
        // we need to determine if the attribute is black-listed on the model.
        //
        // 如果在“fillable”关键是数组,当然我们可以假设它是一个fillable属性
        // 否则，当需要确定该属性是否在模型中被黑时，我们将检查守护数组
        //
        //                      为模型获取可以标记的属性
        if (in_array($key, $this->getFillable())) {
            return true;
        }

        // If the attribute is explicitly listed in the "guarded" array then we can
        // return false immediately. This means this attribute is definitely not
        // fillable and there is no point in going any further in this method.
        //
        // 如果属性被显式地列在“守护”数组中，那么我们就可以立即返回false
        // 这意味着这个属性绝对不能被填上，并且在这个方法中没有任何意义
        //
        //      确定给定的键是否被保护
        if ($this->isGuarded($key)) {
            return false;
        }
        //                  为模型获取可以标记的属性
        return empty($this->getFillable()) &&
            ! Str::startsWith($key, '_');//确定给定的子字符串是否属于给定的字符串
    }

    /**
     * Determine if the given key is guarded.
     *
     * 确定给定的键是否被保护
     *
     * @param  string  $key
     * @return bool
     */
    public function isGuarded($key)
    {
        //                      获取模型的保护属性
        return in_array($key, $this->getGuarded()) || $this->getGuarded() == ['*'];
    }

    /**
     * Determine if the model is totally guarded.
     *
     * 确定模型是否完全保护
     *
     * @return bool
     */
    public function totallyGuarded()
    {
        //            为模型获取可以标记的属性               获取模型的保护属性
        return count($this->getFillable()) == 0 && $this->getGuarded() == ['*'];
    }

    /**
     * Get the fillable attributes of a given array.
     *
     * 获取给定数组的填充属性
     *
     * @param  array  $attributes
     * @return array
     */
    protected function fillableFromArray(array $attributes)
    {
        //为模型获取可以标记的属性
        if (count($this->getFillable()) > 0 && ! static::$unguarded) {
            return array_intersect_key($attributes, array_flip($this->getFillable()));
        }

        return $attributes;
    }
}
