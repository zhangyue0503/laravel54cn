<?php

namespace Illuminate\View;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory as FactoryContract;

class Factory implements FactoryContract
{
    use Concerns\ManagesComponents,
        Concerns\ManagesEvents,
        Concerns\ManagesLayouts,
        Concerns\ManagesLoops,
        Concerns\ManagesStacks,
        Concerns\ManagesTranslations;

    /**
     * The engine implementation.
     *
     * 引擎实现
     *
     * @var \Illuminate\View\Engines\EngineResolver
     */
    protected $engines;

    /**
     * The view finder implementation.
     *
     * 视图查找器的实现
     *
     * @var \Illuminate\View\ViewFinderInterface
     */
    protected $finder;

    /**
     * The event dispatcher instance.
     *
     * 事件调度器实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The IoC container instance.
     *
     * IoC容器实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Data that should be available to all templates.
     *
     * 应该对所有模板都可用的数据
     *
     * @var array
     */
    protected $shared = [];

    /**
     * The extension to engine bindings.
     *
     * 引擎绑定的扩展
     *
     * @var array
     */
    protected $extensions = [
        'blade.php' => 'blade',
        'php' => 'php',
        'css' => 'file',
    ];

    /**
     * The view composer events.
     *
     * 视图composer事件
     *
     * @var array
     */
    protected $composers = [];

    /**
     * The number of active rendering operations.
     *
     * 活动呈现操作的数量
     *
     * @var int
     */
    protected $renderCount = 0;

    /**
     * Create a new view factory instance.
     *
     * 创建一个新的视图工厂实例
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $engines
     * @param  \Illuminate\View\ViewFinderInterface  $finder
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(EngineResolver $engines, ViewFinderInterface $finder, Dispatcher $events)
    {
        $this->finder = $finder;
        $this->events = $events;
        $this->engines = $engines;
        //向环境中添加一段共享数据
        $this->share('__env', $this);
    }

    /**
     * Get the evaluated view contents for the given view.
     *
     * 获取给定视图的评估视图内容
     *
     * @param  string  $path
     * @param  array   $data
     * @param  array   $mergeData
     * @return \Illuminate\Contracts\View\View
     */
    public function file($path, $data = [], $mergeData = [])
    {
        //                                将给定的数据解析为原始数组
        $data = array_merge($mergeData, $this->parseData($data));
        //用给定的值调用给定的闭包，然后返回值  从给定的参数中创建一个新的视图实例
        return tap($this->viewInstance($path, $path, $data), function ($view) {
            //为给定的视图调用创建者
            $this->callCreator($view);
        });
    }

    /**
     * Get the evaluated view contents for the given view.
     *
	 * 获取给定视图的得到视图内容
	 *
     * @param  string  $view
     * @param  array   $data
     * @param  array   $mergeData
     * @return \Illuminate\Contracts\View\View
     */
    public function make($view, $data = [], $mergeData = [])
    {
        //                 获得视图的完全限定位置
        $path = $this->finder->find(
            //            正常的视图名称
            $view = $this->normalizeName($view)
        );

        // Next, we will create the view instance and call the view creator for the view
        // which can set any data, etc. Then we will return the view instance back to
        // the caller for rendering or performing other view manipulations on this.
        //
        // 接下来，我们将创建视图实例，并调用视图创建器来设置任何数据，等等
        // 然后我们将把视图实例返回给调用者，以呈现或执行其他视图操作
        //
        //                                  将给定的数据解析为原始数组
        $data = array_merge($mergeData, $this->parseData($data));
        //用给定的值调用给定的闭包，然后返回值  从给定的参数中创建一个新的视图实例
        return tap($this->viewInstance($view, $path, $data), function ($view) {
            //为给定的视图调用创建者
            $this->callCreator($view);
        });
    }

