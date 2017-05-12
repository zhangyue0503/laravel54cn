<?php

namespace Illuminate\Database\Eloquent;

use Closure;
use Faker\Generator as Faker;
use InvalidArgumentException;

class FactoryBuilder
{
    /**
     * The model definitions in the container.
     *
     * 容器中的模型定义
     *
     * @var array
     */
    protected $definitions;

    /**
     * The model being built.
     *
     * 正在建造的模型
     *
     * @var string
     */
    protected $class;

    /**
     * The name of the model being built.
     *
     * 正在构建的模型的名称
     *
     * @var string
     */
    protected $name = 'default';

    /**
     * The model states.
     *
     * 模型状态
     *
     * @var array
     */
    protected $states;

    /**
     * The states to apply.
     *
     * 申请的状态
     *
     * @var array
     */
    protected $activeStates = [];

    /**
     * The Faker instance for the builder.
     *
     * 构建器的Faker实例
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * The number of models to build.
     *
     * 构建模型的数量
     *
     * @var int|null
     */
    protected $amount = null;

    /**
     * Create an new builder instance.
     *
     * 创建一个新的构建器实例
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $definitions
     * @param  array  $states
     * @param  \Faker\Generator  $faker
     * @return void
     */
    public function __construct($class, $name, array $definitions, array $states, Faker $faker)
    {
        $this->name = $name;
        $this->class = $class;
        $this->faker = $faker;
        $this->states = $states;
        $this->definitions = $definitions;
    }

    /**
     * Set the amount of models you wish to create / make.
     *
     * 设置您希望创建的模型的数量
     *
     * @param  int  $amount
     * @return $this
     */
    public function times($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Set the states to be applied to the model.
     *
     * 将状态设置为应用于模型
     *
     * @param  array|dynamic  $states
     * @return $this
     */
    public function states($states)
    {
        $this->activeStates = is_array($states) ? $states : func_get_args();

        return $this;
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * 创建一个模型集合并将它们持久化到数据库中
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function create(array $attributes = [])
    {
        //                创建一个模型集合
        $results = $this->make($attributes);

        if ($results instanceof Model) {
            //将模型保存到数据库中
            $results->save();
        } else {
            $results->each->save();
        }

        return $results;
    }

    /**
     * Create a collection of models.
     *
     * 创建一个模型集合
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function make(array $attributes = [])
    {
        if ($this->amount === null) {
            //       用给定的属性创建模型的实例
            return $this->makeInstance($attributes);
        }

        if ($this->amount < 1) {
            return (new $this->class)->newCollection();
        }

        return (new $this->class)->newCollection(array_map(function () use ($attributes) {
            return $this->makeInstance($attributes);
        }, range(1, $this->amount)));
    }

    /**
     * Create an array of raw attribute arrays.
     *
     * 创建一个原始属性数组的数组
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function raw(array $attributes = [])
    {
        if ($this->amount === null) {
            //        获取模型的原始属性数组
            return $this->getRawAttributes($attributes);
        }

        if ($this->amount < 1) {
            return [];
        }

        return array_map(function () use ($attributes) {
            return $this->getRawAttributes($attributes);
        }, range(1, $this->amount));
    }

    /**
     * Get a raw attributes array for the model.
     *
     * 获取模型的原始属性数组
     *
     * @param  array  $attributes
     * @return mixed
     */
    protected function getRawAttributes(array $attributes = [])
    {
        $definition = call_user_func(
            $this->definitions[$this->class][$this->name],
            $this->faker, $attributes
        );
        //对属性数组中的任何闭包属性进行评估
        return $this->callClosureAttributes(
            //               将活动状态应用到模型定义数组中
            array_merge($this->applyStates($definition, $attributes), $attributes)
        );
    }

    /**
     * Make an instance of the model with the given attributes.
     *
     * 用给定的属性创建模型的实例
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \InvalidArgumentException
     */
    protected function makeInstance(array $attributes = [])
    {
        //         当无守护时运行给定调用
        return Model::unguarded(function () use ($attributes) {
            if (! isset($this->definitions[$this->class][$this->name])) {
                throw new InvalidArgumentException("Unable to locate factory with name [{$this->name}] [{$this->class}].");
            }

            return new $this->class(
                //     获取模型的原始属性数组
                $this->getRawAttributes($attributes)
            );
        });
    }

    /**
     * Apply the active states to the model definition array.
     *
     * 将活动状态应用到模型定义数组中
     *
     * @param  array  $definition
     * @param  array  $attributes
     * @return array
     */
    protected function applyStates(array $definition, array $attributes = [])
    {
        foreach ($this->activeStates as $state) {
            if (! isset($this->states[$this->class][$state])) {
                throw new InvalidArgumentException("Unable to locate [{$state}] state for [{$this->class}].");
            }

            $definition = array_merge($definition, call_user_func(
                $this->states[$this->class][$state],
                $this->faker, $attributes
            ));
        }

        return $definition;
    }

    /**
     * Evaluate any Closure attributes on the attribute array.
     *
     * 对属性数组中的任何闭包属性进行评估
     *
     * @param  array  $attributes
     * @return array
     */
    protected function callClosureAttributes(array $attributes)
    {
        foreach ($attributes as &$attribute) {
            $attribute = $attribute instanceof Closure
                            ? $attribute($attributes) : $attribute;
        }

        return $attributes;
    }
}
