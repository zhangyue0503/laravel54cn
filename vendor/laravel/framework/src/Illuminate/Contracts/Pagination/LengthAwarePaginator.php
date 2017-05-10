<?php

namespace Illuminate\Contracts\Pagination;

interface LengthAwarePaginator extends Paginator
{
    /**
     * Create a range of pagination URLs.
     *
     * 创建一系列的分页url
     *
     * @param  int  $start
     * @param  int  $end
     * @return array
     */
    public function getUrlRange($start, $end);

    /**
     * Determine the total number of items in the data store.
     *
     * 确定数据存储中项目的总数
     *
     * @return int
     */
    public function total();

    /**
     * Get the page number of the last available page.
     *
     * 获取最后可用页面的页面数量
     *
     * @return int
     */
    public function lastPage();
}
