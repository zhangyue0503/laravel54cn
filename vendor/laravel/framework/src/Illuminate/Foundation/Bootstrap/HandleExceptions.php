<?php

namespace Illuminate\Foundation\Bootstrap;

use Exception;
use ErrorException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
//处理异常
class HandleExceptions
{
    /**
     * The application instance.
     *
     * 应用程序实例
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Bootstrap the given application.
	 *
	 * 引导给定应用程序
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $this->app = $app;

        error_reporting(-1);

        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handleException']);

        register_shutdown_function([$this, 'handleShutdown']);
        //获取或检查当前的应用程序环境
        if (! $app->environment('testing')) {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Convert PHP errors to ErrorException instances.
     *
     * 将PHP错误转换为ErrorException实例
     *
     * @param  int  $level
     * @param  string  $message
     * @param  string  $file
     * @param  int  $line
     * @param  array  $context
     * @return void
     *
     * @throws \ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * 处理应用程序中未捕获的异常
     *
     * Note: Most exceptions can be handled via the try / catch block in
     * the HTTP and Console kernels. But, fatal error exceptions must
     * be handled differently since they are not normal exceptions.
     *
     * 注意:大多数异常可以通过HTTP和控制台内核中的try/catch块来处理
     * 但是，致命错误异常必须以不同的方式处理，因为它们不是正常的异常
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function handleException($e)
    {
        if (! $e instanceof Exception) {
            $e = new FatalThrowableError($e);
        }
        //获取异常处理程序的实例->报告或记录异常
        $this->getExceptionHandler()->report($e);
        //确定我们是否在控制台中运行
        if ($this->app->runningInConsole()) {
            $this->renderForConsole($e);//在控制台中呈现异常
        } else {
            $this->renderHttpResponse($e);//将异常作为HTTP响应发送并发送
        }
    }

    /**
     * Render an exception to the console.
     *
     * 在控制台中呈现异常
     *
     * @param  \Exception  $e
     * @return void
     */
    protected function renderForConsole(Exception $e)
    {
        //获取异常处理程序的实例->在控制台中呈现异常
        $this->getExceptionHandler()->renderForConsole(new ConsoleOutput, $e);
    }

    /**
     * Render an exception as an HTTP response and send it.
     *
     * 将异常作为HTTP响应发送并发送
     *
     * @param  \Exception  $e
     * @return void
     */
    protected function renderHttpResponse(Exception $e)
    {
        //获取异常处理程序的实例->在HTTP响应中呈现异常
        $this->getExceptionHandler()->render($this->app['request'], $e)->send();
    }

    /**
     * Handle the PHP shutdown event.
     *
     * 处理PHP关闭事件
     *
     * @return void
     */
    public function handleShutdown()
    {
        //                                               确定错误类型是否致命
        if (! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            //处理应用程序中未捕获的异常(从错误数组中创建一个新的致命异常实例)
            $this->handleException($this->fatalExceptionFromError($error, 0));
        }
    }

    /**
     * Create a new fatal exception instance from an error array.
     *
     * 从错误数组中创建一个新的致命异常实例
     *
     * @param  array  $error
     * @param  int|null  $traceOffset
     * @return \Symfony\Component\Debug\Exception\FatalErrorException
     */
    protected function fatalExceptionFromError(array $error, $traceOffset = null)
    {
        return new FatalErrorException(
            $error['message'], $error['type'], 0, $error['file'], $error['line'], $traceOffset
        );
    }

    /**
     * Determine if the error type is fatal.
     *
     * 确定错误类型是否致命
     *
     * @param  int  $type
     * @return bool
     */
    protected function isFatal($type)
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

    /**
     * Get an instance of the exception handler.
     *
     * 获取异常处理程序的实例
     *
     * @return \Illuminate\Contracts\Debug\ExceptionHandler
     */
    protected function getExceptionHandler()
    {
        //              从容器中解析给定类型
        return $this->app->make(ExceptionHandler::class);
    }
}
