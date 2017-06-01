<?php

namespace Illuminate\View;

use Exception;
use Throwable;
use ArrayAccess;
use BadMethodCallException;
use Illuminate\Support\Str;
use Illuminate\Support\MessageBag;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\View\Engines\EngineInterface;
use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Contracts\View\View as ViewContract;

class View implements ArrayAccess, ViewContract
{
    /**
     * The view factory instance.
     *
     * 视图工厂实例
     *
     * @var \Illuminate\View\Factory
     */
    protected $factory;

    /**
     * The engine implementation.
     *
     * 引擎实现
     *
     * @var \Illuminate\View\Engines\EngineInterface
     */
    protected $engine;

    /**
     * The name of the view.
     *
     * 视图的名称
     *
     * @var string
     */
    protected $view;

    /**
     * The array of view data.
     *
     * 视图数据数组
     *
     * @var array
     */
    protected $data;

    /**
     * The path to the view file.
     *
     * 视图文件的路径
     *
     * @var string
     */
    protected $path;

    /**
     * Create a new view instance.
     *
     * 创建一个新的视图实例
     *
     * @param  \Illuminate\View\Factory  $factory
     * @param  \Illuminate\View\Engines\EngineInterface  $engine
     * @param  string  $view
     * @param  string  $path
     * @param  mixed  $data
     * @return void
     */
    public function __construct(Factory $factory, EngineInterface $engine, $view, $path, $data = [])
    {
        $this->view = $view;
        $this->path = $path;
        $this->engine = $engine;
        $this->factory = $factory;
        //                                      获取数组实例
        $this->data = $data instanceof Arrayable ? $data->toArray() : (array) $data;
    }

    /**
     * Get the string contents of the view.
     *
     * 获取视图的字符串内容
     *
     * @param  callable|null  $callback
     * @return string
     *
     * @throws \Throwable
     */
    public function render(callable $callback = null)
    {
        try {
            //获取视图实例的内容
            $contents = $this->renderContents();

            $response = isset($callback) ? call_user_func($callback, $this, $contents) : null;

            // Once we have the contents of the view, we will flush the sections if we are
            // done rendering all views so that there is nothing left hanging over when
            // another view gets rendered in the future by the application developer.
            //
            // 一旦我们有了视图的内容，我们就会刷新这些部分，如果我们已经完成了所有视图，这样，当应用程序开发人员在将来呈现另一个视图时，就不会有任何东西挂在上面了
            //
            //            如果完成渲染，就刷新所有的部分内容
            $this->factory->flushStateIfDoneRendering();

            return ! is_null($response) ? $response : $contents;
        } catch (Exception $e) {
            //            将所有的工厂状态都像分段和堆栈一样刷新
            $this->factory->flushState();

            throw $e;
        } catch (Throwable $e) {
            $this->factory->flushState();

            throw $e;
        }
    }

