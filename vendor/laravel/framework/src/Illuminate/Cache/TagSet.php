<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;

class TagSet
{
    /**
     * The cache store implementation.
     *
     * 缓存存储实现
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $store;

    /**
     * The tag names.
     *
     * 标签的名称
     *
     * @var array
     */
    protected $names = [];

    /**
     * Create a new TagSet instance.
     *
     * 创建一个新的TagSet实例
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @param  array  $names
     * @return void
     */
    public function __construct(Store $store, array $names = [])
    {
        $this->store = $store;
        $this->names = $names;
    }

    /**
     * Reset all tags in the set.
     *
     * 重置集合中的所有标签
     *
     * @return void
     */
    public function reset()
    {
        array_walk($this->names, [$this, 'resetTag']);
    }

    /**
     * Reset the tag and return the new tag identifier.
     *
     * 重置标签并返回新的标签标识符
     *
     * @param  string  $name
     * @return string
     */
    public function resetTag($name)
    {
        $this->store->forever($this->tagKey($name), $id = str_replace('.', '', uniqid('', true)));

        return $id;
    }

    /**
     * Get a unique namespace that changes when any of the tags are flushed.
     *
     * 获得一个惟一的名称空间，当任何标记被刷新时，它会发生变化
     *
     * @return string
     */
    public function getNamespace()
    {
        //为集合中的所有标签获取一组标记标识符
        return implode('|', $this->tagIds());
    }

    /**
     * Get an array of tag identifiers for all of the tags in the set.
     *
     * 为集合中的所有标签获取一组标记标识符
     *
     * @return array
     */
    protected function tagIds()
    {
        return array_map([$this, 'tagId'], $this->names);
    }

    /**
     * Get the unique tag identifier for a given tag.
     *
     * 获取特定标签的唯一标签标识符
     *
     * @param  string  $name
     * @return string
     */
    public function tagId($name)
    {
        //通过键从缓存中检索一个项(获取给定标记的标记标识符键)        重置标签并返回新的标签标识符
        return $this->store->get($this->tagKey($name)) ?: $this->resetTag($name);
    }

    /**
     * Get the tag identifier key for a given tag.
     *
     * 获取给定标记的标记标识符键
     *
     * @param  string  $name
     * @return string
     */
    public function tagKey($name)
    {
        return 'tag:'.$name.':key';
    }

    /**
     * Get all of the tag names in the set.
     *
     * 在集合中获取所有的标签名
     *
     * @return array
     */
    public function getNames()
    {
        return $this->names;
    }
}
