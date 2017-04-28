<?php

namespace Illuminate\Support;
//item命名空间解析
class NamespacedItemResolver
{
    /**
     * A cache of the parsed items.
     *
     * 解析项的缓存
     *
     * @var array
     */
    protected $parsed = [];

    /**
     * Parse a key into namespace, group, and item.
     *
     * 将关键字解析为命名空间、组和项
     *
     * @param  string  $key
     * @return array
     */
    public function parseKey($key)
    {
        // If we've already parsed the given key, we'll return the cached version we
        // already have, as this will save us some processing. We cache off every
        // key we parse so we can quickly return it on all subsequent requests.
        //
        // 如果我们已经分析了给定的key，我们将返回缓存版本，我们已经有了，因为这将节省我们一些处理
        // 我们缓存我们解析的每一个key，这样我们就可以在所有随后的请求上快速返回它
        //
        if (isset($this->parsed[$key])) {
            return $this->parsed[$key];
        }

        // If the key does not contain a double colon, it means the key is not in a
        // namespace, and is just a regular configuration item. Namespaces are a
        // tool for organizing configuration items for things such as modules.
        //
        // 如果key不包含双冒号，则意味着key不在命名空间中，并且只是一个常规的配置项
        // 命名空间是用于组织诸如模块之类的配置项的工具
        //
        if (strpos($key, '::') === false) {
            $segments = explode('.', $key);
            //               解析基本段的数组
            $parsed = $this->parseBasicSegments($segments);
        } else {
            //           解析命名空间段阵列
            $parsed = $this->parseNamespacedSegments($key);
        }

        // Once we have the parsed array of this key's elements, such as its groups
        // and namespace, we will cache each array inside a simple list that has
        // the key and the parsed array for quick look-ups for later requests.
        //
        // 一旦我们得到了这个key元素的解析数组，例如它的组和命名空间，我们将在一个简单的列表中缓存每个数组，其中包含了key和解析数组，以便以后的请求快速查找
        //
        return $this->parsed[$key] = $parsed;
    }

    /**
     * Parse an array of basic segments.
     *
     * 解析基本段的数组
     *
     * @param  array  $segments
     * @return array
     */
    protected function parseBasicSegments(array $segments)
    {
        // The first segment in a basic array will always be the group, so we can go
        // ahead and grab that segment. If there is only one total segment we are
        // just pulling an entire group out of the array and not a single item.
        //
        // 基本数组中的第一个段始终是组，所以我们可以继续抓取该段
        // 如果只有一个总片段，我们只是把整个组从数组中取出，而不是单个项
        //
        $group = $segments[0];

        if (count($segments) == 1) {
            return [null, $group, null];
        }

        // If there is more than one segment in this group, it means we are pulling
        // a specific item out of a group and will need to return this item name
        // as well as the group so we know which item to pull from the arrays.
        //
        // 如果该组中有多个段，则意味着我们从一个组中拉出特定项，并且需要返回该项名称以及该组，因此我们知道从数组中拔出哪个项目
        //
        else {
            $item = implode('.', array_slice($segments, 1));

            return [null, $group, $item];
        }
    }

    /**
     * Parse an array of namespaced segments.
     *
     * 解析命名空间段阵列
     *
     * @param  string  $key
     * @return array
     */
    protected function parseNamespacedSegments($key)
    {
        list($namespace, $item) = explode('::', $key);

        // First we'll just explode the first segment to get the namespace and group
        // since the item should be in the remaining segments. Once we have these
        // two pieces of data we can proceed with parsing out the item's value.
        //
        // 首先，我们将拆分第一个段，以获得命名空间和组，因为该项目应在其余部分
        // 一旦我们拥有了这两个数据，我们就可以继续分析这个项目的值
        //
        $itemSegments = explode('.', $item);

        $groupAndItem = array_slice(
            //解析基本段的数组
            $this->parseBasicSegments($itemSegments), 1
        );

        return array_merge([$namespace], $groupAndItem);
    }

    /**
     * Set the parsed value of a key.
     *
     * 设置键的解析值
     *
     * @param  string  $key
     * @param  array   $parsed
     * @return void
     */
    public function setParsedKey($key, $parsed)
    {
        $this->parsed[$key] = $parsed;
    }
}
