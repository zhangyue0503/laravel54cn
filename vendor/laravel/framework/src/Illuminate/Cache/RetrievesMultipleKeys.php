<?php

namespace Illuminate\Cache;

trait RetrievesMultipleKeys
{
    /**
     * Retrieve multiple items from the cache by key.
     *
     * 通过键从缓存中检索多个项
     *
     * Items not found in the cache will have a null value.
     *
     * 在缓存中未找到的项将具有空值
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys)
    {
        $return = [];

        foreach ($keys as $key) {
            //                通过键从缓存中检索一个项
            $return[$key] = $this->get($key);
        }

        return $return;
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * 将多个项目存储在缓存中，在给定的时间内
     *
     * @param  array  $values
     * @param  float|int  $minutes
     * @return void
     */
    public function putMany(array $values, $minutes)
    {
        foreach ($values as $key => $value) {
            //在缓存中存储一个项
            $this->put($key, $value, $minutes);
        }
    }
}
