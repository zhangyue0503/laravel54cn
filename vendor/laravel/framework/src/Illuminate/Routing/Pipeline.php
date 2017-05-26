<?php

namespace Illuminate\Routing;

use Closure;
use Exception;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Pipeline\Pipeline as BasePipeline;
use Symfony\Component\Debug\Exception\FatalThrowableError;

/**
 * This extended pipeline catches any exceptions that occur during each slice.
 *
 * 这个扩展的管道捕捉任何异常发生在每个切片
 *
 * The exceptions are converted to HTTP responses for proper middleware handling.
 *
 * 异常被转换为HTTP响应，以便处理适当的中间件
 *
 */
class Pipeline extends BasePipeline
{
    /**
     * Get the final piece of the Closure onion.
     *
     * 得到最后一个洋葱的洋葱
     *
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function prepareDestination(Closure $destination)
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (Exception $e) {
                //          处理给定的异常
                return $this->handleException($passable, $e);
            } catch (Throwable $e) {
                return $this->handleException($passable, new FatalThrowableError($e));
            }
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * 获取一个表示应用程序洋葱切片的闭包
     *
     * @return \Closure
     */
    protected function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    //获取表示应用程序洋葱片（分层）的闭包
                    $slice = parent::carry();

                    $callable = $slice($stack, $pipe);

                    return $callable($passable);
                } catch (Exception $e) {
                    //          处理给定的异常
                    return $this->handleException($passable, $e);
                } catch (Throwable $e) {
                    return $this->handleException($passable, new FatalThrowableError($e));
                }
            };
        };
    }

    /**
     * Handle the given exception.
     *
     * 处理给定的异常
     *
     * @param  mixed  $passable
     * @param  \Exception  $e
     * @return mixed
     *
     * @throws \Exception
     */
    protected function handleException($passable, Exception $e)
    {
        //确定给定的抽象类型是否已绑定
        if (! $this->container->bound(ExceptionHandler::class) || ! $passable instanceof Request) {
            throw $e;
        }
        //               从容器中解析给定类型
        $handler = $this->container->make(ExceptionHandler::class);
        //报告或记录异常
        $handler->report($e);
        //             在HTTP响应中呈现异常
        $response = $handler->render($passable, $e);

        if (method_exists($response, 'withException')) {
            //         设置附加到响应的异常
            $response->withException($e);
        }

        return $response;
    }
}
