<?php

namespace Illuminate\Foundation\Exceptions;

use Exception;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\Debug\Exception\FlattenException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class Handler implements ExceptionHandlerContract
{
    /**
     * The container implementation.
     *
     * 容器实现
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * A list of the exception types that should not be reported.
     *
     * 一个不应该被报告的异常类型的列表
     *
     * @var array
     */
    protected $dontReport = [];

    /**
     * Create a new exception handler instance.
     *
     * 创建一个新的异常处理程序实例
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Report or log an exception.
     *
     * 报告或记录异常
     *
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    public function report(Exception $e)
    {
        if ($this->shouldntReport($e)) {//确定异常是否在“不要报告”列表中
            return;
        }

        try {
            $logger = $this->container->make(LoggerInterface::class);//从容器中解析给定类型
        } catch (Exception $ex) {
            throw $e; // throw the original exception  抛出原始异常
        }
        //
        $logger->error($e);
    }

    /**
     * Determine if the exception should be reported.
     *
     * 确定是否应该报告异常
     *
     * @param  \Exception  $e
     * @return bool
     */
    public function shouldReport(Exception $e)
    {
        return ! $this->shouldntReport($e);//确定异常是否在“不要报告”列表中
    }

    /**
     * Determine if the exception is in the "do not report" list.
     *
     * 确定异常是否在“不要报告”列表中
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function shouldntReport(Exception $e)
    {
        $dontReport = array_merge($this->dontReport, [HttpResponseException::class]);
        //                                      从集合中获取第一项
        return ! is_null(collect($dontReport)->first(function ($type) use ($e) {
            return $e instanceof $type;
        }));
    }

    /**
     * Render an exception into a response.
     *
     * 在响应中呈现异常
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $e)
    {
        $e = $this->prepareException($e);//准备渲染异常

        if ($e instanceof HttpResponseException) {
            return $e->getResponse();//获取底层响应实例
        } elseif ($e instanceof AuthenticationException) {
            return $this->unauthenticated($request, $e);//将身份验证异常转换为未经身份验证的响应
        } elseif ($e instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($e, $request);//从给定的验证异常创建响应对象
        }
        //准备包含异常的响应
        return $this->prepareResponse($request, $e);
    }

    /**
     * Prepare exception for rendering.
     *
     * 准备渲染异常
     *
     * @param  \Exception  $e
     * @return \Exception
     */
    protected function prepareException(Exception $e)
    {
        if ($e instanceof ModelNotFoundException) {
            $e = new NotFoundHttpException($e->getMessage(), $e);
        } elseif ($e instanceof AuthorizationException) {
            $e = new HttpException(403, $e->getMessage());
        }

        return $e;
    }

    /**
     * Create a response object from the given validation exception.
     *
     * 从给定的验证异常创建响应对象
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        if ($e->response) {
            return $e->response;
        }
        //                消息容器的另一种语义快捷方式  获取验证器的消息容器
        $errors = $e->validator->errors()->getMessages();
        //确定当前请求是否可能需要JSON响应
        if ($request->expectsJson()) {
            //从应用程序返回新的响应->从应用程序返回一个新的JSON响应
            return response()->json($errors, 422);
        }
        //得到重定向器的实例->创建一个新的重定向响应到以前的位置->在会话中闪存输入的数组
        return redirect()->back()->withInput(
            $request->input()//从请求中检索输入项
        )->withErrors($errors);//将错误的容器闪存到会话中
    }

    /**
     * Prepare response containing exception render.
     *
     * 准备包含异常的响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function prepareResponse($request, Exception $e)
    {
        //确定给定的异常是否为HTTP异常
        if ($this->isHttpException($e)) {
            //将给定的异常映射到一个Illuminate响应中(渲染给定的HttpException)
            return $this->toIlluminateResponse($this->renderHttpException($e), $e);
        } else {
            //将给定的异常映射到一个Illuminate响应中(创建一个Symfony响应给定的异常)
            return $this->toIlluminateResponse($this->convertExceptionToResponse($e), $e);
        }
    }

    /**
     * Render the given HttpException.
     *
     * 渲染给定的HttpException
     *
     * @param  \Symfony\Component\HttpKernel\Exception\HttpException  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderHttpException(HttpException $e)
    {
        $status = $e->getStatusCode();
        //获取给定视图的得到视图内容->替换给定名称空间的名称空间提示
        view()->replaceNamespace('errors', [
            resource_path('views/errors'),//获取资源文件夹的路径
            __DIR__.'/views',
        ]);
        //获取给定视图的得到视图内容->确定给定的视图是否存在
        if (view()->exists("errors::{$status}")) {
            //从应用程序返回新的响应->从应用程序返回一个新的视图响应
            return response()->view("errors::{$status}", ['exception' => $e], $status, $e->getHeaders());
        } else {
            //创建一个Symfony响应给定的异常
            return $this->convertExceptionToResponse($e);
        }
    }

    /**
     * Create a Symfony response for the given exception.
     *
     * 创建一个Symfony响应给定的异常
     *
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertExceptionToResponse(Exception $e)
    {
        $e = FlattenException::create($e);

        $handler = new SymfonyExceptionHandler(config('app.debug'));
        //                                 获取与给定异常相关的全部HTML内容
        return SymfonyResponse::create($handler->getHtml($e), $e->getStatusCode(), $e->getHeaders());
    }

    /**
     * Map the given exception into an Illuminate response.
     *
     * 将给定的异常映射到一个Illuminate响应中
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    protected function toIlluminateResponse($response, Exception $e)
    {
        if ($response instanceof SymfonyRedirectResponse) {
            //                                  返回目标URL              检索当前web响应的状态代码              返回所有标头
            $response = new RedirectResponse($response->getTargetUrl(), $response->getStatusCode(), $response->headers->all());
        } else {
            //                       获取当前的响应内容                    检索当前web响应的状态代码          返回所有标头
            $response = new Response($response->getContent(), $response->getStatusCode(), $response->headers->all());
        }
        //设置附加到响应的异常
        return $response->withException($e);
    }

    /**
     * Render an exception to the console.
     *
     * 在控制台中呈现异常
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Exception  $e
     * @return void
     */
    public function renderForConsole($output, Exception $e)
    {
        //                        呈现捕获异常
        (new ConsoleApplication)->renderException($e, $output);
    }

    /**
     * Determine if the given exception is an HTTP exception.
     *
     * 确定给定的异常是否为HTTP异常
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function isHttpException(Exception $e)
    {
        return $e instanceof HttpException;
    }
}
