<?php

namespace Illuminate\Log;

use Closure;
use RuntimeException;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger as MonologLogger;
use Illuminate\Log\Events\MessageLogged;
use Monolog\Handler\RotatingFileHandler;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Illuminate\Contracts\Logging\Log as LogContract;

class Writer implements LogContract, PsrLoggerInterface
{
    /**
     * The Monolog logger instance.
     *
     * Monolog记录器实例
     *
     * @var \Monolog\Logger
     */
    protected $monolog;

    /**
     * The event dispatcher instance.
     *
     * 事件调度实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $dispatcher;

    /**
     * The Log levels.
     *
     * 日志等级
     *
     * @var array
     */
    protected $levels = [
        'debug'     => MonologLogger::DEBUG,
        'info'      => MonologLogger::INFO,
        'notice'    => MonologLogger::NOTICE,
        'warning'   => MonologLogger::WARNING,
        'error'     => MonologLogger::ERROR,
        'critical'  => MonologLogger::CRITICAL,
        'alert'     => MonologLogger::ALERT,
        'emergency' => MonologLogger::EMERGENCY,
    ];

    /**
     * Create a new log writer instance.
     *
     * 创建新日志写入实例
     *
     * @param  \Monolog\Logger  $monolog
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public function __construct(MonologLogger $monolog, Dispatcher $dispatcher = null)
    {
        $this->monolog = $monolog;

        if (isset($dispatcher)) {
            $this->dispatcher = $dispatcher;
        }
    }

    /**
     * Log an emergency message to the logs.
     *
     * 在日志中记录紧急消息
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        // 将消息写入Monolog
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an alert message to the logs.
     *
     * 将警报消息记录到日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function alert($message, array $context = [])
    {
        // 将消息写入Monolog
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a critical message to the logs.
     *
     * 将关键消息记录到日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function critical($message, array $context = [])
    {
        // 将消息写入Monolog
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an error message to the logs.
     *
     * 将错误消息记录到日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function error($message, array $context = [])
    {
        // 将消息写入Monolog
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a warning message to the logs.
     *
     * 将警告消息记录到日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function warning($message, array $context = [])
    {
        // 将消息写入Monolog
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a notice to the logs.
     *
     * 将通知记录在日志上
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function notice($message, array $context = [])
    {
        // 将消息写入Monolog
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an informational message to the logs.
     *
     * 将信息消息记录到日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function info($message, array $context = [])
    {
        // 将消息写入Monolog
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a debug message to the logs.
     *
     * 将调试消息记录到日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function debug($message, array $context = [])
    {
        // 将消息写入Monolog
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a message to the logs.
     *
     * 将消息记录到日志
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        // 将消息写入Monolog
        $this->writeLog($level, $message, $context);
    }

    /**
     * Dynamically pass log calls into the writer.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function write($level, $message, array $context = [])
    {
        // 将消息写入Monolog
        $this->writeLog($level, $message, $context);
    }

    /**
     * Write a message to Monolog.
     *
     * 将消息写入Monolog
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    protected function writeLog($level, $message, $context)
    {
        // 引发日志事件                              格式化记录器的参数
        $this->fireLogEvent($level, $message = $this->formatMessage($message), $context);

        $this->monolog->{$level}($message, $context);
    }

    /**
     * Register a file log handler.
     *
     * 注册日志文件句柄
     *
     * @param  string  $path
     * @param  string  $level
     * @return void
     */
    public function useFiles($path, $level = 'debug')
    {
        //                                                                字符串等解析为Monolog的常量
        $this->monolog->pushHandler($handler = new StreamHandler($path, $this->parseLevel($level)));
        //                            获得一个默认的Monolog格式化程序实例
        $handler->setFormatter($this->getDefaultFormatter());
    }

    /**
     * Register a daily file log handler.
     *
     * 注册每日文件日志处理程序
     *
     * @param  string  $path
     * @param  int     $days
     * @param  string  $level
     * @return void
     */
    public function useDailyFiles($path, $days = 0, $level = 'debug')
    {
        $this->monolog->pushHandler(
            //                                                字符串等解析为Monolog的常量
            $handler = new RotatingFileHandler($path, $days, $this->parseLevel($level))
        );
        //                         获得一个默认的Monolog格式化程序实例
        $handler->setFormatter($this->getDefaultFormatter());
    }

