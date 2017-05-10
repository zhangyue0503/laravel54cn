<?php

namespace Illuminate\Contracts\Logging;

interface Log
{
    /**
     * Log an alert message to the logs.
     *
     * 将警报消息记录到日志中
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function alert($message, array $context = []);

    /**
     * Log a critical message to the logs.
     *
     * 将一条重要消息记录到日志中
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function critical($message, array $context = []);

    /**
     * Log an error message to the logs.
     *
     * 将错误消息记录到日志中
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function error($message, array $context = []);

    /**
     * Log a warning message to the logs.
     *
     * 将一条警告消息记录到日志中
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function warning($message, array $context = []);

    /**
     * Log a notice to the logs.
     *
     * 将一个通知记录到日志中
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function notice($message, array $context = []);

    /**
     * Log an informational message to the logs.
     *
     * 将一条信息消息记录到日志中
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function info($message, array $context = []);

    /**
     * Log a debug message to the logs.
     *
     * 将调试消息记录到日志中
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function debug($message, array $context = []);

    /**
     * Log a message to the logs.
     *
     * 将消息记录到日志中
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function log($level, $message, array $context = []);

    /**
     * Register a file log handler.
     *
     * 注册一个文件日志处理程序
     *
     * @param  string  $path
     * @param  string  $level
     * @return void
     */
    public function useFiles($path, $level = 'debug');

    /**
     * Register a daily file log handler.
     *
     * 注册一个每日文件日志处理程序
     *
     * @param  string  $path
     * @param  int     $days
     * @param  string  $level
     * @return void
     */
    public function useDailyFiles($path, $days = 0, $level = 'debug');
}
