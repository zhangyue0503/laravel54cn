<?php

namespace Illuminate\Queue\Console;

use Illuminate\Queue\Listener;
use Illuminate\Console\Command;
use Illuminate\Queue\ListenerOptions;

class ListenCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $signature = 'queue:listen
                            {connection? : The name of connection}
                            {--delay=0 : Amount of time to delay failed jobs}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--queue= : The queue to listen on}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=0 : Number of times to attempt a job before logging it failed}';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Listen to a given queue';

    /**
     * The queue listener instance.
     *
     * 队列监听实例
     *
     * @var \Illuminate\Queue\Listener
     */
    protected $listener;

    /**
     * Create a new queue listen command.
     *
     * 创建一个新的队列监听命令
     *
     * @param  \Illuminate\Queue\Listener  $listener
     * @return void
     */
    public function __construct(Listener $listener)
    {
        parent::__construct();//创建一个新的控制台命令实例
        //在队列侦听器上设置选项
        $this->setOutputHandler($this->listener = $listener);
    }

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        //
        // 我们需要为应用程序设置队列配置文件中设置的连接的正确队列
        // 我们将基于当前正在执行的队列操作的设置连接来拉它
        //
        //          获取要监听的队列连接的名称
        $queue = $this->getQueue(
            //                      返回给定参数名的参数值
            $connection = $this->input->getArgument('connection')
        );
        //          监听给定的队列连接
        $this->listener->listen(
            //                    获取命令的侦听器选项
            $connection, $queue, $this->gatherOptions()
        );
    }

    /**
     * Get the name of the queue connection to listen on.
     *
     * 获取要监听的队列连接的名称
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        $connection = $connection ?: $this->laravel['config']['queue.default'];
        //            返回给定选项名的选项值
        return $this->input->getOption('queue') ?: $this->laravel['config']->get(
            "queue.connections.{$connection}.queue", 'default'
        );
    }

    /**
     * Get the listener options for the command.
     *
     * 获取命令的侦听器选项
     *
     * @return \Illuminate\Queue\ListenerOptions
     */
    protected function gatherOptions()
    {
        //创建一个新的侦听器选项实例
        return new ListenerOptions(
            //获取命令选项的值
            $this->option('env'), $this->option('delay'),
            $this->option('memory'), $this->option('timeout'),
            $this->option('sleep'), $this->option('tries'),
            $this->option('force')
        );
    }

    /**
     * Set the options on the queue listener.
     *
     * 在队列侦听器上设置选项
     *
     * @param  \Illuminate\Queue\Listener  $listener
     * @return void
     */
    protected function setOutputHandler(Listener $listener)
    {
        //设置输出处理程序回调
        $listener->setOutputHandler(function ($type, $line) {
            $this->output->write($line);
        });
    }
}
