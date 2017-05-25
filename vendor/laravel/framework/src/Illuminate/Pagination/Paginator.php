<?php

namespace Illuminate\Pagination;

use Countable;
use ArrayAccess;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
//分页程序
class Paginator extends AbstractPaginator implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Jsonable, PaginatorContract
{
    /**
     * Determine if there are more items in the data source.
     *
     * 确定数据源中是否有更多项目
     *
     * @return bool
     */
    protected $hasMore;

    /**
     * Create a new paginator instance.
     *
     * 创建一个新的页面实例
     *
     * @param  mixed  $items
     * @param  int  $perPage
     * @param  int|null  $currentPage
     * @param  array  $options (path, query, fragment, pageName)
     * @return void
     */
    public function __construct($items, $perPage, $currentPage = null, array $options = [])
    {
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->perPage = $perPage;
        //                       获取请求的当前页面
        $this->currentPage = $this->setCurrentPage($currentPage);
        $this->path = $this->path != '/' ? rtrim($this->path, '/') : $this->path;
        //为paginator设置条目
        $this->setItems($items);
    }

    /**
     * Get the current page for the request.
     *
     * 获取请求的当前页面
     *
     * @param  int  $currentPage
     * @return int
     */
    protected function setCurrentPage($currentPage)
    {
        //                             解析当前页或返回默认值
        $currentPage = $currentPage ?: static::resolveCurrentPage();
        //            确定给定值是否为有效的页码
        return $this->isValidPageNumber($currentPage) ? (int) $currentPage : 1;
    }

    /**
     * Set the items for the paginator.
     *
     * 为paginator设置条目
     *
     * @param  mixed  $items
     * @return void
     */
    protected function setItems($items)
    {
        //                                                     创建一个新的集合实例，如果该值不是一个准备好的
        $this->items = $items instanceof Collection ? $items : Collection::make($items);

        $this->hasMore = count($this->items) > ($this->perPage);
        //                        切片底层集合数组
        $this->items = $this->items->slice(0, $this->perPage);
    }

    /**
     * Get the URL for the next page.
     *
     * 获取下一个页面的URL
     *
     * @return string|null
     */
    public function nextPageUrl()
    {
        //      确定数据源中是否有更多项目
        if ($this->hasMorePages()) {
            //获取给定页面编号的URL       得到当前页面
            return $this->url($this->currentPage() + 1);
        }
    }

    /**
     * Render the paginator using the given view.
     *
     * 使用给定的视图呈现paginator
     *
     * @param  string|null  $view
     * @param  array  $data
     * @return string
     */
    public function links($view = null, $data = [])
    {
        //         使用给定的视图呈现paginator
        return $this->render($view, $data);
    }

    /**
     * Render the paginator using the given view.
     *
     * 使用给定的视图呈现paginator
     *
     * @param  string|null  $view
     * @param  array  $data
     * @return string
     */
    public function render($view = null, $data = [])
    {
        //创建一个新的HTML字符串实例
        return new HtmlString(
            //从解析器获取视图工厂的实例->获取给定视图的评估视图内容
            static::viewFactory()->make($view ?: static::$defaultSimpleView, array_merge($data, [
                'paginator' => $this,
            ]))->render()//获取对象的评估内容
        );
    }

    /**
     * Manually indicate that the paginator does have more pages.
     *
     * 手动显示分页器确实有更多的页面
     *
     * @param  bool  $value
     * @return $this
     */
    public function hasMorePagesWhen($value = true)
    {
        $this->hasMore = $value;

        return $this;
    }

    /**
     * Determine if there are more items in the data source.
     *
     * 确定数据源中是否有更多项目
     *
     * @return bool
     */
    public function hasMorePages()
    {
        return $this->hasMore;
    }

    /**
     * Get the instance as an array.
     *
     * 将实例作为数组
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'per_page' => $this->perPage(),//获取每页显示的项目数量
            'current_page' => $this->currentPage(),//得到当前页面
            'next_page_url' => $this->nextPageUrl(),//获取下一个页面的URL
            'prev_page_url' => $this->previousPageUrl(),//获取前一页的URL
            'from' => $this->firstItem(),//得到切片中第一个项目的数量
            'to' => $this->lastItem(),//获取切片中最后一个项目的数量
            'data' => $this->items->toArray(),//获取切片中最后一个项目的数量
        ];
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
        return $this->toArray();//将实例作为数组
    }

    /**
     * Convert the object to its JSON representation.
     *
     * 将对象转换为JSON表示
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        //                      将对象转换为JSON可序列化的对象
        return json_encode($this->jsonSerialize(), $options);
    }
}