    /**
     * Register a Syslog handler.
     *
     * 注册Sys日志处理程序
     *
     * @param  string  $name
     * @param  string  $level
     * @param  mixed  $facility
     * @return \Psr\Log\LoggerInterface
     */
    public function useSyslog($name = 'laravel', $level = 'debug', $facility = LOG_USER)
    {
        //              将处理程序推到堆栈上
        return $this->monolog->pushHandler(new SyslogHandler($name, $facility, $level));
    }

    /**
     * Register an error_log handler.
     *
     * 注册错误日志处理程序
     *
     * @param  string  $level
     * @param  int  $messageType
     * @return void
     */
    public function useErrorLog($level = 'debug', $messageType = ErrorLogHandler::OPERATING_SYSTEM)
    {
        //将处理程序推到堆栈上
        $this->monolog->pushHandler(
        //                                                字符串等解析为Monolog的常量
            $handler = new ErrorLogHandler($messageType, $this->parseLevel($level))
        );
        //                         获得一个默认的Monolog格式化程序实例
        $handler->setFormatter($this->getDefaultFormatter());
    }

    /**
     * Register a new callback handler for when a log event is triggered.
     *
     * 当触发日志事件时，注册一个新回调处理程序
     *
     * @param  \Closure  $callback
     * @return void
     *
     * @throws \RuntimeException
     */
    public function listen(Closure $callback)
    {
        if (! isset($this->dispatcher)) {
            throw new RuntimeException('Events dispatcher has not been set.');
        }
        //用分配器注册事件监听器
        $this->dispatcher->listen(MessageLogged::class, $callback);
    }

    /**
     * Fires a log event.
     *
     * 引发日志事件
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array   $context
     * @return void
     */
    protected function fireLogEvent($level, $message, array $context = [])
    {
        // If the event dispatcher is set, we will pass along the parameters to the
        // log listeners. These are useful for building profilers or other tools
        // that aggregate all of the log messages for a given "request" cycle.
        //
        // 如果设置了事件调度器，我们将将参数传递给日志侦听器
        // 这些都是有用的建筑剖面或其他工具，聚集所有的日志信息对于一个给定的“request”周期
        //
        if (isset($this->dispatcher)) {
            //触发事件并调用监听器
            $this->dispatcher->dispatch(new MessageLogged($level, $message, $context));
        }
    }

    /**
     * Format the parameters for the logger.
     *
     * 格式化记录器的参数
     *
     * @param  mixed  $message
     * @return mixed
     */
    protected function formatMessage($message)
    {
        if (is_array($message)) {
            return var_export($message, true);
        } elseif ($message instanceof Jsonable) {
            return $message->toJson(); //将对象转换为JSON表示形式
        } elseif ($message instanceof Arrayable) {
            //                   获取数组实例
            return var_export($message->toArray(), true);
        }

        return $message;
    }

    /**
     * Parse the string level into a Monolog constant.
     *
     * 字符串等解析为Monolog的常量
     *
     * @param  string  $level
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    protected function parseLevel($level)
    {
        if (isset($this->levels[$level])) {
            return $this->levels[$level];
        }

        throw new InvalidArgumentException('Invalid log level.');
    }

    /**
     * Get the underlying Monolog instance.
     *
     * 得到底层Monolog实例
     *
     * @return \Monolog\Logger
     */
    public function getMonolog()
    {
        return $this->monolog;
    }

    /**
     * Get a default Monolog formatter instance.
     *
     * 获得一个默认的Monolog格式化程序实例
     *
     * @return \Monolog\Formatter\LineFormatter
     */
    protected function getDefaultFormatter()
    {
        return new LineFormatter(null, null, true, true);
    }

    /**
     * Get the event dispatcher instance.
     *
     * 获取事件调度实例
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Set the event dispatcher instance.
     *
     * 设置事件调度实例
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public function setEventDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
}
