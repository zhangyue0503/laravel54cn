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
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;

class LengthAwarePaginator extends AbstractPaginator implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Jsonable, LengthAwarePaginatorContract
{
    /**
     * The total number of items before slicing.
     *
     * 在切片之前的项目总数
     *
     * @var int
     */
    protected $total;

    /**
     * The last available page.
     *
     * 最后一个可用的页面
     *
     * @var int
     */
    protected $lastPage;

    /**
     * Create a new paginator instance.
     *
     * 创建一个新的页面实例
     *
     * @param  mixed  $items
     * @param  int  $total
     * @param  int  $perPage
     * @param  int|null  $currentPage
     * @param  array  $options (path, query, fragment, pageName)
     * @return void
     */
    public function __construct($items, $total, $perPage, $currentPage = null, array $options = [])
    {
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->total = $total;
        $this->perPage = $perPage;
        $this->lastPage = (int) ceil($total / $perPage);
        $this->path = $this->path != '/' ? rtrim($this->path, '/') : $this->path;
        //                        获取请求的当前页面
        $this->currentPage = $this->setCurrentPage($currentPage, $this->pageName);
        //                                                               创建一个新的集合实例，如果该值不是一个准备好的
        $this->items = $items instanceof Collection ? $items : Collection::make($items);
    }

    /**
     * Get the current page for the request.
     *
     * 获取请求的当前页面
     *
     * @param  int  $currentPage
     * @param  string  $pageName
     * @return int
     */
    protected function setCurrentPage($currentPage, $pageName)
    {
        //                                    解析当前页或返回默认值
        $currentPage = $currentPage ?: static::resolveCurrentPage($pageName);
        //          确定给定值是否为有效的页码
        return $this->isValidPageNumber($currentPage) ? (int) $currentPage : 1;
    }

    /**
     * Render the paginator using the given view.
     *
     * 使用给定的视图呈现paginator
     *
     * @param  string  $view
     * @param  array  $data
     * @return string
     */
    public function links($view = null, $data = [])
    {
        //使用给定的视图呈现paginator
        return $this->render($view, $data);
    }

    /**
     * Render the paginator using the given view.
     *
     * 使用给定的视图呈现paginator
     *
     * @param  string  $view
     * @param  array  $data
     * @return string
     */
    public function render($view = null, $data = [])
    {
        //创建一个新的HTML字符串实例    从解析器获取视图工厂的实例->获取给定视图的评估视图内容
        return new HtmlString(static::viewFactory()->make($view ?: static::$defaultView, array_merge($data, [
            'paginator' => $this,
            'elements' => $this->elements(),//将元素数组传递给视图
        ]))->render());//获取对象的评估内容
    }

    /**
     * Get the array of elements to pass to the view.
     *
     * 将元素数组传递给视图
     *
     * @return array
     */
    protected function elements()
    {
        //             创建一个新的URL窗口实例
        $window = UrlWindow::make($this);

        return array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);
    }

    /**
     * Get the total number of items being paginated.
     *
     * 获取被分页的项的总数
     *
     * @return int
     */
    public function total()
    {
        return $this->total;
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
        //         得到当前页面              得到最后一页
        return $this->currentPage() < $this->lastPage();
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
        //      得到最后一页         得到当前页面
        if ($this->lastPage() > $this->currentPage()) {
            //    获取给定页面编号的URL
            return $this->url($this->currentPage() + 1);
        }
    }

    /**
     * Get the last page.
     *
     * 得到最后一页
     *
     * @return int
     */
    public function lastPage()
    {
        return $this->lastPage;
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
            'total' => $this->total(),//获取被分页的项的总数
            'per_page' => $this->perPage(),//获取每页显示的项目数量
            'current_page' => $this->currentPage(),//得到当前页面
            'last_page' => $this->lastPage(),//得到最后一页
            'next_page_url' => $this->nextPageUrl(),//获取下一个页面的URL
            'prev_page_url' => $this->previousPageUrl(),//获取前一页的URL
            'from' => $this->firstItem(),//得到切片中第一个项目的数量
            'to' => $this->lastItem(),//获取切片中最后一个项目的数量
            'data' => $this->items->toArray(),//将项目的集合作为一个简单的数组
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
        //将实例作为数组
        return $this->toArray();
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
        //                     将对象转换为JSON可序列化的对象
        return json_encode($this->jsonSerialize(), $options);
    }
}
