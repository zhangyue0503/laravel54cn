<?php

namespace Illuminate\Pagination;

use Closure;
use ArrayIterator;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Htmlable;

abstract class AbstractPaginator implements Htmlable
{
    /**
     * All of the items being paginated.
     *
     * 所有被分页的项
     *
     * @var \Illuminate\Support\Collection
     */
    protected $items;

    /**
     * The number of items to be shown per page.
     *
     * 每个页面显示的项目数量
     *
     * @var int
     */
    protected $perPage;

    /**
     * The current page being "viewed".
     *
     * 当前页面被“查看”
     *
     * @var int
     */
    protected $currentPage;

    /**
     * The base path to assign to all URLs.
     *
     * 为所有url分配的基本路径
     *
     * @var string
     */
    protected $path = '/';

    /**
     * The query parameters to add to all URLs.
     *
     * 添加到所有url的查询参数
     *
     * @var array
     */
    protected $query = [];

    /**
     * The URL fragment to add to all URLs.
     *
     * 添加到所有URL的URL片段
     *
     * @var string|null
     */
    protected $fragment;

    /**
     * The query string variable used to store the page.
     *
     * 用于存储页面的查询字符串变量
     *
     * @var string
     */
    protected $pageName = 'page';

    /**
     * The current page resolver callback.
     *
     * 当前页面解析器回调
     *
     * @var \Closure
     */
    protected static $currentPathResolver;

    /**
     * The current page resolver callback.
     *
     * 当前页面解析器回调
     *
     * @var \Closure
     */
    protected static $currentPageResolver;

    /**
     * The view factory resolver callback.
     *
     * 视图工厂解析器回调
     *
     * @var \Closure
     */
    protected static $viewFactoryResolver;

    /**
     * The default pagination view.
     *
     * 默认的分页视图
     *
     * @var string
     */
    public static $defaultView = 'pagination::default';

    /**
     * The default "simple" pagination view.
     *
     * 默认的“简单”分页视图
     *
     * @var string
     */
    public static $defaultSimpleView = 'pagination::simple-default';

