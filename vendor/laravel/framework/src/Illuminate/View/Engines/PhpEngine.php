<?php

namespace Illuminate\View\Engines;

use Exception;
use Throwable;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class PhpEngine implements EngineInterface
{
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
        //        在给定的路径中获取视图的值
        return $this->evaluatePath($path, $data);
    }

    /**
     * Get the evaluated contents of the view at the given path.
     *
     * 在给定的路径中获取视图的值
     *
     * @param  string  $__path
     * @param  array   $__data
     * @return string
     */
    protected function evaluatePath($__path, $__data)
    {
        $obLevel = ob_get_level();

        ob_start();

        extract($__data, EXTR_SKIP);

        // We'll evaluate the contents of the view inside a try/catch block so we can
        // flush out any stray output that might get out before an error occurs or
        // an exception is thrown. This prevents any partial views from leaking.
        //
        // 我们将对一个try/catch块中的视图的内容进行评估，这样我们就可以清除任何可能在出现错误或抛出异常之前输出的流
        // 这可以防止任何部分视图泄漏
        //
        try {
            include $__path;
        } catch (Exception $e) {
            //      处理一个视图异常
            $this->handleViewException($e, $obLevel);
        } catch (Throwable $e) {
            $this->handleViewException(new FatalThrowableError($e), $obLevel);
        }

        return ltrim(ob_get_clean());
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
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }
}
