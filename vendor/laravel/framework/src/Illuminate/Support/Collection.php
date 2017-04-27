<?php

namespace Illuminate\Support;

use Countable;
use Exception;
use ArrayAccess;
use Traversable;
use ArrayIterator;
use CachingIterator;
use JsonSerializable;
use IteratorAggregate;
use InvalidArgumentException;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Collection implements ArrayAccess, Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    use Macroable;

    /**
     * The items contained in the collection.
     *
     * 集合中包含的项
     *
     * @var array
     */
    protected $items = [];

    /**
     * The methods that can be proxied.
     *
     * 可以使用的方法
     *
     * @var array
     */
    protected static $proxies = [
        'contains', 'each', 'every', 'filter', 'first', 'map',
        'partition', 'reject', 'sortBy', 'sortByDesc', 'sum',
    ];

    /**
     * Create a new collection.
	 *
	 * 创建一个新集合
     *
     * @param  mixed  $items
     * @return void
     */
    public function __construct($items = [])
    {
        //                收集结果从Collection或Arrayable数组
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * 创建一个新的集合实例，如果该值不是一个准备好的
     *
     * @param  mixed  $items
     * @return static
     */
    public static function make($items = [])
    {
        return new static($items);
    }

    /**
     * Get all of the items in the collection.
	 *
	 * 获取集合中的所有项目
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Get the average value of a given key.
     *
     * 获取给定键的平均值
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function avg($callback = null)
    {
        if ($count = $this->count()) {//计数集合中的项目数
            //       得到给定值的和
            return $this->sum($callback) / $count;
        }
    }

    /**
     * Alias for the "avg" method.
     *
     * avg方法的别名
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function average($callback = null)
    {
        return $this->avg($callback);//获取给定键的平均值
    }

    /**
     * Get the median of a given key.
     *
     * 得到给定键的中位数
     *
     * @param  null $key
     * @return mixed
     */
    public function median($key = null)
    {
        $count = $this->count();//计数集合中的项目数

        if ($count == 0) {
            return;
        }
        //         返回给定对象             获取给定键的值
        $values = with(isset($key) ? $this->pluck($key) : $this)
                    ->sort()->values();

        $middle = (int) ($count / 2);

        if ($count % 2) {
            return $values->get($middle);
        }

        return (new static([
            $values->get($middle - 1), $values->get($middle),
        ]))->average();// avg方法的别名
    }

    /**
     * Get the mode of a given key.
     *
     * 获取给定键的模式
     *
     * @param  mixed  $key
     * @return array|null
     */
    public function mode($key = null)
    {
        $count = $this->count();//计数集合中的项目数

        if ($count == 0) {
            return;
        }
        //                          获取给定键的值
        $collection = isset($key) ? $this->pluck($key) : $this;

        $counts = new self;
        //在每个项目上执行回调
        $collection->each(function ($value) use ($counts) {
            $counts[$value] = isset($counts[$value]) ? $counts[$value] + 1 : 1;
        });

        $sorted = $counts->sort();//通过回调来对每个项目进行排序

        $highestValue = $sorted->last();//从集合中获取最后一个项
        //在每个项目上运行过滤器
        return $sorted->filter(function ($value) use ($highestValue) {
            return $value == $highestValue;
        })->sort()->keys()->all();//通过回调来对每个项目进行排序->获取集合项的键->获取集合中的所有项目
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * 将项目集合折叠到单个数组中
     *
     * @return static
     */
    public function collapse()
    {
        //                将数组数组折叠为单个数组
        return new static(Arr::collapse($this->items));
    }

    /**
     * Determine if an item exists in the collection.
     *
     * 确定集合中是否存在项
     *
     * @param  mixed  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        if (func_num_args() == 1) {
            if ($this->useAsCallable($key)) {//确定给定值是否可调用，但不是字符串
                return ! is_null($this->first($key));//从集合中获取第一项
            }

            return in_array($key, $this->items);
        }

        if (func_num_args() == 2) {
            $value = $operator;

            $operator = '=';
        }
        //        确定集合中是否存在项     得到一个操作检查回调
        return $this->contains($this->operatorForWhere($key, $operator, $value));
    }

    /**
     * Determine if an item exists in the collection using strict comparison.
     *
     * 使用严格比较确定集合中是否存在项
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return bool
     */
    public function containsStrict($key, $value = null)
    {
        if (func_num_args() == 2) {
            //确定集合中是否存在项
            return $this->contains(function ($item) use ($key, $value) {
                //使用“点”符号从数组或对象中获取项
                return data_get($item, $key) === $value;
            });
        }
        //确定给定值是否可调用，但不是字符串
        if ($this->useAsCallable($key)) {
            //                从集合中获取第一项
            return ! is_null($this->first($key));
        }

        return in_array($key, $this->items, true);
    }

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * 获取在给定项目中不存在的集合中的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function diff($items)
    {
        //                                            收集结果从Collection或Arrayable数组
        return new static(array_diff($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Get the items in the collection whose keys are not present in the given items.
     *
     * 获取在给定项目中不存在键的集合中的项
     *
     * @param  mixed  $items
     * @return static
     */
    public function diffKeys($items)
    {
        //                                            收集结果从Collection或Arrayable数组
        return new static(array_diff_key($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Execute a callback over each item.
     *
     * 在每个项目上执行回调
     *
     * @param  callable  $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Determine if all items in the collection pass the given test.
     *
     * 确定集合中的所有项目是否通过给定的测试
     *
     * @param  string|callable  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function every($key, $operator = null, $value = null)
    {
        if (func_num_args() == 1) {
            $callback = $this->valueRetriever($key);//获取一个值检索回调

            foreach ($this->items as $k => $v) {
                if (! $callback($v, $k)) {
                    return false;
                }
            }

            return true;
        }

        if (func_num_args() == 2) {
            $value = $operator;

            $operator = '=';
        }
        //确定集合中的所有项目是否通过给定的测试   得到一个操作检查回调
        return $this->every($this->operatorForWhere($key, $operator, $value));
    }

    /**
     * Get all items except for those with the specified keys.
     *
     * 获取除指定键外的所有项目
     *
     * @param  mixed  $keys
     * @return static
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        //获取指定数组，除了指定的数组项
        return new static(Arr::except($this->items, $keys));
    }

    /**
     * Run a filter over each of the items.
     *
     * 在每个项目上运行过滤器
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if ($callback) {
            //                    使用给定的回调筛选数组
            return new static(Arr::where($this->items, $callback));
        }

        return new static(array_filter($this->items));
    }

    /**
     * Apply the callback if the value is truthy.
     *
     * 如果该值是一些应用回调
     *
     * @param  bool  $value
     * @param  callable  $callback
     * @param  callable  $default
     * @return mixed
     */
    public function when($value, callable $callback, callable $default = null)
    {
        if ($value) {
            return $callback($this);
        } elseif ($default) {
            return $default($this);
        }

        return $this;
    }

    /**
     * Filter items by the given key value pair.
     *
     * 按给定键值对筛选项目
     *
     * @param  string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return static
     */
    public function where($key, $operator, $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;

            $operator = '=';
        }
        //    在每个项目上运行过滤器        得到一个操作检查回调
        return $this->filter($this->operatorForWhere($key, $operator, $value));
    }

    /**
     * Get an operator checker callback.
     *
     * 得到一个操作检查回调
     *
     * @param  string  $key
     * @param  string  $operator
     * @param  mixed  $value
     * @return \Closure
     */
    protected function operatorForWhere($key, $operator, $value)
    {
        return function ($item) use ($key, $operator, $value) {
            $retrieved = data_get($item, $key);

            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
            }
        };
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * 使用给定的键值对使用严格比较筛选项目
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return static
     */
    public function whereStrict($key, $value)
    {
        //             按给定键值对筛选项目
        return $this->where($key, '===', $value);
    }

    /**
     * Filter items by the given key value pair.
     *
     * 按给定键值对筛选项目
     *
     * @param  string  $key
     * @param  mixed  $values
     * @param  bool  $strict
     * @return static
     */
    public function whereIn($key, $values, $strict = false)
    {
        $values = $this->getArrayableItems($values);//收集结果从Collection或Arrayable数组
        //在每个项目上运行过滤器
        return $this->filter(function ($item) use ($key, $values, $strict) {
            //               使用“点”符号从数组或对象中获取项
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * 使用给定的键值对使用严格比较筛选项目
     *
     * @param  string  $key
     * @param  mixed  $values
     * @return static
     */
    public function whereInStrict($key, $values)
    {
        //          按给定键值对筛选项目
        return $this->whereIn($key, $values, true);
    }

    /**
     * Filter items by the given key value pair.
     *
     * 按给定键值对筛选项目
     *
     * @param  string  $key
     * @param  mixed  $values
     * @param  bool  $strict
     * @return static
     */
    public function whereNotIn($key, $values, $strict = false)
    {
        $values = $this->getArrayableItems($values);//收集结果从Collection或Arrayable数组
        //创建不通过给定的真值测试的所有元素的集合
        return $this->reject(function ($item) use ($key, $values, $strict) {
            //               使用“点”符号从数组或对象中获取项
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * 使用给定的键值对使用严格比较筛选项目
     *
     * @param  string  $key
     * @param  mixed  $values
     * @return static
     */
    public function whereNotInStrict($key, $values)
    {
        //        按给定键值对筛选项目
        return $this->whereNotIn($key, $values, true);
    }

    /**
     * Get the first item from the collection.
     *
     * 从集合中获取第一项
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        //       通过给定的真值测试返回数组中的第一个元素
        return Arr::first($this->items, $callback, $default);
    }

    /**
     * Get a flattened array of the items in the collection.
     *
     * 获取集合中的项目的扁平数组
     *
     * @param  int  $depth
     * @return static
     */
    public function flatten($depth = INF)
    {
        //              将多维数组变平为单级
        return new static(Arr::flatten($this->items, $depth));
    }

    /**
     * Flip the items in the collection.
     *
     * 在集合中翻转项目
     *
     * @return static
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * Remove an item from the collection by key.
     *
     * 从集合中移除项目
     *
     * @param  string|array  $keys
     * @return $this
     */
    public function forget($keys)
    {
        foreach ((array) $keys as $key) {
            $this->offsetUnset($key); //取消的项目在一个给定的偏移
        }

        return $this;
    }

    /**
     * Get an item from the collection by key.
     *
     * 按key从集合中获取项
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {//确定项目是否存在于偏移量
            return $this->items[$key];
        }

        return value($default);
    }

    /**
     * Group an associative array by a field or using a callback.
     *
     * 通过字段或使用回调来组合关联数组
     *
     * @param  callable|string  $groupBy
     * @param  bool  $preserveKeys
     * @return static
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        $groupBy = $this->valueRetriever($groupBy);//获取一个值检索回调

        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);

            if (! is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }

            foreach ($groupKeys as $groupKey) {
                if (! array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static;
                }
                //通过给定的偏移量设置项目
                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }

        return new static($results);
    }

    /**
     * Key an associative array by a field or using a callback.
     *
     * 用字段或使用回调键组合一个关联数组
     *
     * @param  callable|string  $keyBy
     * @return static
     */
    public function keyBy($keyBy)
    {
        //          获取一个值检索回调
        $keyBy = $this->valueRetriever($keyBy);

        $results = [];

        foreach ($this->items as $key => $item) {
            $resolvedKey = $keyBy($item, $key);

            if (is_object($resolvedKey)) {
                $resolvedKey = (string) $resolvedKey;
            }

            $results[$resolvedKey] = $item;
        }

        return new static($results);
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * 通过键确定集合中是否存在项
     *
     * @param  mixed  $key
     * @return bool
     */
    public function has($key)
    {
        //        确定项目是否存在于偏移量
        return $this->offsetExists($key);
    }

    /**
     * Concatenate values of a given key as a string.
     *
     * 一个给定的键连接的值作为一个字符串
     *
     * @param  string  $value
     * @param  string  $glue
     * @return string
     */
    public function implode($value, $glue = null)
    {
        $first = $this->first();//从集合中获取第一项

        if (is_array($first) || is_object($first)) {
            //                    获取给定键的值          获取集合中的所有项目
            return implode($glue, $this->pluck($value)->all());
        }

        return implode($value, $this->items);
    }

    /**
     * Intersect the collection with the given items.
     *
     * 将集合与给定项相交
     *
     * @param  mixed  $items
     * @return static
     */
    public function intersect($items)
    {
        //               计算数组的交集                         收集结果从Collection或Arrayable数组
        return new static(array_intersect($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Determine if the collection is empty or not.
     *
     * 确定集合是否为空
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Determine if the collection is not empty.
     *
     * 确定集合是否为空
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return ! $this->isEmpty();//确定集合是否为空
    }

    /**
     * Determine if the given value is callable, but not a string.
     *
     * 确定给定值是否可调用，但不是字符串
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function useAsCallable($value)
    {
        return ! is_string($value) && is_callable($value);
    }

    /**
     * Get the keys of the collection items.
     *
     * 获取集合项的键
     *
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * Get the last item from the collection.
     *
     * 从集合中获取最后一个项
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        //     返回经过给定的真值测试的数组中的最后一个元素
        return Arr::last($this->items, $callback, $default);
    }

    /**
     * Get the values of a given key.
     *
     * 获取给定键的值
     *
     * @param  string|array  $value
     * @param  string|null  $key
     * @return static
     */
    public function pluck($value, $key = null)
    {
        //                  从数组中提取数组值
        return new static(Arr::pluck($this->items, $value, $key));
    }

    /**
     * Run a map over each of the items.
     *
     * 在每个项目上运行map
     *
     * @param  callable  $callback
     * @return static
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);

        $items = array_map($callback, $this->items, $keys);

        return new static(array_combine($keys, $items));
    }

    /**
     * Run an associative map over each of the items.
     *
     * 在每个项目上运行关联映射
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * 回调应该返回一个具有单个 键/值 对的关联数组
     *0
     * @param  callable  $callback
     * @return static
     */
    public function mapWithKeys(callable $callback)
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);

            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return new static($result);
    }

    /**
     * Map a collection and flatten the result by a single level.
     *
     * 映射集合并将结果按单个级别拉平
     *
     * @param  callable  $callback
     * @return static
     */
    public function flatMap(callable $callback)
    {
        //       在每个项目上运行map       将项目集合折叠到单个数组中
        return $this->map($callback)->collapse();
    }

    /**
     * Get the max value of a given key.
     *
     * 获取给定键的最大值
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function max($callback = null)
    {
        $callback = $this->valueRetriever($callback);//获取一个值检索回调
        //       在每个项目上运行过滤器
        return $this->filter(function ($value) {
            return ! is_null($value);
            //调用array_reduce
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);

            return is_null($result) || $value > $result ? $value : $result;
        });
    }

    /**
     * Merge the collection with the given items.
     *
     * 将集合与给定项合并
     *
     * @param  mixed  $items
     * @return static
     */
    public function merge($items)
    {
        //                                            收集结果从Collection或Arrayable数组
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * 通过使用此集合来获取集合，并为其值创建另一个集合
     *
     * @param  mixed  $values
     * @return static
     */
    public function combine($values)
    {
        //                                 获取集合中的所有项目     收集结果从Collection或Arrayable数组
        return new static(array_combine($this->all(), $this->getArrayableItems($values)));
    }

    /**
     * Union the collection with the given items.
     *
     * 将集合与给定项结合
     *
     * @param  mixed  $items
     * @return static
     */
    public function union($items)
    {
        //                                 收集结果从Collection或Arrayable数组
        return new static($this->items + $this->getArrayableItems($items));
    }

    /**
     * Get the min value of a given key.
     *
     * 获取给定键的最小值
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function min($callback = null)
    {
        $callback = $this->valueRetriever($callback);//获取一个值检索回调

        return $this->filter(function ($value) {//在每个项目上运行过滤器
            return ! is_null($value);
            //调用array_reduce
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);

            return is_null($result) || $value < $result ? $value : $result;
        });
    }

    /**
     * Create a new collection consisting of every n-th element.
     *
     * 创建一个新的集合组成的每一个n元
     *
     * @param  int  $step
     * @param  int  $offset
     * @return static
     */
    public function nth($step, $offset = 0)
    {
        $new = [];

        $position = 0;

        foreach ($this->items as $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }

            $position++;
        }

        return new static($new);
    }

    /**
     * Get the items with the specified keys.
     *
     * 使用指定键获取项目
     *
     * @param  mixed  $keys
     * @return static
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            return new static($this->items);
        }

        $keys = is_array($keys) ? $keys : func_get_args();
        //                    从给定数组中获取项目的子集
        return new static(Arr::only($this->items, $keys));
    }

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * “页码”的切片，它变成一个较小的收集
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return static
     */
    public function forPage($page, $perPage)
    {
        //       切片底层集合数组
        return $this->slice(($page - 1) * $perPage, $perPage);
    }

    /**
     * Partition the collection into two arrays using the given callback or key.
     *
     * 使用给定的回调或key将集合分割为两个数组
     *
     * @param  callable|string  $callback
     * @return static
     */
    public function partition($callback)
    {
        $partitions = [new static, new static];

        $callback = $this->valueRetriever($callback);//获取一个值检索回调

        foreach ($this->items as $key => $item) {
            $partitions[(int) ! $callback($item)][$key] = $item;
        }

        return new static($partitions);
    }

    /**
     * Pass the collection to the given callback and return the result.
     *
     * 将集合传递给给定的回调函数并返回结果
     *
     * @param  callable $callback
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    /**
     * Get and remove the last item from the collection.
     *
     * 获取并移除集合中的最后一个项
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Push an item onto the beginning of the collection.
     *
     * 将项目推到集合的开头
     *
     * @param  mixed  $value
     * @param  mixed  $key
     * @return $this
     */
    public function prepend($value, $key = null)
    {
        //               将项目推到数组的开头
        $this->items = Arr::prepend($this->items, $value, $key);

        return $this;
    }

    /**
     * Push an item onto the end of the collection.
     *
     * 将项目推到集合的结尾
     *
     * @param  mixed  $value
     * @return $this
     */
    public function push($value)
    {
        //通过给定的偏移量设置项目
        $this->offsetSet(null, $value);

        return $this;
    }

    /**
     * Get and remove an item from the collection.
     *
     * 从集合中获取和移除项
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        //     从数组中获取值，并将其移除
        return Arr::pull($this->items, $key, $default);
    }

    /**
     * Put an item in the collection by key.
     *
     * 按项在集合中放置项
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return $this
     */
    public function put($key, $value)
    {
        //通过给定的偏移量设置项目
        $this->offsetSet($key, $value);

        return $this;
    }

    /**
     * Get one or more items randomly from the collection.
     *
     * 从集合中随机获取一个或多个项
     *
     * @param  int|null  $amount
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function random($amount = 1)
    {
        //                 计数集合中的项目数
        if ($amount > ($count = $this->count())) {
            throw new InvalidArgumentException("You requested {$amount} items, but there are only {$count} items in the collection.");
        }

        $keys = array_rand($this->items, $amount);

        if (count(func_get_args()) == 0) {
            return $this->items[$keys];
        }
        //       如果给定值不是数组，请将其包在一个数组中
        $keys = array_wrap($keys);

        return new static(array_intersect_key($this->items, array_flip($keys)));
    }

    /**
     * Reduce the collection to a single value.
     *
     * 调用array_reduce
     *
     * @param  callable  $callback
     * @param  mixed  $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * 创建不通过给定的真值测试的所有元素的集合
     *
     * @param  callable|mixed  $callback
     * @return static
     */
    public function reject($callback)
    {
        //确定给定值是否可调用，但不是字符串
        if ($this->useAsCallable($callback)) {
            //在每个项目上运行过滤器
            return $this->filter(function ($value, $key) use ($callback) {
                return ! $callback($value, $key);
            });
        }
        //在每个项目上运行过滤器
        return $this->filter(function ($item) use ($callback) {
            return $item != $callback;
        });
    }

    /**
     * Reverse items order.
     *
     * 反向项目顺序
     *
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * 搜索给定值的集合，如果成功返回相应的键
     *
     * @param  mixed  $value
     * @param  bool  $strict
     * @return mixed
     */
    public function search($value, $strict = false)
    {
        //确定给定值是否可调用，但不是字符串
        if (! $this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }

        foreach ($this->items as $key => $item) {
            if (call_user_func($value, $item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Get and remove the first item from the collection.
     *
     * 从集合中获取并移除第一项
     *
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Shuffle the items in the collection.
     *
     * 将集合中的项洗牌
     *
     * @param  int  $seed
     * @return static
     */
    public function shuffle($seed = null)
    {
        $items = $this->items;

        if (is_null($seed)) {
            shuffle($items);
        } else {
            srand($seed);

            usort($items, function () {
                return rand(-1, 1);
            });
        }

        return new static($items);
    }

    /**
     * Slice the underlying collection array.
     *
     * 切片底层集合数组
     *
     * @param  int  $offset
     * @param  int  $length
     * @return static
     */
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Split a collection into a certain number of groups.
     *
     * 将集合拆分成若干个组
     *
     * @param  int  $numberOfGroups
     * @return static
     */
    public function split($numberOfGroups)
    {
        if ($this->isEmpty()) {//确定集合是否为空
            return new static;
        }
        //                计数集合中的项目数
        $groupSize = ceil($this->count() / $numberOfGroups);
        //         基础集合数组
        return $this->chunk($groupSize);
    }

    /**
     * Chunk the underlying collection array.
     *
     * 基础集合数组
     *
     * @param  int  $size
     * @return static
     */
    public function chunk($size)
    {
        if ($size <= 0) {
            return new static;
        }

        $chunks = [];

        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * Sort through each item with a callback.
     *
     * 通过回调来对每个项目进行排序
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function sort(callable $callback = null)
    {
        $items = $this->items;

        $callback
            ? uasort($items, $callback)
            : asort($items);

        return new static($items);
    }

    /**
     * Sort the collection using the given callback.
     *
     * 使用给定的回调排序集合
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @param  bool  $descending
     * @return static
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false)
    {
        $results = [];

        $callback = $this->valueRetriever($callback);//获取一个值检索回调

        // First we will loop through the items and get the comparator from a callback
        // function which we were given. Then, we will sort the returned values and
        // and grab the corresponding values for the sorted keys from this array.
        //
        // 首先，我们将循环通过的项目，并得到一个回调函数，我们给出了比较器
        // 然后，我们将对返回的值进行排序，并从该数组中获取排序关键字的相应值
        //
        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        $descending ? arsort($results, $options)
                    : asort($results, $options);

        // Once we have sorted all of the keys in the array, we will loop through them
        // and grab the corresponding model so we can set the underlying items list
        // to the sorted version. Then we'll just return the collection instance.
        //
        // 一旦我们对数组中的所有键进行排序，我们将遍历它们并抓取相应的模型，以便将基础项列表设置为已排序的版本
        // 然后我们将返回集合实例
        //
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        return new static($results);
    }

    /**
     * Sort the collection in descending order using the given callback.
     *
     * 使用给定的回调顺序将集合按降序排序
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @return static
     */
    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->sortBy($callback, $options, true);//使用给定的回调排序集合
    }

    /**
     * Splice a portion of the underlying collection array.
     *
     * 将底层集合阵列的一部分拼接
     *
     * @param  int  $offset
     * @param  int|null  $length
     * @param  mixed  $replacement
     * @return static
     */
    public function splice($offset, $length = null, $replacement = [])
    {
        if (func_num_args() == 1) {
            return new static(array_splice($this->items, $offset));
        }

        return new static(array_splice($this->items, $offset, $length, $replacement));
    }

    /**
     * Get the sum of the given values.
     *
     * 得到给定值的和
     *
     * @param  callable|string|null  $callback
     * @return mixed
     */
    public function sum($callback = null)
    {
        if (is_null($callback)) {
            return array_sum($this->items);
        }

        $callback = $this->valueRetriever($callback);//获取一个值检索回调
        //调用array_reduce
        return $this->reduce(function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * Take the first or last {$limit} items.
     *
     * 以第一个或最后一个{$limit}项目
     *
     * @param  int  $limit
     * @return static
     */
    public function take($limit)
    {
        if ($limit < 0) {
            //切片底层集合数组
            return $this->slice($limit, abs($limit));
        }
        //切片底层集合数组
        return $this->slice(0, $limit);
    }

    /**
     * Pass the collection to the given callback and then return it.
     *
     * 将集合传递给给定的回调函数，然后返回
     *
     * @param  callable  $callback
     * @return $this
     */
    public function tap(callable $callback)
    {
        $callback(new static($this->items));

        return $this;
    }

    /**
     * Transform each item in the collection using a callback.
     *
     * 使用回调函数转换集合中的每个项
     *
     * @param  callable  $callback
     * @return $this
     */
    public function transform(callable $callback)
    {
        //             在每个项目上运行map      获取集合中的所有项目
        $this->items = $this->map($callback)->all();

        return $this;
    }

    /**
     * Return only unique items from the collection array.
     *
     * 只返回集合数组中的唯一项
     *
     * @param  string|callable|null  $key
     * @param  bool  $strict
     * @return static
     */
    public function unique($key = null, $strict = false)
    {
        if (is_null($key)) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }

        $callback = $this->valueRetriever($key);//获取一个值检索回调

        $exists = [];
        //创建不通过给定的真值测试的所有元素的集合
        return $this->reject(function ($item, $key) use ($callback, $strict, &$exists) {
            if (in_array($id = $callback($item, $key), $exists, $strict)) {
                return true;
            }

            $exists[] = $id;
        });
    }

    /**
     * Return only unique items from the collection array using strict comparison.
     *
     * 只使用严格的比较返回集合数组中的惟一项
     *
     * @param  string|callable|null  $key
     * @return static
     */
    public function uniqueStrict($key = null)
    {
        return $this->unique($key, true); //只返回集合数组中的唯一项
    }

    /**
     * Reset the keys on the underlying array.
     *
     * 重置基础阵列上的键
     *
     * @return static
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * Get a value retrieving callback.
     *
     * 获取一个值检索回调
     *
     * @param  string  $value
     * @return callable
     */
    protected function valueRetriever($value)
    {
        if ($this->useAsCallable($value)) {//确定给定值是否可调用，但不是字符串
            return $value;
        }

        return function ($item) use ($value) {
            return data_get($item, $value);//使用“点”符号从数组或对象中获取项
        };
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * 将集合与一个或多个数组一起压缩
     *
     * e.g. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param  mixed ...$items
     * @return static
     */
    public function zip($items)
    {
        $arrayableItems = array_map(function ($items) {
            return $this->getArrayableItems($items);//收集结果从Collection或Arrayable数组
        }, func_get_args());

        $params = array_merge([function () {
            return new static(func_get_args());
        }, $this->items], $arrayableItems);

        return new static(call_user_func_array('array_map', $params));
    }

    /**
     * Get the collection of items as a plain array.
     *
     * 将项目的集合作为一个简单的数组
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            //                                     获取数组实例
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * 将对象转换为JSON可序列化的对象
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof Jsonable) {
                //                     将对象转换为JSON表示形式
                return json_decode($value->toJson(), true);
            } elseif ($value instanceof Arrayable) {
                return $value->toArray();//获取数组实例
            } else {
                return $value;
            }
        }, $this->items);
    }

    /**
     * Get the collection of items as JSON.
     *
     * 获取项目的集合为JSON
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        //                  将对象转换为JSON可序列化的对象
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get an iterator for the items.
     *
     * 获取项目的迭代器
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Get a CachingIterator instance.
     *
     * 得到一个CachingIterator实例
     *
     * @param  int  $flags
     * @return \CachingIterator
     */
    public function getCachingIterator($flags = CachingIterator::CALL_TOSTRING)
    {
        //                             获取项目的迭代器
        return new CachingIterator($this->getIterator(), $flags);
    }

    /**
     * Count the number of items in the collection.
     *
     * 计数集合中的项目数
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Get a base Support collection instance from this collection.
     *
     * 从集合中获取基础支持集合实例
     *
     * @return \Illuminate\Support\Collection
     */
    public function toBase()
    {
        return new self($this);
    }

    /**
     * Determine if an item exists at an offset.
     *
     * 确定项目是否存在于偏移量
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item at a given offset.
     *
     * 得到给定偏移量的项目
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * 通过给定的偏移量设置项目
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * 取消的项目在一个给定的偏移
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Convert the collection to its string representation.
     *
     * 将集合转换为字符串表示形式
     *
     * @return string
     */
    public function __toString()
    {
        //获取项目的集合为JSON
        return $this->toJson();
    }

    /**
     * Results array of items from Collection or Arrayable.
     *
     * 收集结果从Collection或Arrayable数组
     *
     * @param  mixed  $items
     * @return array
     */
    protected function getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof self) {
            return $items->all();//获取集合中的所有项目
        } elseif ($items instanceof Arrayable) {
            return $items->toArray();//获取数组实例
        } elseif ($items instanceof Jsonable) {
            //                     将对象转换为JSON表示形式
            return json_decode($items->toJson(), true);
        } elseif ($items instanceof JsonSerializable) {
            return $items->jsonSerialize();
        } elseif ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }

    /**
     * Add a method to the list of proxied methods.
     *
     * 方法添加到代理的方法列表
     *
     * @param  string  $method
     * @return void
     */
    public static function proxy($method)
    {
        static::$proxies[] = $method;
    }

    /**
     * Dynamically access collection proxies.
     *
     * 动态访问集合代理
     *
     * @param  string  $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get($key)
    {
        if (! in_array($key, static::$proxies)) {
            throw new Exception("Property [{$key}] does not exist on this collection instance.");
        }
        //       创建一个新的代理实例
        return new HigherOrderCollectionProxy($this, $key);
    }
}
