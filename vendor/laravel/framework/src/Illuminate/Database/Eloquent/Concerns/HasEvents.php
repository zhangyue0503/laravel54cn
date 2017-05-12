<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Contracts\Events\Dispatcher;

trait HasEvents
{
    /**
     * The event map for the model.
     *
     * 模型的事件映射
     *
     * Allows for object-based events for native Eloquent events.
     *
     * 允许基于对象的事件来进行原生的Eloquent件
     *
     * @var array
     */
    protected $events = [];

    /**
     * User exposed observable events.
     *
     * 用户暴露的可观察事件
     *
     * These are extra user-defined events observers may subscribe to.
     *
     * 这些是额外的用户定义事件，观察者可以订阅
     *
     * @var array
     */
    protected $observables = [];

    /**
     * Register an observer with the Model.
     *
     * 用模型注册一个观察者
     *
     * @param  object|string  $class
     * @return void
     */
    public static function observe($class)
    {
        $instance = new static;

        $className = is_string($class) ? $class : get_class($class);

        // When registering a model observer, we will spin through the possible events
        // and determine if this observer has that method. If it does, we will hook
        // it into the model's event system, making it convenient to watch these.
        //
        // 在注册一个模型观察者时，我们将对可能的事件进行旋转，并确定这个观察者是否有这个方法
        // 如果是这样，我们将把它连接到模型的事件系统中，这样便于观察
        //
        //              获取可观察到的事件名称
        foreach ($instance->getObservableEvents() as $event) {
            if (method_exists($class, $event)) {
                //向调度程序注册一个模型事件
                static::registerModelEvent($event, $className.'@'.$event);
            }
        }
    }

    /**
     * Get the observable event names.
     *
     * 获取可观察到的事件名称
     *
     * @return array
     */
    public function getObservableEvents()
    {
        return array_merge(
            [
                'creating', 'created', 'updating', 'updated',
                'deleting', 'deleted', 'saving', 'saved',
                'restoring', 'restored',
            ],
            $this->observables
        );
    }

    /**
     * Set the observable event names.
     *
     * 设置可观察的事件名
     *
     * @param  array  $observables
     * @return $this
     */
    public function setObservableEvents(array $observables)
    {
        $this->observables = $observables;

        return $this;
    }

    /**
     * Add an observable event name.
     *
     * 添加一个可观察的事件名
     *
     * @param  array|mixed  $observables
     * @return void
     */
    public function addObservableEvents($observables)
    {
        $this->observables = array_unique(array_merge(
            $this->observables, is_array($observables) ? $observables : func_get_args()
        ));
    }

    /**
     * Remove an observable event name.
     *
     * 删除一个可观察的事件名
     *
     * @param  array|mixed  $observables
     * @return void
     */
    public function removeObservableEvents($observables)
    {
        $this->observables = array_diff(
            $this->observables, is_array($observables) ? $observables : func_get_args()
        );
    }

    /**
     * Register a model event with the dispatcher.
     *
     * 向调度程序注册一个模型事件
     *
     * @param  string  $event
     * @param  \Closure|string  $callback
     * @return void
     */
    protected static function registerModelEvent($event, $callback)
    {
        if (isset(static::$dispatcher)) {
            $name = static::class;

            static::$dispatcher->listen("eloquent.{$event}: {$name}", $callback);
        }
    }

    /**
     * Fire the given event for the model.
     *
     * 触发模型的给定的事件
     *
     * @param  string  $event
     * @param  bool  $halt
     * @return mixed
     */
    protected function fireModelEvent($event, $halt = true)
    {
        if (! isset(static::$dispatcher)) {
            return true;
        }

        // First, we will get the proper method to call on the event dispatcher, and then we
        // will attempt to fire a custom, object based event for the given event. If that
        // returns a result we can return that result, or we'll call the string events.
        //
        // 首先，我们将获得适当的方法来调用事件分配器，然后我们将尝试为给定的事件触发一个定制的、基于对象的事件。如果返回一个结果，我们可以返回那个结果，或者我们将调用字符串事件
        //
        $method = $halt ? 'until' : 'fire';
        //               为给定事件触发定制的模型事件
        $result = $this->fireCustomModelEvent($event, $method);

        if ($result === false) {
            return false;
        }

        return ! empty($result) ? $result : static::$dispatcher->{$method}(
            "eloquent.{$event}: ".static::class, $this
        );
    }

    /**
     * Fire a custom model event for the given event.
     *
     * 为给定事件触发定制的模型事件
     *
     * @param  string  $event
     * @param  string  $method
     * @return mixed|null
     */
    protected function fireCustomModelEvent($event, $method)
    {
        if (! isset($this->events[$event])) {
            return;
        }

        $result = static::$dispatcher->$method(new $this->events[$event]($this));

        if (! is_null($result)) {
            return $result;
        }
    }

    /**
     * Register a saving model event with the dispatcher.
     *
     * 使用分派器注册一个保存模型事件
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function saving($callback)
    {
        //向调度程序注册一个模型事件
        static::registerModelEvent('saving', $callback);
    }

    /**
     * Register a saved model event with the dispatcher.
     *
     * 使用分派器注册一个保存的模型事件
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function saved($callback)
    {
        //向调度程序注册一个模型事件
        static::registerModelEvent('saved', $callback);
    }

    /**
     * Register an updating model event with the dispatcher.
     *
     * 使用分派器注册一个更新模型事件
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function updating($callback)
    {
        //向调度程序注册一个模型事件
        static::registerModelEvent('updating', $callback);
    }

    /**
     * Register an updated model event with the dispatcher.
     *
     * 使用分派器注册一个更新的模型事件
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function updated($callback)
    {
        //向调度程序注册一个模型事件
        static::registerModelEvent('updated', $callback);
    }

    /**
     * Register a creating model event with the dispatcher.
     *
     * 使用分派器注册一个创建模型事件
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function creating($callback)
    {
        //向调度程序注册一个模型事件
        static::registerModelEvent('creating', $callback);
    }

    /**
     * Register a created model event with the dispatcher.
     *
     * 使用分派器注册一个创建的模型事件
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function created($callback)
    {
        //向调度程序注册一个模型事件
        static::registerModelEvent('created', $callback);
    }

    /**
     * Register a deleting model event with the dispatcher.
     *
     * 使用分派器注册一个删除模型事件
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function deleting($callback)
    {
        //向调度程序注册一个模型事件
        static::registerModelEvent('deleting', $callback);
    }

    /**
     * Register a deleted model event with the dispatcher.
     *
     * 使用分派器注册一个删除的模型事件
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function deleted($callback)
    {
        //向调度程序注册一个模型事件
        static::registerModelEvent('deleted', $callback);
    }

    /**
     * Remove all of the event listeners for the model.
     *
     * 删除模型中的所有事件侦听器
     *
     * @return void
     */
    public static function flushEventListeners()
    {
        if (! isset(static::$dispatcher)) {
            return;
        }

        $instance = new static;
        //             获取可观察到的事件名称
        foreach ($instance->getObservableEvents() as $event) {
            static::$dispatcher->forget("eloquent.{$event}: ".static::class);
        }
    }

    /**
     * Get the event dispatcher instance.
     *
     * 获取事件调度程序实例
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public static function getEventDispatcher()
    {
        return static::$dispatcher;
    }

    /**
     * Set the event dispatcher instance.
     *
     * 设置事件调度实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public static function setEventDispatcher(Dispatcher $dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * Unset the event dispatcher for models.
     *
     * 为模型设置事件调度程序
     *
     * @return void
     */
    public static function unsetEventDispatcher()
    {
        static::$dispatcher = null;
    }
}