    /**
     * Determine if the given value is a valid page number.
     *
     * 确定给定值是否为有效的页码
     *
     * @param  int  $page
     * @return bool
     */
    protected function isValidPageNumber($page)
    {
        return $page >= 1 && filter_var($page, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Get the URL for the previous page.
     *
     * 获取前一页的URL
     *
     * @return string|null
     */
    public function previousPageUrl()
    {
        //得到当前页面
        if ($this->currentPage() > 1) {
            //获取给定页面编号的URL
            return $this->url($this->currentPage() - 1);
        }
    }

    /**
     * Create a range of pagination URLs.
     *
     * 创建一系列的分页url
     *
     * @param  int  $start
     * @param  int  $end
     * @return array
     */
    public function getUrlRange($start, $end)
    {
        //                                   在每个项目上运行关联映射
        return collect(range($start, $end))->mapWithKeys(function ($page) {
            //                    获取给定页面编号的URL
            return [$page => $this->url($page)];
        })->all();// 获取集合中的所有项目
    }

    /**
     * Get the URL for a given page number.
     *
     * 获取给定页面编号的URL
     *
     * @param  int  $page
     * @return string
     */
    public function url($page)
    {
        if ($page <= 0) {
            $page = 1;
        }

        // If we have any extra query string key / value pairs that need to be added
        // onto the URL, we will put them in query string form and then attach it
        // to the URL. This allows for extra information like sortings storage.
        //
        // 如果我们有额外的查询字符串键/值对需要添加到URL中，我们将把它们放在查询字符串形式中，然后将其附加到URL
        // 这就允许额外的信息，比如分类存储
        //
        $parameters = [$this->pageName => $page];

        if (count($this->query) > 0) {
            $parameters = array_merge($this->query, $parameters);
        }

        return $this->path
                        .(Str::contains($this->path, '?') ? '&' : '?')//确定一个给定的字符串包含另一个字符串
                        .http_build_query($parameters, '', '&')
                        .$this->buildFragment();//构建URL的完整片段部分
    }

    /**
     * Get / set the URL fragment to be appended to URLs.
     *
     * 将URL片段添加到URL中
     *
     * @param  string|null  $fragment
     * @return $this|string|null
     */
    public function fragment($fragment = null)
    {
        if (is_null($fragment)) {
            return $this->fragment;
        }

        $this->fragment = $fragment;

        return $this;
    }

    /**
     * Add a set of query string values to the paginator.
     *
     * 向paginator添加一组查询字符串值
     *
     * @param  array|string  $key
     * @param  string|null  $value
     * @return $this
     */
    public function appends($key, $value = null)
    {
        if (is_array($key)) {
            return $this->appendArray($key);//添加一个查询字符串值数组
        }

        return $this->addQuery($key, $value);//向paginator添加一个查询字符串值
    }

    /**
     * Add an array of query string values.
     *
     * 添加一个查询字符串值数组
     *
     * @param  array  $keys
     * @return $this
     */
    protected function appendArray(array $keys)
    {
        foreach ($keys as $key => $value) {
            $this->addQuery($key, $value);//向paginator添加一个查询字符串值
        }

        return $this;
    }

    /**
     * Add a query string value to the paginator.
     *
     * 向paginator添加一个查询字符串值
     *
     * @param  string  $key
     * @param  string  $value
     * @return $this
     */
    protected function addQuery($key, $value)
    {
        if ($key !== $this->pageName) {
            $this->query[$key] = $value;
        }

        return $this;
    }

    /**
     * Build the full fragment portion of a URL.
     *
     * 构建URL的完整片段部分
     *
     * @return string
     */
    protected function buildFragment()
    {
        return $this->fragment ? '#'.$this->fragment : '';
    }

    /**
     * Get the slice of items being paginated.
     *
     * 获取被分页的项
     *
     * @return array
     */
    public function items()
    {
        return $this->items->all();//获取集合中的所有项目
    }

    /**
     * Get the number of the first item in the slice.
     *
     * 得到切片中第一个项目的数量
     *
     * @return int
     */
    public function firstItem()
    {
        return count($this->items) > 0 ? ($this->currentPage - 1) * $this->perPage + 1 : null;
    }

    /**
     * Get the number of the last item in the slice.
     *
     * 获取切片中最后一个项目的数量
     *
     * @return int
     */
    public function lastItem()
    {
        //                         得到切片中第一个项目的数量      获取当前页面的项目数量
        return count($this->items) > 0 ? $this->firstItem() + $this->count() - 1 : null;
    }

    /**
     * Get the number of items shown per page.
     *
     * 获取每页显示的项目数量
     *
     * @return int
     */
    public function perPage()
    {
        return $this->perPage;
    }

    /**
     * Determine if there are enough items to split into multiple pages.
     *
     * 确定是否有足够的条目可以分成多个页面
     *
     * @return bool
     */
    public function hasPages()
    {
        //       得到当前页面                   确定数据源中是否有更多项目
        return $this->currentPage() != 1 || $this->hasMorePages();
    }

    /**
     * Determine if the paginator is on the first page.
     *
     * 确定页面上的paginator是否在第一页
     *
     * @return bool
     */
    public function onFirstPage()
    {
        //          得到当前页面
        return $this->currentPage() <= 1;
    }

    /**
     * Get the current page.
     *
     * 得到当前页面
     *
     * @return int
     */
    public function currentPage()
    {
        return $this->currentPage;
    }

    /**
     * Get the query string variable used to store the page.
     *
     * 获取用于存储页面的查询字符串变量
     *
     * @return string
     */
    public function getPageName()
    {
        return $this->pageName;
    }

    /**
     * Set the query string variable used to store the page.
     *
     * 设置用于存储页面的查询字符串变量
     *
     * @param  string  $name
     * @return $this
     */
    public function setPageName($name)
    {
        $this->pageName = $name;

        return $this;
    }

    /**
     * Set the base path to assign to all URLs.
     *
     * 设置为所有url分配的基本路径
     *
     * @param  string  $path
     * @return $this
     */
    public function withPath($path)
    {
        //设置为所有url分配的基本路径
        return $this->setPath($path);
    }

    /**
     * Set the base path to assign to all URLs.
     *
     * 设置为所有url分配的基本路径
     *
     * @param  string  $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Resolve the current request path or return the default value.
     *
     * 解决当前请求路径或返回默认值
     *
     * @param  string  $default
     * @return string
     */
    public static function resolveCurrentPath($default = '/')
    {
        if (isset(static::$currentPathResolver)) {
            return call_user_func(static::$currentPathResolver);
        }

        return $default;
    }

    /**
     * Set the current request path resolver callback.
     *
     * 设置当前请求路径解析器回调
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public static function currentPathResolver(Closure $resolver)
    {
        static::$currentPathResolver = $resolver;
    }

    /**
     * Resolve the current page or return the default value.
     *
     * 解析当前页或返回默认值
     *
     * @param  string  $pageName
     * @param  int  $default
     * @return int
     */
    public static function resolveCurrentPage($pageName = 'page', $default = 1)
    {
        if (isset(static::$currentPageResolver)) {
            return call_user_func(static::$currentPageResolver, $pageName);
        }

        return $default;
    }

    /**
     * Set the current page resolver callback.
     *
     * 设置当前页面解析器回调
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public static function currentPageResolver(Closure $resolver)
    {
        static::$currentPageResolver = $resolver;
    }

    /**
     * Get an instance of the view factory from the resolver.
     *
     * 从解析器获取视图工厂的实例
     *
     * @return \Illuminate\Contracts\View\Factory
     */
    public static function viewFactory()
    {
        return call_user_func(static::$viewFactoryResolver);
    }

    /**
     * Set the view factory resolver callback.
     *
     * 设置视图工厂解析器回调
     *
     * @param  \Closure  $resolver
     * @return void
     */
    public static function viewFactoryResolver(Closure $resolver)
    {
        static::$viewFactoryResolver = $resolver;
    }

    /**
     * Set the default pagination view.
     *
     * 设置默认的分页视图
     *
     * @param  string  $view
     * @return void
     */
    public static function defaultView($view)
    {
        static::$defaultView = $view;
    }

    /**
     * Set the default "simple" pagination view.
     *
     * 设置默认的“简单”分页视图
     *
     * @param  string  $view
     * @return void
     */
    public static function defaultSimpleView($view)
    {
        static::$defaultSimpleView = $view;
    }

    /**
     * Get an iterator for the items.
     *
     * 获取项目的迭代器
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        //                            获取集合中的所有项目
        return new ArrayIterator($this->items->all());
    }

    /**
     * Determine if the list of items is empty or not.
     *
     * 确定条目的列表是否为空
     *
     * @return bool
     */
    public function isEmpty()
    {
        //                   确定集合是否为空
        return $this->items->isEmpty();
    }

    /**
     * Get the number of items for the current page.
     *
     * 获取当前页面的项目数量
     *
     * @return int
     */
    public function count()
    {
        //             计数集合中的项目数
        return $this->items->count();
    }

    /**
     * Get the paginator's underlying collection.
     *
     * 获取paginator的底层集合
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCollection()
    {
        return $this->items;
    }

    /**
     * Set the paginator's underlying collection.
     *
     * 设置paginator的底层集合
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @return $this
     */
    public function setCollection(Collection $collection)
    {
        $this->items = $collection;

        return $this;
    }

    /**
     * Determine if the given item exists.
     *
     * 确定给定的项是否存在
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        //               通过键确定集合中是否存在项
        return $this->items->has($key);
    }

    /**
     * Get the item at the given offset.
     *
     * 在给定的偏移量中获得该项
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        //                按key从集合中获取项
        return $this->items->get($key);
    }

    /**
     * Set the item at the given offset.
     *
     * 在给定的偏移量上设置项目
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        //     按项在集合中放置项
        $this->items->put($key, $value);
    }

    /**
     * Unset the item at the given key.
     *
     * 在给定的键上取消该项
     *
     * @param  mixed  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        //          从集合中移除项目
        $this->items->forget($key);
    }

    /**
     * Render the contents of the paginator to HTML.
     *
     * 将paginator的内容呈现给HTML
     *
     * @return string
     */
    public function toHtml()
    {
        //                  使用给定的视图呈现paginator
        return (string) $this->render();
    }

    /**
     * Make dynamic calls into the collection.
     *
     * 对集合进行动态调用
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        //        获取paginator的底层集合
        return $this->getCollection()->$method(...$parameters);
    }

    /**
     * Render the contents of the paginator when casting to string.
     *
     * 在字符串转换为字符串时，渲染页面的内容
     *
     * @return string
     */
    public function __toString()
    {
        //                  使用给定的视图呈现paginator
        return (string) $this->render();
    }
}
