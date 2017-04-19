<?php

namespace Illuminate\Log;

use Monolog\Logger as Monolog;
use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        // 在容器中注册共享绑定
        $this->app->singleton('log', function () {
            return $this->createLogger(); //创建日志记录器
        });
    }

    /**
     * Create the logger.
     *
     * 创建日志记录器
     *
     * @return \Illuminate\Log\Writer
     */
    public function createLogger()
    {
        $log = new Writer(  // 创建新日志写入实例
            //     monolog实例    获取日志“频道”的名称
            new Monolog($this->channel()), $this->app['events']
        );

        if ($this->app->hasMonologConfigurator()) {  //确定应用程序是否有一个自定义的Monolog配置
            //              获取应用程序的自定义Monolog配置             得到底层Monolog实例
            call_user_func($this->app->getMonologConfigurator(), $log->getMonolog());
        } else {
            $this->configureHandler($log);// 配置应用程序的Mongolog句柄
        }

        return $log;
    }

    /**
     * Get the name of the log "channel".
     *
     * 获取日志“频道”的名称
     *
     * @return string
     */
    protected function channel()
    {
        //             确定给定的抽象类型是否已绑定    获取或检查当前应用程序环境
        return $this->app->bound('env') ? $this->app->environment() : 'production';
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * 配置应用程序的Mongolog句柄
     *
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureHandler(Writer $log)
    {
        //                              获取默认的日志句柄
        $this->{'configure'.ucfirst($this->handler()).'Handler'}($log);
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * 配置应用程序的Mongolog句柄
     *
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureSingleHandler(Writer $log)
    {
        $log->useFiles( //注册日志文件句柄
            //由开发人员定义的自定义存储路径
            $this->app->storagePath().'/logs/laravel.log',
            $this->logLevel() //获取应用程序的日志级别
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * 配置应用程序的Mongolog句柄
     *
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureDailyHandler(Writer $log)
    {
        $log->useDailyFiles(
            $this->app->storagePath().'/logs/laravel.log', $this->maxFiles(), //由开发人员定义的自定义存储路径，注册每日文件日志处理程序,获取应用程序的日志文件的最大数目,
            $this->logLevel()//获取应用程序的日志级别
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * 配置应用程序的Mongolog句柄
     *
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureSyslogHandler(Writer $log)
    {
        //登记Sys日志处理程序            获取应用程序的日志级别
        $log->useSyslog('laravel', $this->logLevel());
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * 配置应用程序的Mongolog句柄
     *
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureErrorlogHandler(Writer $log)
    {
        //注册错误日志处理程序  获取应用程序的日志级别
        $log->useErrorLog($this->logLevel());
    }

    /**
     * Get the default log handler.
     *
     * 获取默认的日志句柄
     *
     * @return string
     */
    protected function handler()
    {
        if ($this->app->bound('config')) { //确定给定的抽象类型是否已绑定
            //从容器中解析给定类型
            return $this->app->make('config')->get('app.log');
        }

        return 'single';
    }

    /**
     * Get the log level for the application.
     *
     * 获取应用程序的日志级别
     *
     * @return string
     */
    protected function logLevel()
    {
        if ($this->app->bound('config')) {//确定给定的抽象类型是否已绑定
            //从容器中解析给定类型
            return $this->app->make('config')->get('app.log_level', 'debug');
        }

        return 'debug';
    }

    /**
     * Get the maximum number of log files for the application.
     *
     * 获取应用程序的日志文件的最大数目
     *
     * @return int
     */
    protected function maxFiles()
    {
        if ($this->app->bound('config')) {//确定给定的抽象类型是否已绑定
            //从容器中解析给定类型
            return $this->app->make('config')->get('app.log_max_files', 5);
        }

        return 0;
    }
}
