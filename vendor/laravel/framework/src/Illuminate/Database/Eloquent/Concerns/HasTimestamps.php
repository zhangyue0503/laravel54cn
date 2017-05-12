<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Carbon\Carbon;

trait HasTimestamps
{
    /**
     * Indicates if the model should be timestamped.
     *
     * 指示该模型是否应该被时间戳
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Update the model's update timestamp.
     *
     * 更新模型的更新时间戳
     *
     * @return bool
     */
    public function touch()
    {
        //确定模型使用时间戳
        if (! $this->usesTimestamps()) {
            return false;
        }
        //更新的创建和更新时间戳
        $this->updateTimestamps();
        // 将模型保存到数据库中
        return $this->save();
    }

    /**
     * Update the creation and update timestamps.
     *
     * 更新的创建和更新时间戳
     *
     * @return void
     */
    protected function updateTimestamps()
    {
        //为模型获取一个新的时间戳
        $time = $this->freshTimestamp();
        //确定模型或特定的属性是否已经修改
        if (! $this->isDirty(static::UPDATED_AT)) {
            $this->setUpdatedAt($time);//设置“更新at”属性的值
        }

        if (! $this->exists && ! $this->isDirty(static::CREATED_AT)) {
            //设置“创建at”属性的值
            $this->setCreatedAt($time);
        }
    }

    /**
     * Set the value of the "created at" attribute.
     *
     * 设置“创建at”属性的值
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setCreatedAt($value)
    {
        $this->{static::CREATED_AT} = $value;

        return $this;
    }

    /**
     * Set the value of the "updated at" attribute.
     *
     * 设置“更新at”属性的值
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setUpdatedAt($value)
    {
        $this->{static::UPDATED_AT} = $value;

        return $this;
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * 为模型获取一个新的时间戳
     *
     * @return \Carbon\Carbon
     */
    public function freshTimestamp()
    {
        return new Carbon;
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * 为模型获取新的时间戳
     *
     * @return string
     */
    public function freshTimestampString()
    {
        //DateTime转换为存储字符串(为模型获取一个新的时间戳)
        return $this->fromDateTime($this->freshTimestamp());
    }

    /**
     * Determine if the model uses timestamps.
     *
     * 确定模型使用时间戳
     *
     * @return bool
     */
    public function usesTimestamps()
    {
        return $this->timestamps;
    }

    /**
     * Get the name of the "created at" column.
     *
     * 获取“created at”列的名称
     *
     * @return string
     */
    public function getCreatedAtColumn()
    {
        return static::CREATED_AT;
    }

    /**
     * Get the name of the "updated at" column.
     *
     * 获取“updated at”列的名称
     *
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        return static::UPDATED_AT;
    }
}
