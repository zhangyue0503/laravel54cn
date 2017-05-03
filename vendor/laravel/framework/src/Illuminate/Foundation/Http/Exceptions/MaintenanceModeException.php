<?php

namespace Illuminate\Foundation\Http\Exceptions;

use Exception;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class MaintenanceModeException extends ServiceUnavailableHttpException
{
    /**
     * When the application was put in maintenance mode.
     *
     * 当应用程序处于维护模式时
     *
     * @var \Carbon\Carbon
     */
    public $wentDownAt;

    /**
     * The number of seconds to wait before retrying.
     *
     * 在重新尝试之前等待的秒数
     *
     * @var int
     */
    public $retryAfter;

    /**
     * When the application should next be available.
     *
     * 当应用程序下一次可用时
     *
     * @var \Carbon\Carbon
     */
    public $willBeAvailableAt;

    /**
     * Create a new exception instance.
     *
     * 创建一个新的异常实例
     *
     * @param  int  $time
     * @param  int  $retryAfter
     * @param  string  $message
     * @param  \Exception  $previous
     * @param  int  $code
     * @return void
     */
    public function __construct($time, $retryAfter = null, $message = null, Exception $previous = null, $code = 0)
    {
        parent::__construct($retryAfter, $message, $previous, $code);//服务不可用Http异常

        $this->wentDownAt = Carbon::createFromTimestamp($time);//从时间戳中创建一个Carbon实例

        if ($retryAfter) {
            $this->retryAfter = $retryAfter;
            //                         从时间戳中创建一个Carbon实例->在实例中添加秒
            $this->willBeAvailableAt = Carbon::createFromTimestamp($time)->addSeconds($this->retryAfter);
        }
    }
}
