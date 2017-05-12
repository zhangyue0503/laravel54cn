<?php

namespace Illuminate\Database\Eloquent;

use LogicException;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Queue\QueueableCollection;
use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection implements QueueableCollection
{
    /**
     * Find a model in the collection by key.
     *
     * 在集合中找到一个模型
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function find($key, $default = null)
    {
        if ($key instanceof Model) {
            //           获取模型主键的值
            $key = $key->getKey();
        }

        if (is_array($key)) {
            //确定集合是否为空
            if ($this->isEmpty()) {
                return new static;
            }
            //按给定键值对筛选项目       从集合中获取第一项
            return $this->whereIn($this->first()->getKeyName(), $key);
        }
        //通过给定的真值测试返回数组中的第一个元素
        return Arr::first($this->items, function ($model) use ($key) {
            return $model->getKey() == $key;
        }, $default);
    }

    /**
     * Load a set of relationships onto the collection.
     *
     * 将一组关系加载到集合中
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function load($relations)
    {
        if (count($this->items) > 0) {
            if (is_string($relations)) {
                $relations = func_get_args();
            }
            //从集合中获取第一项       获取查询生成器的新实例  设置应该加载的关系
            $query = $this->first()->newQuery()->with($relations);
            //贪婪加载的关系模型
            $this->items = $query->eagerLoadRelations($this->items);
        }

        return $this;
    }

    /**
     * Add an item to the collection.
     *
     * 向集合中添加一个项目
     *
     * @param  mixed  $item
     * @return $this
     */
    public function add($item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Determine if a key exists in the collection.
     *
     * 确定集合中是否存在一个键
     *
     * @param  mixed  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        //                             确定给定值是否可调用，但不是字符串
        if (func_num_args() > 1 || $this->useAsCallable($key)) {
            //确定集合中是否存在项
            return parent::contains(...func_get_args());
        }
        //                              获取模型主键的值
        $key = $key instanceof Model ? $key->getKey() : $key;
        //确定集合中是否存在项
        return parent::contains(function ($model) use ($key) {
            return $model->getKey() == $key;
        });
    }

    /**
     * Get the array of primary keys.
     *
     * 获取主键的数组
     *
     * @return array
     */
    public function modelKeys()
    {
        return array_map(function ($model) {
            return $model->getKey();
        }, $this->items);
    }

    /**
     * Merge the collection with the given items.
     *
     * 将集合与给定的项合并
     *
     * @param  \ArrayAccess|array  $items
     * @return static
     */
    public function merge($items)
    {
        //                 用主键键入字典
        $dictionary = $this->getDictionary();

        foreach ($items as $item) {
            $dictionary[$item->getKey()] = $item;
        }

        return new static(array_values($dictionary));
    }

    /**
     * Run a map over each of the items.
     *
     * 在每个项目上运行一个映射
     *
     * @param  callable  $callback
     * @return \Illuminate\Support\Collection
     */
    public function map(callable $callback)
    {
        //在每个项目上运行map
        $result = parent::map($callback);
        //确定集合中是否存在项
        return $result->contains(function ($item) {
            return ! $item instanceof Model;
            //    从集合中获取基础支持集合实例
        }) ? $result->toBase() : $result;
    }

    /**
     * Diff the collection with the given items.
     *
     * 将集合与给定的项进行比较
     *
     * @param  \ArrayAccess|array  $items
     * @return static
     */
    public function diff($items)
    {
        $diff = new static;
        //             用主键键入字典
        $dictionary = $this->getDictionary($items);

        foreach ($this->items as $item) {
            if (! isset($dictionary[$item->getKey()])) {
                //向集合中添加一个项目
                $diff->add($item);
            }
        }

        return $diff;
    }

    /**
     * Intersect the collection with the given items.
     *
     * 与给定项目的集合相交
     *
     * @param  \ArrayAccess|array  $items
     * @return static
     */
    public function intersect($items)
    {
        $intersect = new static;
        //             用主键键入字典
        $dictionary = $this->getDictionary($items);

        foreach ($this->items as $item) {
            if (isset($dictionary[$item->getKey()])) {
                //向集合中添加一个项目
                $intersect->add($item);
            }
        }

        return $intersect;
    }

    /**
     * Return only unique items from the collection.
     *
     * 从集合中返回唯一的项
     *
     * @param  string|callable|null  $key
     * @param  bool  $strict
     * @return static|\Illuminate\Support\Collection
     */
    public function unique($key = null, $strict = false)
    {
        if (! is_null($key)) {
            //只返回集合数组中的唯一项
            return parent::unique($key, $strict);
        }
        //                                用主键键入字典
        return new static(array_values($this->getDictionary()));
    }

    /**
     * Returns only the models from the collection with the specified keys.
     *
     * 只返回带有指定键的集合中的模型
     *
     * @param  mixed  $keys
     * @return static
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            return new static($this->items);
        }
        //               从给定数组中获取项目的子集  用主键键入字典
        $dictionary = Arr::only($this->getDictionary(), $keys);

        return new static(array_values($dictionary));
    }

    /**
     * Returns all models in the collection except the models with specified keys.
     *
     * 返回集合中的所有模型，除了带有指定键的模型
     *
     * @param  mixed  $keys
     * @return static
     */
    public function except($keys)
    {
        //         获取指定数组，除了指定的数组项(用主键键入字典,)
        $dictionary = Arr::except($this->getDictionary(), $keys);

        return new static(array_values($dictionary));
    }

    /**
     * Make the given, typically visible, attributes hidden across the entire collection.
     *
     * 让给定的，通常可见的，隐藏在整个集合中的属性
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function makeHidden($attributes)
    {
        //在每个项目上执行回调
        return $this->each(function ($model) use ($attributes) {
            //为模型添加隐藏属性
            $model->addHidden($attributes);
        });
    }

    /**
     * Make the given, typically hidden, attributes visible across the entire collection.
     *
     * 让给定的、通常隐藏的属性在整个集合中可见
     *
     * @param  array|string  $attributes
     * @return $this
     */
    public function makeVisible($attributes)
    {
        //在每个项目上执行回调
        return $this->each(function ($model) use ($attributes) {
            //让给定的，通常隐藏的属性可见
            $model->makeVisible($attributes);
        });
    }

    /**
     * Get a dictionary keyed by primary keys.
     *
     * 用主键键入字典
     *
     * @param  \ArrayAccess|array|null  $items
     * @return array
     */
    public function getDictionary($items = null)
    {
        $items = is_null($items) ? $this->items : $items;

        $dictionary = [];

        foreach ($items as $value) {
            $dictionary[$value->getKey()] = $value;
        }

        return $dictionary;
    }

    /**
     * The following methods are intercepted to always return base collections.
     *
     * 下面的方法被拦截，以返回基本集合
     */

    /**
     * Get an array with the values of a given key.
     *
     * 获取一个给定键值的数组
     *
     * @param  string  $value
     * @param  string|null  $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($value, $key = null)
    {
        //从集合中获取基础支持集合实例->获取给定键的值
        return $this->toBase()->pluck($value, $key);
    }

    /**
     * Get the keys of the collection items.
     *
     * 获取集合项目的关键字
     *
     * @return \Illuminate\Support\Collection
     */
    public function keys()
    {
        //从集合中获取基础支持集合实例->获取集合项的键
        return $this->toBase()->keys();
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * 将集合与一个或多个数组一起压缩
     *
     * @param  mixed ...$items
     * @return \Illuminate\Support\Collection
     */
    public function zip($items)
    {
        //                          从集合中获取基础支持集合实例
        return call_user_func_array([$this->toBase(), 'zip'], func_get_args());
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * 将项目的集合折叠成一个数组
     *
     * @return \Illuminate\Support\Collection
     */
    public function collapse()
    {
        //从集合中获取基础支持集合实例->将项目集合折叠到单个数组中
        return $this->toBase()->collapse();
    }

    /**
     * Get a flattened array of the items in the collection.
     *
     * 在集合中获取一个扁平数组
     *
     * @param  int  $depth
     * @return \Illuminate\Support\Collection
     */
    public function flatten($depth = INF)
    {
        //从集合中获取基础支持集合实例->获取集合中的项目的扁平数组
        return $this->toBase()->flatten($depth);
    }

    /**
     * Flip the items in the collection.
     *
     * 在集合中翻转项目
     *
     * @return \Illuminate\Support\Collection
     */
    public function flip()
    {
        //从集合中获取基础支持集合实例->在集合中翻转项目
        return $this->toBase()->flip();
    }

    /**
     * Get the type of the entities being queued.
     *
     * 获取正在排队的实体的类型
     *
     * @return string|null
     */
    public function getQueueableClass()
    {
        //计数集合中的项目数
        if ($this->count() === 0) {
            return;
        }
        //                    从集合中获取第一项
        $class = get_class($this->first());
        //在每个项目上执行回调
        $this->each(function ($model) use ($class) {
            if (get_class($model) !== $class) {
                throw new LogicException('Queueing collections with multiple model types is not supported.');
            }
        });

        return $class;
    }

    /**
     * Get the identifiers for all of the entities.
     *
     * 获取所有实体的标识符
     *
     * @return array
     */
    public function getQueueableIds()
    {
        //获取主键的数组
        return $this->modelKeys();
    }
}
