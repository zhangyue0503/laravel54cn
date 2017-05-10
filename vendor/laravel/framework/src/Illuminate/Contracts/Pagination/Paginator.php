<?php

namespace Illuminate\Contracts\Pagination;

interface Paginator
{
    /**
     * Get the URL for a given page.
     *
     * 获取给定页面的URL
     *
     * @param  int  $page
     * @return string
     */
    public function url($page);

    /**
     * Add a set of query string values to the paginator.
     *
     * 添加一个paginator组查询字符串的值
     *
     * @param  array|string  $key
     * @param  string|null  $value
     * @return $this
     */
    public function appends($key, $value = null);

    /**
     * Get / set the URL fragment to be appended to URLs.
     *
     * 获取/设置URL片段到URL中
     *
     * @param  string|null  $fragment
     * @return $this|string
     */
    public function fragment($fragment = null);

    /**
     * The URL for the next page, or null.
     *
     * 下一个页面的URL，或null
     *
     * @return string|null
     */
    public function nextPageUrl();

    /**
     * Get the URL for the previous page, or null.
     *
     * 获取前一个页面的URL或null
     *
     * @return string|null
     */
    public function previousPageUrl();

    /**
     * Get all of the items being paginated.
     *
     * 获取所有被分页的项
     *
     * @return array
     */
    public function items();

    /**
     * Get the "index" of the first item being paginated.
     *
     * “index”的第一项是分页的
     *
     * @return int
     */
    public function firstItem();

    /**
     * Get the "index" of the last item being paginated.
     *
     * “index”的最后一项是分页的
     *
     * @return int
     */
    public function lastItem();

    /**
     * Determine how many items are being shown per page.
     *
     * 确定每个页面显示了多少项
     *
     * @return int
     */
    public function perPage();

    /**
     * Determine the current page being paginated.
     *
     * 确定当前页面被分页的页面
     *
     * @return int
     */
    public function currentPage();

    /**
     * Determine if there are enough items to split into multiple pages.
     *
     * 确定是否有足够的条目可以分成多个页面
     *
     * @return bool
     */
    public function hasPages();

    /**
     * Determine if there is more items in the data store.
     *
     * 确定数据存储中是否有更多项目
     *
     * @return bool
     */
    public function hasMorePages();

    /**
     * Determine if the list of items is empty or not.
     *
     * 确定条目的列表是否为空
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Render the paginator using a given view.
     *
     * 呈现paginator使用给定的视图
     *
     * @param  string|null  $view
     * @param  array  $data
     * @return string
     */
    public function render($view = null, $data = []);
}
