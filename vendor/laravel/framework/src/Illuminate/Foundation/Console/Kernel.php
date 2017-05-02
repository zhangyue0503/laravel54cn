<?php

namespace Illuminate\Foundation\Console;

use Closure;
use Exception;
use Throwable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Kernel implements KernelContract
{
    /**
     * The application implementation.
     *
     * 应用程序实现
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The event dispatcher implementation.
     *
     * 事件调度器实现
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The Artisan application instance.
     *
     * Artisan应用程序实例
     *
     * @var \Illuminate\Console\Application
     */
    protected $artisan;

    /**
     * The Artisan commands provided by the application.
     *
     * 应用程序提供的Artisan命令
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Indicates if the Closure commands have been loaded.
     *
     * 指示是否已经加载了关闭命令
     *
     * @var bool
     */
    protected $commandsLoaded = false;

    /**
     * The bootstrap classes for the application.
     *
     * 应用程序的引导类
     *
     * @var array
     */
    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\SetRequestForConsole::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
    ];

    /**
     * Create a new console kernel instance.
     *
     * 创建一个新的控制台内核实例
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(Application $app, Dispatcher $events)
    {
        if (! defined('ARTISAN_BINARY')) {
            define('ARTISAN_BINARY', 'artisan');
        }

        $this->app = $app;
        $this->events = $events;
        //注册一个新的“引导”监听
        $this->app->booted(function () {
            $this->defineConsoleSchedule();//定义应用程序的命令调度
        });
    }

    /**
     * Define the application's command schedule.
     *
     * 定义应用程序的命令调度
     *
     * @return void
     */
    protected function defineConsoleSchedule()
    {
        $this->app->instance(//在容器中注册一个已存在的实例
            Schedule::class, $schedule = new Schedule($this->app[Cache::class])
        );

        $this->schedule($schedule);//定义应用程序的命令调度
    }

    /**
     * Run the console application.
     *
     * 运行控制台应用程序
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    public function handle($input, $output = null)
    {
        try {
            $this->bootstrap();//引导应用程序命令

            if (! $this->commandsLoaded) {
                $this->commands();//为应用程序注册基于闭包的命令

                $this->commandsLoaded = true;
            }
            //获得Artisan应用程序实例->运行当前的应用程序
            return $this->getArtisan()->run($input, $output);
        } catch (Exception $e) {
            //向异常处理程序报告异常
            $this->reportException($e);
            //向异常处理程序报告异常
            $this->renderException($output, $e);

            return 1;
        } catch (Throwable $e) {
            $e = new FatalThrowableError($e);
            //向异常处理程序报告异常
            $this->reportException($e);
            //向异常处理程序报告异常
            $this->renderException($output, $e);

            return 1;
        }
    }

    /**
     * Terminate the application.
     *
     * 终止应用程序
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  int  $status
     * @return void
     */
    public function terminate($input, $status)
    {
        //终止应用程序
        $this->app->terminate();
    }

    /**
     * Define the application's command schedule.
     *
     * 定义应用程序的命令调度
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }

    /**
     * Register the Closure based commands for the application.
     *
     * 为应用程序注册基于闭包的命令
     *
     * @return void
     */
    protected function commands()
    {
        //
    }

    /**
     * Register a Closure based command with the application.
     *
     * 使用应用程序注册一个基于闭包的命令
     *
     * @param  string  $signature
     * @param  Closure  $callback
     * @return \Illuminate\Foundation\Console\ClosureCommand
     */
    public function command($signature, Closure $callback)
    {
        $command = new ClosureCommand($signature, $callback);
        //登记一个控制台的“starting”程序
        Artisan::starting(function ($artisan) use ($command) {
            $artisan->add($command);//向控制台添加一个命令
        });

        return $command;
    }

    /**
     * Register the given command with the console application.
     *
     * 使用控制台应用程序注册给定的命令
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return void
     */
    public function registerCommand($command)
    {
        //获得Artisan应用程序实例->向控制台添加一个命令
        $this->getArtisan()->add($command);
    }

    /**
     * Run an Artisan console command by name.
     *
     * 按名称运行一个Artisan控制台命令
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  \Symfony\Component\Console\Output\OutputInterface  $outputBuffer
     * @return int
     */
    public function call($command, array $parameters = [], $outputBuffer = null)
    {
        $this->bootstrap();//引导应用程序命令

        if (! $this->commandsLoaded) {
            $this->commands();//为应用程序注册基于闭包的命令

            $this->commandsLoaded = true;
        }
        //获得Artisan应用程序实例->按名称运行一个Artisan控制台命令
        return $this->getArtisan()->call($command, $parameters, $outputBuffer);
    }

    /**
     * Queue the given console command.
     *
     * 队列指定控制台命令
     *
     * @param  string  $command
     * @param  array   $parameters
     * @return void
     */
    public function queue($command, array $parameters = [])
    {
        //                                推送一条新的消息到队列
        $this->app[QueueContract::class]->push(
            new QueuedCommand(func_get_args())//创建一个新的作业实例
        );
    }

    /**
     * Get all of the commands registered with the console.
     *
     * 获得控制台注册的所有命令
     *
     * @return array
     */
    public function all()
    {
        $this->bootstrap();//引导应用程序命令
        //获得Artisan应用程序实例->数组键是完整的名称和命令实例的值()
        return $this->getArtisan()->all();
    }

    /**
     * Get the output for the last run command.
     *
     * 获取最后一个运行命令的输出
     *
     * @return string
     */
    public function output()
    {
        $this->bootstrap();//引导应用程序命令
        //获得Artisan应用程序实例->获取最后一个运行命令的输出
        return $this->getArtisan()->output();
    }

    /**
     * Bootstrap the application for artisan commands.
     *
     * 引导应用程序命令
     *
     * @return void
     */
    public function bootstrap()
    {
        if (! $this->app->hasBeenBootstrapped()) {//确定应用程序是否已经引导
            $this->app->bootstrapWith($this->bootstrappers());//运行给定的引导类数组(获取应用程序的引导类)
        }

        // If we are calling an arbitrary command from within the application, we'll load
        // all of the available deferred providers which will make all of the commands
        // available to an application. Otherwise the command will not be available.
        //
        // 如果我们从应用程序中调用任意命令，我们将加载所有可用的延迟提供程序，它们将执行所有命令应用程序可用
        // 否则，命令将不可用。
        //
        $this->app->loadDeferredProviders();//加载和启动所有剩余的延迟供应者
    }

    /**
     * Get the Artisan application instance.
     *
     * 获得Artisan应用程序实例
     *
     * @return \Illuminate\Console\Application
     */
    protected function getArtisan()
    {
        if (is_null($this->artisan)) {
            //                        创建一个新的Artisan控制台应用程序               获得应用程序的版本号
            return $this->artisan = (new Artisan($this->app, $this->events, $this->app->version()))
                                ->resolveCommands($this->commands);//通过应用程序解析命令数组
        }

        return $this->artisan;
    }

    /**
     * Set the Artisan application instance.
     *
     * 设置Artisan应用程序实例
     *
     * @param  \Illuminate\Console\Application  $artisan
     * @return void
     */
    public function setArtisan($artisan)
    {
        $this->artisan = $artisan;
    }

    /**
     * Get the bootstrap classes for the application.
     *
     * 获取应用程序的引导类
     *
     * @return array
     */
    protected function bootstrappers()
    {
        return $this->bootstrappers;
    }

    /**
     * Report the exception to the exception handler.
     *
     * 向异常处理程序报告异常
     *
     * @param  \Exception  $e
     * @return void
     */
    protected function reportException(Exception $e)
    {
        //报告或记录异常
        $this->app[ExceptionHandler::class]->report($e);
    }

    /**
     * Report the exception to the exception handler.
     *
     * 向异常处理程序报告异常
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Exception  $e
     * @return void
     */
    protected function renderException($output, Exception $e)
    {
        //在控制台中呈现异常
        $this->app[ExceptionHandler::class]->renderForConsole($output, $e);
    }
}
