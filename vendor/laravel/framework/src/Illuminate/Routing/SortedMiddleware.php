<?php

namespace Illuminate\Routing;

use Illuminate\Support\Collection;

class SortedMiddleware extends Collection
{
    /**
     * Create a new Sorted Middleware container.
     *
     * 创建一个新的已排序的中间件容器
     *
     * @param  array  $priorityMap
     * @param  array|Collection  $middlewares
     * @return void
     */
    public function __construct(array $priorityMap, $middlewares)
    {
        if ($middlewares instanceof Collection) {
            $middlewares = $middlewares->all();// 获取集合中的所有项目
        }
        //               按照给定的优先级映射对中间件进行排序
        $this->items = $this->sortMiddleware($priorityMap, $middlewares);
    }

    /**
     * Sort the middlewares by the given priority map.
     *
     * 按照给定的优先级映射对中间件进行排序
     *
     * Each call to this method makes one discrete middleware movement if necessary.
     *
     * 对该方法的每次调用都需要在必要时进行一个离散的中间件移动
     *
     * @param  array  $priorityMap
     * @param  array  $middlewares
     * @return array
     */
    protected function sortMiddleware($priorityMap, $middlewares)
    {
        $lastIndex = 0;

        foreach ($middlewares as $index => $middleware) {
            if (! is_string($middleware)) {
                continue;
            }
            //获取数组的第一个元素
            $stripped = head(explode(':', $middleware));

            if (in_array($stripped, $priorityMap)) {
                $priorityIndex = array_search($stripped, $priorityMap);

                // This middleware is in the priority map. If we have encountered another middleware
                // that was also in the priority map and was at a lower priority than the current
                // middleware, we will move this middleware to be above the previous encounter.
                //
                // 该中间件位于优先级映射中
                // 如果我们在优先级映射中遇到了另一个中间件，并且处于比当前中间件更低的优先级，我们将把该中间件移到前面的相遇之上
                //
                if (isset($lastPriorityIndex) && $priorityIndex < $lastPriorityIndex) {
                    //     按照给定的优先级映射对中间件进行排序
                    return $this->sortMiddleware(
                        $priorityMap, array_values(
                            //将中间件连接到一个新位置并删除旧的条目
                            $this->moveMiddleware($middlewares, $index, $lastIndex)
                        )
                    );

                // This middleware is in the priority map; but, this is the first middleware we have
                // encountered from the map thus far. We'll save its current index plus its index
                // from the priority map so we can compare against them on the next iterations.
                    //
                    // 此中间件处于优先级映射中;但是，这是我们从映射到目前为止遇到的第一个中间件
                    // 我们将从优先级映射中保存当前索引和它的索引，这样我们就可以在下一次迭代中与它们进行比较
                    //
                } else {
                    $lastIndex = $index;
                    $lastPriorityIndex = $priorityIndex;
                }
            }
        }

        return array_values(array_unique($middlewares, SORT_REGULAR));
    }

    /**
     * Splice a middleware into a new position and remove the old entry.
     *
     * 将中间件连接到一个新位置并删除旧的条目
     *
     * @param  array  $middlewares
     * @param  int  $from
     * @param  int  $to
     * @return array
     */
    protected function moveMiddleware($middlewares, $from, $to)
    {
        array_splice($middlewares, $to, 0, $middlewares[$from]);

        unset($middlewares[$from + 1]);

        return $middlewares;
    }
}