    /**
     * Get the rendered contents of a partial from a loop.
     *
     * 从一个循环中获取部分的呈现内容
     *
     * @param  string  $view
     * @param  array   $data
     * @param  string  $iterator
     * @param  string  $empty
     * @return string
     */
    public function renderEach($view, $data, $iterator, $empty = 'raw|')
    {
        $result = '';

        // If is actually data in the array, we will loop through the data and append
        // an instance of the partial view to the final result HTML passing in the
        // iterated value of this data array, allowing the views to access them.
        //
        // 如果是数组中的数据，我们将循环遍历数据，并将部分视图的一个实例附加到这个数据数组的迭代值的最终结果HTML中，从而允许视图访问它们
        //
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $result .= $this->make(//获取给定视图的得到视图内容
                    $view, ['key' => $key, $iterator => $value]
                )->render();//获取对象的评估内容
            }
        }

        // If there is no data in the array, we will render the contents of the empty
        // view. Alternatively, the "empty view" could be a raw string that begins
        // with "raw|" for convenience and to let this know that it is a string.
        //
        // 如果数组中没有数据，我们将呈现空视图的内容。或者，“空视图”可以是一个原始字符串，以“原始”为方便，并让它知道它是一个字符串
        //
        else {
            //            确定给定的子字符串是否属于给定的字符串
            $result = Str::startsWith($empty, 'raw|')
                        ? substr($empty, 4)
                //获取给定视图的得到视图内容     获取对象的评估内容
                        : $this->make($empty)->render();
        }

        return $result;
    }

    /**
     * Normalize a view name.
     *
     * 正常的视图名称
     *
     * @param  string $name
     * @return string
     */
    protected function normalizeName($name)
    {
        //             规范化给定事件名称
        return ViewName::normalize($name);
    }

    /**
     * Parse the given data into a raw array.
     *
     * 将给定的数据解析为原始数组
     *
     * @param  mixed  $data
     * @return array
     */
    protected function parseData($data)
    {
        //                                     获取数组实例
        return $data instanceof Arrayable ? $data->toArray() : $data;
    }

    /**
     * Create a new view instance from the given arguments.
     *
     * 从给定的参数中创建一个新的视图实例
     *
     * @param  string  $view
     * @param  string  $path
     * @param  array  $data
     * @return \Illuminate\Contracts\View\View
     */
    protected function viewInstance($view, $path, $data)
    {
        //                           为给定的路径获取适当的视图引擎
        return new View($this, $this->getEngineFromPath($path), $view, $path, $data);
    }

    /**
     * Determine if a given view exists.
     *
     * 确定给定的视图是否存在
     *
     * @param  string  $view
     * @return bool
     */
    public function exists($view)
    {
        try {
            //获得视图的完全限定位置
            $this->finder->find($view);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the appropriate view engine for the given path.
     *
     * 为给定的路径获取适当的视图引擎
     *
     * @param  string  $path
     * @return \Illuminate\View\Engines\EngineInterface
     *
     * @throws \InvalidArgumentException
     */
    public function getEngineFromPath($path)
    {
        //                     获取视图文件使用的扩展名
        if (! $extension = $this->getExtension($path)) {
            throw new InvalidArgumentException("Unrecognized extension in file: $path");
        }

        $engine = $this->extensions[$extension];
        //                   按名称命名引擎实例
        return $this->engines->resolve($engine);
    }

    /**
     * Get the extension used by the view file.
     *
     * 获取视图文件使用的扩展名
     *
     * @param  string  $path
     * @return string
     */
    protected function getExtension($path)
    {
        $extensions = array_keys($this->extensions);
        //通过给定的真值测试返回数组中的第一个元素
        return Arr::first($extensions, function ($value) use ($path) {
            //确定给定的字符串的结束是否是给定的子字符串
            return Str::endsWith($path, '.'.$value);
        });
    }

    /**
     * Add a piece of shared data to the environment.
     *
     * 向环境中添加一段共享数据
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function share($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            $this->shared[$key] = $value;
        }

        return $value;
    }

    /**
     * Increment the rendering counter.
     *
     * 增加呈现计数器
     *
     * @return void
     */
    public function incrementRender()
    {
        $this->renderCount++;
    }

    /**
     * Decrement the rendering counter.
     *
     * 衰减呈现计数器
     *
     * @return void
     */
    public function decrementRender()
    {
        $this->renderCount--;
    }

    /**
     * Check if there are no active render operations.
     *
     * 检查是否没有激活的呈现操作
     *
     * @return bool
     */
    public function doneRendering()
    {
        return $this->renderCount == 0;
    }

    /**
     * Add a location to the array of view locations.
     *
     * 在视图位置的数组中添加一个位置
     *
     * @param  string  $location
     * @return void
     */
    public function addLocation($location)
    {
        //在finder中添加一个位置
        $this->finder->addLocation($location);
    }

    /**
     * Add a new namespace to the loader.
     *
     * 向加载程序添加一个新的名称空间
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function addNamespace($namespace, $hints)
    {
        //向finder中添加一个名称空间提示
        $this->finder->addNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Prepend a new namespace to the loader.
     *
     * 为加载程序预先准备一个新的名称空间
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function prependNamespace($namespace, $hints)
    {
        //为查找器预先准备一个名称空间提示
        $this->finder->prependNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Replace the namespace hints for the given namespace.
     *
     * 替换给定名称空间的名称空间提示
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function replaceNamespace($namespace, $hints)
    {
        //替换给定名称空间的名称空间提示
        $this->finder->replaceNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Register a valid view extension and its engine.
     *
     * 注册一个有效的视图扩展和它的引擎
     *
     * @param  string    $extension
     * @param  string    $engine
     * @param  \Closure  $resolver
     * @return void
     */
    public function addExtension($extension, $engine, $resolver = null)
    {
        //在finder中添加一个有效的视图扩展名
        $this->finder->addExtension($extension);

        if (isset($resolver)) {
            //引擎字符串通常对应于一个文件扩展名
            $this->engines->register($engine, $resolver);
        }

        unset($this->extensions[$extension]);

        $this->extensions = array_merge([$extension => $engine], $this->extensions);
    }

    /**
     * Flush all of the factory state like sections and stacks.
     *
     * 将所有的工厂状态都像分段和堆栈一样刷新
     *
     * @return void
     */
    public function flushState()
    {
        $this->renderCount = 0;

        $this->flushSections();//刷新所有的部分
        $this->flushStacks();//把所有的堆栈都刷新
    }

    /**
     * Flush all of the section contents if done rendering.
     *
     * 如果完成渲染，就刷新所有的部分内容
     *
     * @return void
     */
    public function flushStateIfDoneRendering()
    {
        //检查是否没有激活的呈现操作
        if ($this->doneRendering()) {
            $this->flushState();//将所有的工厂状态都像分段和堆栈一样刷新
        }
    }

    /**
     * Get the extension to engine bindings.
     *
     * 获取引擎绑定的扩展名
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Get the engine resolver instance.
     *
     * 获取引擎解析器实例
     *
     * @return \Illuminate\View\Engines\EngineResolver
     */
    public function getEngineResolver()
    {
        return $this->engines;
    }

    /**
     * Get the view finder instance.
     *
     * 获取视图查找器实例
     *
     * @return \Illuminate\View\ViewFinderInterface
     */
    public function getFinder()
    {
        return $this->finder;
    }

    /**
     * Set the view finder instance.
     *
     * 设置视图查找器实例
     *
     * @param  \Illuminate\View\ViewFinderInterface  $finder
     * @return void
     */
    public function setFinder(ViewFinderInterface $finder)
    {
        $this->finder = $finder;
    }

    /**
     * Flush the cache of views located by the finder.
     *
     * 刷新查找器所在的视图的缓存
     *
     * @return void
     */
    public function flushFinderCache()
    {
        //获取视图查找器实例    刷新位置视图的缓存
        $this->getFinder()->flush();
    }

    /**
     * Get the event dispatcher instance.
     *
     * 获取事件调度程序实例
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance.
     *
     * 设置事件调度程序实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Get the IoC container instance.
     *
     * 获取IoC容器实例
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the IoC container instance.
     *
     * 设置IoC容器实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get an item from the shared data.
     *
     * 从共享数据中获取一个项目
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function shared($key, $default = null)
    {
        //使用“点”符号从数组中获取一个项
        return Arr::get($this->shared, $key, $default);
    }

    /**
     * Get all of the shared data for the environment.
     *
     * 获取环境的所有共享数据
     *
     * @return array
     */
    public function getShared()
    {
        return $this->shared;
    }
}
