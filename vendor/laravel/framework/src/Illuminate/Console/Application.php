<?php

namespace Illuminate\Console;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Process\ProcessUtils;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Illuminate\Contracts\Console\Application as ApplicationContract;

class Application extends SymfonyApplication implements ApplicationContract
{
    /**
     * The Laravel application instance.
     *
     * Laravel应用程序实例
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $laravel;

    /**
     * The output from the previous command.
     *
     * 来自前一个命令的输出
     *
     * @var \Symfony\Component\Console\Output\BufferedOutput
     */
    protected $lastOutput;

    /**
     * The console application bootstrappers.
     *
     * 控制台程序的启动加载器数组
     *
     * @var array
     */
    protected static $bootstrappers = [];

    /**
     * Create a new Artisan console application.
     *
     * 创建一个新的Artisan控制台应用程序
     *
     * @param  \Illuminate\Contracts\Container\Container  $laravel
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  string  $version
     * @return void
     */
    public function __construct(Container $laravel, Dispatcher $events, $version)
    {
        //Symfony\Component\Console\Application
        parent::__construct('Laravel Framework', $version);

        $this->laravel = $laravel;
        $this->setAutoExit(false);//设置是否在命令执行后自动退出
        $this->setCatchExceptions(false);//在命令执行过程中设置是否捕获异常
        //将事件触发，直到返回第一个非空响应
        $events->dispatch(new Events\ArtisanStarting($this));
        //引导控制台应用程序
        $this->bootstrap();
    }

    /**
     * Determine the proper PHP executable.
     *
     * 确定适当的PHP可执行文件
     *
     * @return string
     */
    public static function phpBinary()
    {
        //转义字符串用作shell参数                                         发现PHP可执行文件
        return ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false));
    }

    /**
     * Determine the proper Artisan executable.
     *
     * 确定合适的Artisan可执行程序
     *
     * @return string
     */
    public static function artisanBinary()
    {
        //                                   转义字符串用作shell参数
        return defined('ARTISAN_BINARY') ? ProcessUtils::escapeArgument(ARTISAN_BINARY) : 'artisan';
    }

    /**
     * Format the given command as a fully-qualified executable command.
     *
     * 将给定的命令格式化为完全限定的可执行命令
     *
     * @param  string  $string
     * @return string
     */
    public static function formatCommandString($string)
    {
        //                          确定适当的PHP可执行文件      确定合适的Artisan可执行程序
        return sprintf('%s %s %s', static::phpBinary(), static::artisanBinary(), $string);
    }

    /**
     * Register a console "starting" bootstrapper.
     *
     * 登记一个控制台的“starting”程序
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function starting(Closure $callback)
    {
        static::$bootstrappers[] = $callback;
    }

    /**
     * Bootstrap the console application.
     *
     * 引导控制台应用程序
     *
     * @return void
     */
    protected function bootstrap()
    {
        foreach (static::$bootstrappers as $bootstrapper) {
            $bootstrapper($this);
        }
    }

    /*
     * Clear the console application bootstrappers.
     *
     * 清除控制台应用程序启动加载器
     *
     * @return void
     */
    public static function forgetBootstrappers()
    {
        static::$bootstrappers = [];
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
        //                                将项目推到集合的开头
        $parameters = collect($parameters)->prepend($command);

        $this->lastOutput = $outputBuffer ?: new BufferedOutput;
        //在命令执行过程中设置是否捕获异常
        $this->setCatchExceptions(false);
        //运行当前的应用程序                          将项目的集合作为一个简单的数组
        $result = $this->run(new ArrayInput($parameters->toArray()), $this->lastOutput);
        //在命令执行过程中设置是否捕获异常
        $this->setCatchExceptions(true);

        return $result;
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
        //                                 清空缓冲区并返回其内容
        return $this->lastOutput ? $this->lastOutput->fetch() : '';
    }

    /**
     * Add a command to the console.
     *
     * 向控制台添加一个命令
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    public function add(SymfonyCommand $command)
    {
        if ($command instanceof Command) {
            //设置Laravel应用程序实例
            $command->setLaravel($this->laravel);
        }
        //将该命令添加到父实例
        return $this->addToParent($command);
    }

    /**
     * Add the command to the parent instance.
     *
     * 将该命令添加到父实例
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function addToParent(SymfonyCommand $command)
    {
        //添加一个命令对象
        return parent::add($command);
    }

    /**
     * Add a command, resolving through the application.
     *
     * 添加一个命令，通过应用程序解析
     *
     * @param  string  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    public function resolve($command)
    {
        //向控制台添加一个命令          从容器中解析给定类型
        return $this->add($this->laravel->make($command));
    }

    /**
     * Resolve an array of commands through the application.
     *
     * 通过应用程序解析命令数组
     *
     * @param  array|mixed  $commands
     * @return $this
     */
    public function resolveCommands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        foreach ($commands as $command) {
            //添加一个命令，通过应用程序解析
            $this->resolve($command);
        }

        return $this;
    }

    /**
     * Get the default input definitions for the applications.
     *
     * 获取应用程序的默认输入定义
     *
     * This is used to add the --env option to every available command.
     *
     * 这用于向每个可用的命令添加-env选项
     *
     * @return \Symfony\Component\Console\Input\InputDefinition
     */
    protected function getDefaultInputDefinition()
    {
        //用给定的值调用给定的闭包，然后返回值(获得默认输入定义,)
        return tap(parent::getDefaultInputDefinition(), function ($definition) {
            //                        为定义获取全局环境选项
            $definition->addOption($this->getEnvironmentOption());
        });
    }

    /**
     * Get the global environment option for the definition.
     *
     * 为定义获取全局环境选项
     *
     * @return \Symfony\Component\Console\Input\InputOption
     */
    protected function getEnvironmentOption()
    {
        $message = 'The environment the command should run under';
        //表示一个命令行选项
        return new InputOption('--env', null, InputOption::VALUE_OPTIONAL, $message);
    }

    /**
     * Get the Laravel application instance.
     *
     * Laravel应用程序实例
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getLaravel()
    {
        return $this->laravel;
    }
}
