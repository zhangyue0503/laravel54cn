<?php

namespace Illuminate\View\Engines;

use Closure;
use InvalidArgumentException;

class EngineResolver
{
    /**
     * The array of engine resolvers.
     *
     * 引擎解析器阵列
     *
     * @var array
     */
    protected $resolvers = [];

    /**
     * The resolved engine instances.
     *
     * 解析引擎实例
     *
     * @var array
     */
    protected $resolved = [];

    /**
     * Register a new engine resolver.
     *
     * 注册一个新的引擎解析器
     *
     * The engine string typically corresponds to a file extension.
     *
     * 引擎字符串通常对应于一个文件扩展名
     *
     * @param  string   $engine
     * @param  \Closure  $resolver
     * @return void
     */
    public function register($engine, Closure $resolver)
    {
        unset($this->resolved[$engine]);

        $this->resolvers[$engine] = $resolver;
    }

    /**
     * Resolver an engine instance by name.
     *
     * 按名称命名引擎实例
     *
     * @param  string  $engine
     * @return \Illuminate\View\Engines\EngineInterface
     * @throws \InvalidArgumentException
     */
    public function resolve($engine)
    {
        if (isset($this->resolved[$engine])) {
            return $this->resolved[$engine];
        }

        if (isset($this->resolvers[$engine])) {
            return $this->resolved[$engine] = call_user_func($this->resolvers[$engine]);
        }

        throw new InvalidArgumentException("Engine $engine not found.");
    }
}
