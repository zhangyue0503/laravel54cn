<?php

namespace Illuminate\View\Engines;

use Exception;
use ErrorException;
use Illuminate\View\Compilers\CompilerInterface;

class CompilerEngine extends PhpEngine
{
    /**
     * The Blade compiler instance.
     *
     * Blade编译器实例
     *
     * @var \Illuminate\View\Compilers\CompilerInterface
     */
    protected $compiler;

    /**
     * A stack of the last compiled templates.
     *
     * 一堆最后编译的模板
     *
     * @var array
     */
    protected $lastCompiled = [];

    /**
     * Create a new Blade view engine instance.
     *
     * 创建一个新的Blade视图引擎实例
     *
     * @param  \Illuminate\View\Compilers\CompilerInterface  $compiler
     * @return void
     */
    public function __construct(CompilerInterface $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * 获取视图的评估内容
     *
     * @param  string  $path
     * @param  array   $data
     * @return string
     */
    public function get($path, array $data = [])
    {
        $this->lastCompiled[] = $path;

        // If this given view has expired, which means it has simply been edited since
        // it was last compiled, we will re-compile the views so we can evaluate a
        // fresh copy of the view. We'll pass the compiler the path of the view.
        //
        // 如果这个给定的视图已经过期了，这意味着它已经被编辑了，因为它是最后一个编译的，我们将重新编译视图，以便我们可以评估一个新的视图
        // 我们将通过编译器的视图路径
        //
        //                 确定给定的视图是否已过期
        if ($this->compiler->isExpired($path)) {
            //          在给定的路径上编译视图
            $this->compiler->compile($path);
        }
        //                      获取到已编译版本的视图的路径
        $compiled = $this->compiler->getCompiledPath($path);

        // Once we have the path to the compiled file, we will evaluate the paths with
        // typical PHP just like any other templates. We also keep a stack of views
        // which have been rendered for right exception messages to be generated.
        //
        // 一旦我们有了编译文件的路径，我们将用典型的PHP来评估路径，就像其他模板一样
        // 我们还保留了一组视图，这些视图已经被呈现为要生成的正确的异常消息
        //
        //          在给定的路径中获取视图的值
        $results = $this->evaluatePath($compiled, $data);

        array_pop($this->lastCompiled);

        return $results;
    }

    /**
     * Handle a view exception.
     *
     * 处理一个视图异常
     *
     * @param  \Exception  $e
     * @param  int  $obLevel
     * @return void
     *
     * @throws $e
     */
    protected function handleViewException(Exception $e, $obLevel)
    {
        //                        为异常获取异常消息
        $e = new ErrorException($this->getMessage($e), 0, 1, $e->getFile(), $e->getLine(), $e);
        //处理一个视图异常
        parent::handleViewException($e, $obLevel);
    }

    /**
     * Get the exception message for an exception.
     *
     * 为异常获取异常消息
     *
     * @param  \Exception  $e
     * @return string
     */
    protected function getMessage(Exception $e)
    {
        return $e->getMessage().' (View: '.realpath(last($this->lastCompiled)).')';
    }

    /**
     * Get the compiler implementation.
     *
     * 获取编译器实现
     *
     * @return \Illuminate\View\Compilers\CompilerInterface
     */
    public function getCompiler()
    {
        return $this->compiler;
    }
}