    /**
     * Get the contents of the view instance.
     *
     * 获取视图实例的内容
     *
     * @return string
     */
    protected function renderContents()
    {
        // We will keep track of the amount of views being rendered so we can flush
        // the section after the complete rendering operation is done. This will
        // clear out the sections for any separate views that may be rendered.
        //
        // 我们将跟踪正在呈现的视图的数量，这样我们就可以在完成整个呈现操作之后刷新部分
        // 这将清除可能呈现的任何单独视图的部分
        //
        //              增加呈现计数器
        $this->factory->incrementRender();
        //为给定的视图调用composer
        $this->factory->callComposer($this);
        //获取视图的评估内容
        $contents = $this->getContents();

        // Once we've finished rendering the view, we'll decrement the render count
        // so that each sections get flushed out next time a view is created and
        // no old sections are staying around in the memory of an environment.
        //
        // 一旦我们完成了视图的呈现，我们将减少渲染计数，以便在下一次创建视图时，每个部分都被清空，并且没有旧的部分在环境的内存中停留
        //
        //                衰减呈现计数器
        $this->factory->decrementRender();

        return $contents;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * 获取视图的评估内容
     *
     * @return string
     */
    protected function getContents()
    {
        //             获取视图的评估内容              将数据绑定到视图实例
        return $this->engine->get($this->path, $this->gatherData());
    }

    /**
     * Get the data bound to the view instance.
     *
     * 将数据绑定到视图实例
     *
     * @return array
     */
    protected function gatherData()
    {
        //                                获取环境的所有共享数据
        $data = array_merge($this->factory->getShared(), $this->data);

        foreach ($data as $key => $value) {
            if ($value instanceof Renderable) {
                //                  获取对象的评价内容
                $data[$key] = $value->render();
            }
        }

        return $data;
    }

    /**
     * Get the sections of the rendered view.
     *
     * 获取呈现视图的部分
     *
     * @return array
     */
    public function renderSections()
    {
        //获取视图的字符串内容
        return $this->render(function () {
            //                  获取全部的分段
            return $this->factory->getSections();
        });
    }

    /**
     * Add a piece of data to the view.
     *
     * 将数据添加到视图中
     *
     * @param  string|array  $key
     * @param  mixed   $value
     * @return $this
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Add a view instance to the view data.
     *
     * 将视图实例添加到视图数据
     *
     * @param  string  $key
     * @param  string  $view
     * @param  array   $data
     * @return $this
     */
    public function nest($key, $view, array $data = [])
    {
        //        将数据添加到视图中           获取给定视图的得到视图内容
        return $this->with($key, $this->factory->make($view, $data));
    }

    /**
     * Add validation errors to the view.
     *
     * 向视图添加验证错误
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array  $provider
     * @return $this
     */
    public function withErrors($provider)
    {
        //将数据添加到视图中             将给定的消息提供程序格式化为一个消息包
        $this->with('errors', $this->formatErrors($provider));

        return $this;
    }

    /**
     * Format the given message provider into a MessageBag.
     *
     * 将给定的消息提供程序格式化为一个消息包
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array  $provider
     * @return \Illuminate\Support\MessageBag
     */
    protected function formatErrors($provider)
    {
        return $provider instanceof MessageProvider
        //                 从实例中获取消息
                        ? $provider->getMessageBag() : new MessageBag((array) $provider);
    }

    /**
     * Get the name of the view.
     *
     * 获取视图的名称
     *
     * @return string
     */
    public function name()
    {
        //获取视图的名称
        return $this->getName();
    }

    /**
     * Get the name of the view.
     *
     * 获取视图的名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->view;
    }

    /**
     * Get the array of view data.
     *
     * 获取视图数据数组
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the path to the view file.
     *
     * 获取到视图文件的路径
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the path to the view.
     *
     * 设置视图的路径
     *
     * @param  string  $path
     * @return void
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Get the view factory instance.
     *
     * 获取视图工厂实例
     *
     * @return \Illuminate\View\Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Get the view's rendering engine.
     *
     * 获取视图的呈现引擎
     *
     * @return \Illuminate\View\Engines\EngineInterface
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * Determine if a piece of data is bound.
     *
     * 确定一个数据是否被绑定
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get a piece of bound data to the view.
     *
     * 在视图中获取一个绑定的数据
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->data[$key];
    }

    /**
     * Set a piece of data on the view.
     *
     * 在视图上设置一个数据
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        //将数据添加到视图中
        $this->with($key, $value);
    }

    /**
     * Unset a piece of data from the view.
     *
     * 从视图中打开一个数据
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Get a piece of data from the view.
     *
     * 从视图中获取一段数据
     *
     * @param  string  $key
     * @return mixed
     */
    public function &__get($key)
    {
        return $this->data[$key];
    }

    /**
     * Set a piece of data on the view.
     *
     * 在视图上设置一个数据
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        //将数据添加到视图中
        $this->with($key, $value);
    }

    /**
     * Check if a piece of data is bound to the view.
     *
     * 检查一个数据是否绑定到视图
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Remove a piece of bound data from the view.
     *
     * 从视图中删除一个绑定的数据
     *
     * @param  string  $key
     * @return bool
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Dynamically bind parameters to the view.
     *
     * 动态绑定参数到视图
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return \Illuminate\View\View
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        //确定给定的子字符串是否属于给定的字符串
        if (! Str::startsWith($method, 'with')) {
            throw new BadMethodCallException("Method [$method] does not exist on view.");
        }
        //将数据添加到视图中        将字符串转换为蛇形命名
        return $this->with(Str::snake(substr($method, 4)), $parameters[0]);
    }

    /**
     * Get the string contents of the view.
     *
     * 获取视图的字符串内容
     *
     * @return string
     */
    public function __toString()
    {
        //获取视图的字符串内容
        return $this->render();
    }
}
