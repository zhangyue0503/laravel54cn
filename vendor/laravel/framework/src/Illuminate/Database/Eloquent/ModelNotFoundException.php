<?php

namespace Illuminate\Database\Eloquent;

use RuntimeException;

class ModelNotFoundException extends RuntimeException
{
    /**
     * Name of the affected Eloquent model.
     *
     * 受影响的Eloquent模型的名称
     *
     * @var string
     */
    protected $model;

    /**
     * The affected model IDs.
     *
     * 受影响的模型id
     *
     * @var int|array
     */
    protected $ids;

    /**
     * Set the affected Eloquent model and instance ids.
     *
     * 设置受影响的Eloquent型和实例ids
     *
     * @param  string  $model
     * @param  int|array  $ids
     * @return $this
     */
    public function setModel($model, $ids = [])
    {
        $this->model = $model;
        //             如果给定值不是数组，请将其包在一个数组中
        $this->ids = array_wrap($ids);

        $this->message = "No query results for model [{$model}]";

        if (count($this->ids) > 0) {
            $this->message .= ' '.implode(', ', $this->ids);
        } else {
            $this->message .= '.';
        }

        return $this;
    }

    /**
     * Get the affected Eloquent model.
     *
     * 获得受影响的Eloquent模型
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the affected Eloquent model IDs.
     *
     * 获取受影响的Eloquent模型id
     *
     * @return int|array
     */
    public function getIds()
    {
        return $this->ids;
    }
}
