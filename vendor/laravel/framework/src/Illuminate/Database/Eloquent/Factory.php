<?php

namespace Illuminate\Database\Eloquent;

use ArrayAccess;
use Faker\Generator as Faker;
use Symfony\Component\Finder\Finder;

class Factory implements ArrayAccess
{
    /**
     * The model definitions in the container.
     *
     * 容器中的模型定义
     *
     * @var array
     */
    protected $definitions = [];

    /**
     * The registered model states.
     *
     * 已注册的模型状态
     *
     * @var array
     */
    protected $states = [];

    /**
     * The Faker instance for the builder.
     *
     * 构建器的Faker实例
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Create a new factory instance.
     *
     * 创建一个新的工厂实例
     *
     * @param  \Faker\Generator  $faker
     * @return void
     */
    public function __construct(Faker $faker)
    {
        $this->faker = $faker;
    }

    /**
     * Create a new factory container.
     *
     * 创建一个新的工厂容器
     *
     * @param  \Faker\Generator  $faker
     * @param  string|null  $pathToFactories
     * @return static
     */
    public static function construct(Faker $faker, $pathToFactories = null)
    {
        //                                          获取数据库路径
        $pathToFactories = $pathToFactories ?: database_path('factories');
        //                        从路径上加载工厂
        return (new static($faker))->load($pathToFactories);
    }

    /**
     * Define a class with a given short-name.
     *
     * 定义一个具有给定短名称的类
     *
     * @param  string  $class
     * @param  string  $name
     * @param  callable  $attributes
     * @return $this
     */
    public function defineAs($class, $name, callable $attributes)
    {
        //定义具有给定属性集的类
        return $this->define($class, $attributes, $name);
    }

    /**
     * Define a class with a given set of attributes.
     *
     * 定义具有给定属性集的类
     *
     * @param  string  $class
     * @param  callable  $attributes
     * @param  string  $name
     * @return $this
     */
    public function define($class, callable $attributes, $name = 'default')
    {
        $this->definitions[$class][$name] = $attributes;

        return $this;
    }

    /**
     * Define a state with a given set of attributes.
     *
     * 定义具有给定属性集的状态
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable  $attributes
     * @return $this
     */
    public function state($class, $state, callable $attributes)
    {
        $this->states[$class][$state] = $attributes;

        return $this;
    }

    /**
     * Create an instance of the given model and persist it to the database.
     *
     * 创建给定模型的实例并将其持久化到数据库中
     *
     * @param  string  $class
     * @param  array  $attributes
     * @return mixed
     */
    public function create($class, array $attributes = [])
    {
        //   为给定的模型创建生成器   创建一个模型集合并将它们持久化到数据库中
        return $this->of($class)->create($attributes);
    }

    /**
     * Create an instance of the given model and type and persist it to the database.
     *
     * 创建给定模型和类型的实例并将其持久化到数据库中
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $attributes
     * @return mixed
     */
    public function createAs($class, $name, array $attributes = [])
    {
        //   为给定的模型创建生成器         创建一个模型集合并将它们持久化到数据库中
        return $this->of($class, $name)->create($attributes);
    }

    /**
     * Create an instance of the given model.
     *
     * 创建给定模型的实例
     *
     * @param  string  $class
     * @param  array  $attributes
     * @return mixed
     */
    public function make($class, array $attributes = [])
    {
        //为给定的模型创建生成器     创建一个模型集合
        return $this->of($class)->make($attributes);
    }

    /**
     * Create an instance of the given model and type.
     *
     * 创建给定模型和类型的实例
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $attributes
     * @return mixed
     */
    public function makeAs($class, $name, array $attributes = [])
    {
        //为给定的模型创建生成器            创建一个模型集合
        return $this->of($class, $name)->make($attributes);
    }

    /**
     * Get the raw attribute array for a given named model.
     *
     * 获取给定命名模型的原始属性数组
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $attributes
     * @return array
     */
    public function rawOf($class, $name, array $attributes = [])
    {
        //获取给定模型的原始属性数组
        return $this->raw($class, $attributes, $name);
    }

    /**
     * Get the raw attribute array for a given model.
     *
     * 获取给定模型的原始属性数组
     *
     * @param  string  $class
     * @param  array  $attributes
     * @param  string  $name
     * @return array
     */
    public function raw($class, array $attributes = [], $name = 'default')
    {
        return array_merge(
            call_user_func($this->definitions[$class][$name], $this->faker), $attributes
        );
    }

    /**
     * Create a builder for the given model.
     *
     * 为给定的模型创建生成器
     *
     * @param  string  $class
     * @param  string  $name
     * @return \Illuminate\Database\Eloquent\FactoryBuilder
     */
    public function of($class, $name = 'default')
    {
        //创建一个新的构建器实例
        return new FactoryBuilder($class, $name, $this->definitions, $this->states, $this->faker);
    }

    /**
     * Load factories from path.
     *
     * 从路径上加载工厂
     *
     * @param  string  $path
     * @return $this
     */
    public function load($path)
    {
        $factory = $this;

        if (is_dir($path)) {
            //创建一个新的查找器      仅将匹配限制为文件  搜索符合定义规则的文件和目录
            foreach (Finder::create()->files()->in($path) as $file) {
                require $file->getRealPath();
            }
        }

        return $factory;
    }

    /**
     * Determine if the given offset exists.
     *
     * 确定给定的偏移量是否存在
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->definitions[$offset]);
    }

    /**
     * Get the value of the given offset.
     *
     * 得到给定偏移量的值
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        //        创建给定模型的实例
        return $this->make($offset);
    }

    /**
     * Set the given offset to the given value.
     *
     * 将给定的偏移量设置为给定的值
     *
     * @param  string  $offset
     * @param  callable  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        //         定义具有给定属性集的类
        return $this->define($offset, $value);
    }

    /**
     * Unset the value at the given offset.
     *
     * 在给定的偏移量上设置值
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->definitions[$offset]);
    }
}
