<?php

namespace Illuminate\Foundation;
//别名装载机
class AliasLoader
{
    /**
     * The array of class aliases.
     *
     * 类别名数组
     *
     * @var array
     */
    protected $aliases;

    /**
     * Indicates if a loader has been registered.
     *
     * 指示是否已加载加载程序
     *
     * @var bool
     */
    protected $registered = false;

    /**
     * The namespace for all real-time facades.
     *
     * 所有实时门面的命名空间
     *
     * @var string
     */
    protected static $facadeNamespace = 'Facades\\';

    /**
     * The singleton instance of the loader.
     *
     * 加载程序的单个实例
     *
     * @var \Illuminate\Foundation\AliasLoader
     */
    protected static $instance;

    /**
     * Create a new AliasLoader instance.
     *
     * 创建一个新的AliasLoader实例
     *
     * @param  array  $aliases
     */
    private function __construct($aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * Get or create the singleton alias loader instance.
	 *
	 * 获取或创建别名加载程序实例
     *
     * @param  array  $aliases
     * @return \Illuminate\Foundation\AliasLoader
     */
    public static function getInstance(array $aliases = [])
    {
        if (is_null(static::$instance)) {
            return static::$instance = new static($aliases);
        }
        //                                  获取注册别名
        $aliases = array_merge(static::$instance->getAliases(), $aliases);

        static::$instance->setAliases($aliases); //设置已注册的别名

        return static::$instance;
    }

    /**
     * Load a class alias if it is registered.
	 *
	 * 如果注册，加载类别名
     *
     * @param  string  $alias
     * @return bool|null
     */
    public function load($alias)
    {
        if (static::$facadeNamespace && strpos($alias, static::$facadeNamespace) === 0) {
            $this->loadFacade($alias); // 为给定的别名加载实时门面

            return true;
        }

        if (isset($this->aliases[$alias])) {
            return class_alias($this->aliases[$alias], $alias);
        }
    }

    /**
     * Load a real-time facade for the given alias.
	 *
	 * 为给定的别名加载实时门面
     *
     * @param  string  $alias
     * @return bool
     */
    protected function loadFacade($alias)
    {
		// 用给定的值调用给定的闭包，然后返回值( 确保给定的别名有一个现有的实时门面类)
        tap($this->ensureFacadeExists($alias), function ($path) {
            require $path;
        });
    }

    /**
     * Ensure that the given alias has an existing real-time facade class.
	 *
	 * 确保给定的别名有一个现有的实时门面类
     *
     * @param  string  $alias
     * @return string
     */
    protected function ensureFacadeExists($alias)
    {
        if (file_exists($path = storage_path('framework/cache/facade-'.sha1($alias).'.php'))) {
            return $path;
        }

        file_put_contents($path, $this->formatFacadeStub( // 使用适当的命名空间和类格式化门面存根
            $alias, file_get_contents(__DIR__.'/stubs/facade.stub')
        ));

        return $path;
    }

    /**
     * Format the facade stub with the proper namespace and class.
	 *
	 * 使用适当的命名空间和类格式化门面存根
     *
     * @param  string  $alias
     * @param  string  $stub
     * @return string
     */
    protected function formatFacadeStub($alias, $stub)
    {
        $replacements = [
            str_replace('/', '\\', dirname(str_replace('\\', '/', $alias))),
            class_basename($alias), // 获取类的“basename“从给定的对象/类
            substr($alias, strlen(static::$facadeNamespace)),
        ];

        return str_replace(
            ['DummyNamespace', 'DummyClass', 'DummyTarget'], $replacements, $stub
        );
    }

    /**
     * Add an alias to the loader.
     *
     * 在加载程序中添加别名
     *
     * @param  string  $class
     * @param  string  $alias
     * @return void
     */
    public function alias($class, $alias)
    {
        $this->aliases[$class] = $alias;
    }

    /**
     * Register the loader on the auto-loader stack.
	 *
	 * 在自动装载机堆栈上注册加载器
     *
     * @return void
     */
    public function register()
    {
        if (! $this->registered) {
            $this->prependToLoaderStack(); // 自动装载堆栈装载预先加载的方法

            $this->registered = true;
        }
    }

    /**
     * Prepend the load method to the auto-loader stack.
	 *
	 * 自动装载堆栈装载预先加载的方法
     *
     * @return void
     */
    protected function prependToLoaderStack()
    {
		//                       this->load()
        spl_autoload_register([$this, 'load'], true, true);
    }

    /**
     * Get the registered aliases.
     *
     * 获取注册别名
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Set the registered aliases.
     *
     * 设置已注册的别名
     *
     * @param  array  $aliases
     * @return void
     */
    public function setAliases(array $aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * Indicates if the loader has been registered.
     *
     * 指示加载程序是否已注册
     *
     * @return bool
     */
    public function isRegistered()
    {
        return $this->registered;
    }

    /**
     * Set the "registered" state of the loader.
     *
     * 设置装载机的“注册”状态
     *
     * @param  bool  $value
     * @return void
     */
    public function setRegistered($value)
    {
        $this->registered = $value;
    }

    /**
     * Set the real-time facade namespace.
     *
     * 设置实时门面命名空间
     *
     * @param  string  $namespace
     * @return void
     */
    public static function setFacadeNamespace($namespace)
    {
        static::$facadeNamespace = rtrim($namespace, '\\').'\\';
    }

    /**
     * Set the value of the singleton alias loader.
     *
     * 设置单例别名加载程序的值
     *
     * @param  \Illuminate\Foundation\AliasLoader  $loader
     * @return void
     */
    public static function setInstance($loader)
    {
        static::$instance = $loader;
    }

    /**
     * Clone method.
     *
     * 克隆方法
     *
     * @return void
     */
    private function __clone()
    {
        //
    }
}
