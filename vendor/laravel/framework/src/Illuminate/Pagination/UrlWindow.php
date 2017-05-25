<?php

namespace Illuminate\Pagination;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as PaginatorContract;

class UrlWindow
{
    /**
     * The paginator implementation.
     *
     * paginator实现
     *
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected $paginator;

    /**
     * Create a new URL window instance.
     *
     * 创建一个新的URL窗口实例
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @return void
     */
    public function __construct(PaginatorContract $paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * Create a new URL window instance.
     *
     * 创建一个新的URL窗口实例
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @param  int  $onEachSide
     * @return array
     */
    public static function make(PaginatorContract $paginator, $onEachSide = 3)
    {
        //                           获取显示url的窗口
        return (new static($paginator))->get($onEachSide);
    }

    /**
     * Get the window of URLs to be shown.
     *
     * 获取显示url的窗口
     *
     * @param  int  $onEachSide
     * @return array
     */
    public function get($onEachSide = 3)
    {
        //                获取最后可用页面的页面数量
        if ($this->paginator->lastPage() < ($onEachSide * 2) + 6) {
            return $this->getSmallSlider();//获取url的滑动条没有足够的页面来滑动
        }
        //           创建一个URL滑动条链接
        return $this->getUrlSlider($onEachSide);
    }

    /**
     * Get the slider of URLs there are not enough pages to slide.
     *
     * 获取url的滑动条没有足够的页面来滑动
     *
     * @return array
     */
    protected function getSmallSlider()
    {
        return [
            //                       创建一系列的分页url         从paginator获取最后一页
            'first'  => $this->paginator->getUrlRange(1, $this->lastPage()),
            'slider' => null,
            'last'   => null,
        ];
    }

    /**
     * Create a URL slider links.
     *
     * 创建一个URL滑动条链接
     *
     * @param  int  $onEachSide
     * @return array
     */
    protected function getUrlSlider($onEachSide)
    {
        $window = $onEachSide * 2;
        //确定被呈现的底层paginator是否有页面显示
        if (! $this->hasPages()) {
            return ['first' => null, 'slider' => null, 'last' => null];
        }

        // If the current page is very close to the beginning of the page range, we will
        // just render the beginning of the page range, followed by the last 2 of the
        // links in this list, since we will not have room to create a full slider.
        //
        // 如果当前页面非常接近页面范围的开始，我们将只呈现页面范围的开始，然后是列表中的最后2个链接，因为我们将没有空间创建一个完整的滑动条
        //
        //     从paginator获取当前页面
        if ($this->currentPage() <= $window) {
            //           当太接近窗口的开始时，获取url的滑动条
            return $this->getSliderTooCloseToBeginning($window);
        }

        // If the current page is close to the ending of the page range we will just get
        // this first couple pages, followed by a larger window of these ending pages
        // since we're too close to the end of the list to create a full on slider.
        //
        // 如果当前页面接近页面范围的结束，我们将会得到第一对页面，然后是这些结束页面的一个较大的窗口，因为我们离列表的末尾太近了，无法创建一个完整的滑动条
        //
        //      从paginator获取当前页面          从paginator获取最后一页
        elseif ($this->currentPage() > ($this->lastPage() - $window)) {
            //             当太接近窗口结束时，获取url的滑动条
            return $this->getSliderTooCloseToEnding($window);
        }

        // If we have enough room on both sides of the current page to build a slider we
        // will surround it with both the beginning and ending caps, with this window
        // of pages in the middle providing a Google style sliding paginator setup.
        //
        // 如果我们在当前页面的两边都有足够的空间来构建一个滑动条，我们就会在它的开头和结束的地方设置一个滑动条，中间的页面窗口会提供一个Google风格的滑动paginator设置
        //
        //            当一个完整的滑块可以被制作时，获取url的滑动条
        return $this->getFullSlider($onEachSide);
    }

    /**
     * Get the slider of URLs when too close to beginning of window.
     *
     * 当太接近窗口的开始时，获取url的滑动条
     *
     * @param  int  $window
     * @return array
     */
    protected function getSliderTooCloseToBeginning($window)
    {
        return [
            'first' => $this->paginator->getUrlRange(1, $window + 2),//创建一系列的分页url
            'slider' => null,
            'last' => $this->getFinish(),//获取分页滑块的结束url
        ];
    }

    /**
     * Get the slider of URLs when too close to ending of window.
     *
     * 当太接近窗口结束时，获取url的滑动条
     *
     * @param  int  $window
     * @return array
     */
    protected function getSliderTooCloseToEnding($window)
    {
        $last = $this->paginator->getUrlRange(//创建一系列的分页url
            $this->lastPage() - ($window + 2),
            $this->lastPage()//从paginator获取最后一页
        );

        return [
            'first' => $this->getStart(),//获取分页滑块的起始url
            'slider' => null,
            'last' => $last,
        ];
    }

    /**
     * Get the slider of URLs when a full slider can be made.
     *
     * 当一个完整的滑块可以被制作时，获取url的滑动条
     *
     * @param  int  $onEachSide
     * @return array
     */
    protected function getFullSlider($onEachSide)
    {
        return [
            'first'  => $this->getStart(),//获取分页滑块的起始url
            'slider' => $this->getAdjacentUrlRange($onEachSide),//获取当前页面窗口的页面范围
            'last'   => $this->getFinish(),//获取分页滑块的结束url
        ];
    }

    /**
     * Get the page range for the current page window.
     *
     * 获取当前页面窗口的页面范围
     *
     * @param  int  $onEachSide
     * @return array
     */
    public function getAdjacentUrlRange($onEachSide)
    {
        return $this->paginator->getUrlRange(//创建一系列的分页url
            //从paginator获取当前页面
            $this->currentPage() - $onEachSide,
            $this->currentPage() + $onEachSide
        );
    }

    /**
     * Get the starting URLs of a pagination slider.
     *
     * 获取分页滑块的起始url
     *
     * @return array
     */
    public function getStart()
    {
        return $this->paginator->getUrlRange(1, 2);//创建一系列的分页url
    }

    /**
     * Get the ending URLs of a pagination slider.
     *
     * 获取分页滑块的结束url
     *
     * @return array
     */
    public function getFinish()
    {
        return $this->paginator->getUrlRange(//创建一系列的分页url
            $this->lastPage() - 1,
            $this->lastPage()//从paginator获取最后一页
        );
    }

    /**
     * Determine if the underlying paginator being presented has pages to show.
     *
     * 确定被呈现的底层paginator是否有页面显示
     *
     * @return bool
     */
    public function hasPages()
    {
        //                 从paginator获取最后一页
        return $this->paginator->lastPage() > 1;
    }

    /**
     * Get the current page from the paginator.
     *
     * 从paginator获取当前页面
     *
     * @return int
     */
    protected function currentPage()
    {
        //                     确定当前页面被分页的页面
        return $this->paginator->currentPage();
    }

    /**
     * Get the last page from the paginator.
     *
     * 从paginator获取最后一页
     *
     * @return int
     */
    protected function lastPage()
    {
        //                      获取最后可用页面的页面数量
        return $this->paginator->lastPage();
    }
}
