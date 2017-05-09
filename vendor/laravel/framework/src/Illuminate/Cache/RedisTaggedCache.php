<?php

namespace Illuminate\Cache;
//Redis标记缓存
class RedisTaggedCache extends TaggedCache
{
    /**
     * Forever reference key.
     *
     * 永远参考键
     *
     * @var string
     */
    const REFERENCE_KEY_FOREVER = 'forever_ref';
    /**
     * Standard reference key.
     *
     * 标准的key
     *
     * @var string
     */
    const REFERENCE_KEY_STANDARD = 'standard_ref';

    /**
     * Store an item in the cache.
     *
     * 在缓存中存储一个项
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  \DateTime|float|int  $minutes
     * @return void
     */
    public function put($key, $value, $minutes = null)
    {
        //将标准密钥引用存储到存储中(获得一个惟一的名称空间，当任何标记被刷新时，它会发生变化)
        $this->pushStandardKeys($this->tags->getNamespace(), $key);
        //在缓存中存储一个项
        parent::put($key, $value, $minutes);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * 在缓存中无限期地存储一个项
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        //将永远的关键引用存储到存储中(获得一个惟一的名称空间，当任何标记被刷新时，它会发生变化,)
        $this->pushForeverKeys($this->tags->getNamespace(), $key);
        //在缓存中无限期地存储一个项
        parent::forever($key, $value);
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
        //删除所有永久存储的项
        $this->deleteForeverKeys();
        //删除所有标准项
        $this->deleteStandardKeys();
        //从缓存中删除所有项
        parent::flush();
    }

    /**
     * Store standard key references into store.
     *
     * 将标准密钥引用存储到存储中
     *
     * @param  string  $namespace
     * @param  string  $key
     * @return void
     */
    protected function pushStandardKeys($namespace, $key)
    {
        //存储对引用键的缓存键的引用
        $this->pushKeys($namespace, $key, self::REFERENCE_KEY_STANDARD);
    }

    /**
     * Store forever key references into store.
     *
     * 将永远的关键引用存储到存储中
     *
     * @param  string  $namespace
     * @param  string  $key
     * @return void
     */
    protected function pushForeverKeys($namespace, $key)
    {
        //存储对引用键的缓存键的引用
        $this->pushKeys($namespace, $key, self::REFERENCE_KEY_FOREVER);
    }

    /**
     * Store a reference to the cache key against the reference key.
     *
     * 存储对引用键的缓存键的引用
     *
     * @param  string  $namespace
     * @param  string  $key
     * @param  string  $reference
     * @return void
     */
    protected function pushKeys($namespace, $key, $reference)
    {
        //             从缓存中删除一个项目
        $fullKey = $this->store->getPrefix().sha1($namespace).':'.$key;

        foreach (explode('|', $namespace) as $segment) {
            //                                    获取该段的引用键
            $this->store->connection()->sadd($this->referenceKey($segment, $reference), $fullKey);
        }
    }

    /**
     * Delete all of the items that were stored forever.
     *
     * 删除所有永久存储的项
     *
     * @return void
     */
    protected function deleteForeverKeys()
    {
        //查找和删除存储在引用中的所有项
        $this->deleteKeysByReference(self::REFERENCE_KEY_FOREVER);
    }

    /**
     * Delete all standard items.
     *
     * 删除所有标准项
     *
     * @return void
     */
    protected function deleteStandardKeys()
    {
        //查找和删除存储在引用中的所有项
        $this->deleteKeysByReference(self::REFERENCE_KEY_STANDARD);
    }

    /**
     * Find and delete all of the items that were stored against a reference.
     *
     * 查找和删除存储在引用中的所有项
     *
     * @param  string  $reference
     * @return void
     */
    protected function deleteKeysByReference($reference)
    {
        //                      获得一个惟一的名称空间，当任何标记被刷新时，它会发生变化
        foreach (explode('|', $this->tags->getNamespace()) as $segment) {
            //删除在引用中存储的项键                  获取该段的引用键
            $this->deleteValues($segment = $this->referenceKey($segment, $reference));

            $this->store->connection()->del($segment);
        }
    }

    /**
     * Delete item keys that have been stored against a reference.
     *
     * 删除在引用中存储的项键
     *
     * @param  string  $referenceKey
     * @return void
     */
    protected function deleteValues($referenceKey)
    {
        $values = array_unique($this->store->connection()->smembers($referenceKey));

        if (count($values) > 0) {
            foreach (array_chunk($values, 1000) as $valuesChunk) {
                call_user_func_array([$this->store->connection(), 'del'], $valuesChunk);
            }
        }
    }

    /**
     * Get the reference key for the segment.
     *
     * 获取该段的引用键
     *
     * @param  string  $segment
     * @param  string  $suffix
     * @return string
     */
    protected function referenceKey($segment, $suffix)
    {
        //              获取高速缓存键前缀
        return $this->store->getPrefix().$segment.':'.$suffix;
    }
}
