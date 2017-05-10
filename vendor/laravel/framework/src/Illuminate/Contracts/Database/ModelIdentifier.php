<?php

namespace Illuminate\Contracts\Database;

class ModelIdentifier
{
    /**
     * The class name of the model.
     *
     * 模型的类名
     *
     * @var string
     */
    public $class;

    /**
     * The unique identifier of the model.
     *
     * 模型的唯一标识符
     *
     * This may be either a single ID or an array of IDs.
     *
     * 这可能是一个ID或一个ID数组
     *
     * @var mixed
     */
    public $id;

    /**
     * Create a new model identifier.
     *
     * 创建一个新的模型标识符
     *
     * @param  string  $class
     * @param  mixed  $id
     * @return void
     */
    public function __construct($class, $id)
    {
        $this->id = $id;
        $this->class = $class;
    }
}
