<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;

class TaggedCache extends Repository
{
    use RetrievesMultipleKeys;

    /**
     * The tag set instance.
     *
     * 标记集实例
     *
     * @var \Illuminate\Cache\TagSet
     */
    protected $tags;

    /**
     * Create a new tagged cache instance.
     *
     * 创建一个新的标记缓存实例
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @param  \Illuminate\Cache\TagSet  $tags
     * @return void
     */
    public function __construct(Store $store, TagSet $tags)
    {
        //创建一个新的缓存存储库实例
        parent::__construct($store);

        $this->tags = $tags;
    }

    /**
     * Increment the value of an item in the cache.
     *
     * 增加缓存中的项的值
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function increment($key, $value = 1)
    {
        //增加缓存中的项的值            格式化缓存项的键
        $this->store->increment($this->itemKey($key), $value);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * 增加缓存中的项的值
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function decrement($key, $value = 1)
    {
        //增加缓存中的项的值          格式化缓存项的键
        $this->store->decrement($this->itemKey($key), $value);
    }

    /**
     * Remove all items from the cache.
     *
     * 从缓存中删除所有项
     *
     * @return void
     */
    public function flush()
    {
        //重置集合中的所有标签
        $this->tags->reset();
    }

    /**
     * {@inheritdoc}
     * 格式化缓存项的键
     */
    protected function itemKey($key)
    {
        //为一个有标签的项目获得一个完全合格的密钥
        return $this->taggedItemKey($key);
    }

    /**
     * Get a fully qualified key for a tagged item.
     *
     * 为一个有标签的项目获得一个完全合格的密钥
     *
     * @param  string  $key
     * @return string
     */
    public function taggedItemKey($key)
    {
        //获得一个惟一的名称空间，当任何标记被刷新时，它会发生变化
        return sha1($this->tags->getNamespace()).':'.$key;
    }

    /**
     * Fire an event for this cache instance.
     *
     * 为这个缓存实例触发事件
     *
     * @param  string  $event
     * @return void
     */
    protected function event($event)
    {
        //为这个缓存实例触发事件(为缓存事件设置标签(在集合中获取所有的标签名))
        parent::event($event->setTags($this->tags->getNames()));
    }
}
