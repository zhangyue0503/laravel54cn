<?php

namespace Illuminate\Queue;

use Closure;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;
use Symfony\Component\Process\PhpExecutableFinder;

class Listener
{
    /**
     * The command working path.
     *
     * 命令工作路径
     *
     * @var string
     */
    protected $commandPath;

    /**
     * The environment the workers should run under.
     *
     * 工人们应该管理的环境
     *
     * @var string
     */
    protected $environment;

    /**
     * The amount of seconds to wait before polling the queue.
     *
     * 在轮询队列之前等待的秒数
     *
     * @var int
     */
    protected $sleep = 3;

    /**
     * The amount of times to try a job before logging it failed.
     *
     * 在日志记录失败之前尝试工作的次数
     *
     * @var int
     */
    protected $maxTries = 0;

    /**
     * The queue worker command line.
     *
     * 队列工人命令行
     *
     * @var string
     */
    protected $workerCommand;

    /**
     * The output handler callback.
     *
     * 输出处理程序回调
     *
     * @var \Closure|null
     */
    protected $outputHandler;

    /**
     * Create a new queue listener.
     *
     * 创建一个新的队列侦听器
     *
     * @param  string  $commandPath
     * @return void
     */
    public function __construct($commandPath)
    {
        $this->commandPath = $commandPath;
        //                   构建特定于环境的工人命令
        $this->workerCommand = $this->buildCommandTemplate();
    }

    /**
     * Build the environment specific worker command.
     *
     * 构建特定于环境的工人命令
     *
     * @return string
     */
    protected function buildCommandTemplate()
    {
        $command = 'queue:work %s --once --queue=%s --delay=%s --memory=%s --sleep=%s --tries=%s';
        //    获得PHP二进制                获取Artisan二进制
        return "{$this->phpBinary()} {$this->artisanBinary()} {$command}";
    }

    /**
     * Get the PHP binary.
     *
     * 获得PHP二进制
     *
     * @return string
     */
    protected function phpBinary()
    {
        //                   转义字符串用作shell参数
        return ProcessUtils::escapeArgument(
            (new PhpExecutableFinder)->find(false)// 发现PHP可执行文件
        );
    }

    /**
     * Get the Artisan binary.
     *
     * 获取Artisan二进制
     *
     * @return string
     */
    protected function artisanBinary()
    {
        return defined('ARTISAN_BINARY')
                        ? ProcessUtils::escapeArgument(ARTISAN_BINARY)//转义字符串用作shell参数
                        : 'artisan';
    }

    /**
     * Listen to the given queue connection.
     *
     * 监听给定的队列连接
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Illuminate\Queue\ListenerOptions  $options
     * @return void
     */
    public function listen($connection, $queue, ListenerOptions $options)
    {
        //为工人创建一个新的Symfony流程
        $process = $this->makeProcess($connection, $queue, $options);

        while (true) {
            $this->runProcess($process, $options->memory);//运行给定的过程
        }
    }

    /**
     * Create a new Symfony process for the worker.
     *
     * 为工人创建一个新的Symfony流程
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Illuminate\Queue\ListenerOptions  $options
     * @return \Symfony\Component\Process\Process
     */
    public function makeProcess($connection, $queue, ListenerOptions $options)
    {
        $command = $this->workerCommand;

        // If the environment is set, we will append it to the command string so the
        // workers will run under the specified environment. Otherwise, they will
        // just run under the production environment which is not always right.
        //
        // 如果设置了环境，我们将把它附加到命令字符串，这样工人就会在指定的环境下运行
        // 否则，它们就会在生产环境下运行，而这种环境并不总是正确的
        //
        if (isset($options->environment)) {
            //                 向给定的命令添加环境选项
            $command = $this->addEnvironment($command, $options);
        }

        // Next, we will just format out the worker commands with all of the various
        // options available for the command. This will produce the final command
        // line that we will pass into a Symfony process object for processing.
        //
        // 接下来，我们将使用命令的所有可用选项来格式化工人命令
        // 这将生成最终的命令行，我们将把它传递给一个Symfony进程对象进行处理
        //
        //              使用侦听器选项格式化给定的命令
        $command = $this->formatCommand(
            $command, $connection, $queue, $options
        );

        return new Process(
            $command, $this->commandPath, null, null, $options->timeout
        );
    }

    /**
     * Add the environment option to the given command.
     *
     * 向给定的命令添加环境选项
     *
     * @param  string  $command
     * @param  \Illuminate\Queue\ListenerOptions  $options
     * @return string
     */
    protected function addEnvironment($command, ListenerOptions $options)
    {
        //                                 转义字符串用作shell参数
        return $command.' --env='.ProcessUtils::escapeArgument($options->environment);
    }

    /**
     * Format the given command with the listener options.
     *
     * 使用侦听器选项格式化给定的命令
     *
     * @param  string  $command
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Illuminate\Queue\ListenerOptions  $options
     * @return string
     */
    protected function formatCommand($command, $connection, $queue, ListenerOptions $options)
    {
        return sprintf(
            $command,
            ProcessUtils::escapeArgument($connection),//转义字符串用作shell参数
            ProcessUtils::escapeArgument($queue),
            $options->delay, $options->memory,
            $options->sleep, $options->maxTries
        );
    }

    /**
     * Run the given process.
     *
     * 运行给定的过程
     *
     * @param  \Symfony\Component\Process\Process  $process
     * @param  int  $memory
     * @return void
     */
    public function runProcess(Process $process, $memory)
    {
        //运行进程
        $process->run(function ($type, $line) {
            //处理来自工人进程的输出
            $this->handleWorkerOutput($type, $line);
        });

        // Once we have run the job we'll go check if the memory limit has been exceeded
        // for the script. If it has, we will kill this script so the process manager
        // will restart this with a clean slate of memory automatically on exiting.
        //
        // 一旦我们运行了这个任务，我们就会检查脚本是否已经超出了内存限制
        // 如果有，我们将终止这个脚本，以便进程管理器将在退出时自动地重新启动这个脚本
        //
        //       确定是否已经超过了内存限制
        if ($this->memoryExceeded($memory)) {
            $this->stop();// 停止监听，摆脱脚本
        }
    }

    /**
     * Handle output from the worker process.
     *
     * 处理来自工人进程的输出
     *
     * @param  int  $type
     * @param  string  $line
     * @return void
     */
    protected function handleWorkerOutput($type, $line)
    {
        if (isset($this->outputHandler)) {
            call_user_func($this->outputHandler, $type, $line);
        }
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * 确定是否已经超过了内存限制
     *
     * @param  int  $memoryLimit
     * @return bool
     */
    public function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Stop listening and bail out of the script.
     *
     * 停止监听，摆脱脚本
     *
     * @return void
     */
    public function stop()
    {
        die;
    }

    /**
     * Set the output handler callback.
     *
     * 设置输出处理程序回调
     *
     * @param  \Closure  $outputHandler
     * @return void
     */
    public function setOutputHandler(Closure $outputHandler)
    {
        $this->outputHandler = $outputHandler;
    }
}
