<?php

namespace Illuminate\Contracts\Debug;

use Exception;

interface ExceptionHandler
{
    /**
     * Report or log an exception.
     *
     * 报告或记录异常
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e);

    /**
     * Render an exception into an HTTP response.
     *
     * 在HTTP响应中呈现异常
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $e);

    /**
     * Render an exception to the console.
     *
     * 在控制台中呈现异常
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Exception  $e
     * @return void
     */
    public function renderForConsole($output, Exception $e);
}
