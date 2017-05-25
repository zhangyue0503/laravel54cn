<?php

namespace Illuminate\Queue\Console;

use Carbon\Carbon;
use Illuminate\Queue\Worker;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;

class WorkCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $signature = 'queue:work
                            {connection? : The name of the queue connection to work}
                            {--queue= : The names of the queues to work}
                            {--daemon : Run the worker in daemon mode (Deprecated)}
                            {--once : Only process the next job on the queue}
                            {--delay=0 : Amount of time to delay failed jobs}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
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
    protected $description = 'Start processing jobs on the queue as a daemon';

    /**
     * The queue worker instance.
     *
     * 队列工人实例
     *
     * @var \Illuminate\Queue\Worker
     */
    protected $worker;

    /**
     * Create a new queue listen command.
     *
     * 创建一个新的队列监听命令
     *
     * @param  \Illuminate\Queue\Worker  $worker
     * @return void
     */
    public function __construct(Worker $worker)
    {
        parent::__construct();//创建一个新的控制台命令实例

        $this->worker = $worker;
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
        //     确定该工人是否应该在维护模式下运行    获取命令选项的值
        if ($this->downForMaintenance() && $this->option('once')) {
            //          在给定的时间内休眠脚本
            return $this->worker->sleep($this->option('sleep'));
        }

        // We'll listen to the processed and failed events so we can write information
        // to the console as jobs are processed, which will let the developer watch
        // which jobs are coming through a queue and be informed on its progress.
        //
        // 我们将侦听处理过的和失败的事件，这样我们就可以在处理作业时将信息写入控制台，这样就可以让开发人员监视哪些作业通过队列，并了解其进展情况
        //
        //         监听队列事件，以更新控制台输出
        $this->listenForEvents();
        //                  获取一个命令参数的值
        $connection = $this->argument('connection')
                        ?: $this->laravel['config']['queue.default'];

        // We need to get the right queue for the connection which is set in the queue
        // configuration file for the application. We will pull it based on the set
        // connection being run for the queue operation currently being executed.
        //
        // 我们需要为应用程序设置队列配置文件中设置的连接的正确队列
        // 我们将基于当前正在执行的队列操作的设置连接来拉它
        //
        //            获取该worker的队列名称
        $queue = $this->getQueue($connection);
        //       运行工作实例
        $this->runWorker(
            $connection, $queue
        );
    }

    /**
     * Run the worker instance.
     *
     * 运行工作实例
     *
     * @param  string  $connection
     * @param  string  $queue
     * @return array
     */
    protected function runWorker($connection, $queue)
    {
        //         设置缓存存储库实现                   获取高速缓存驱动程序实例
        $this->worker->setCache($this->laravel['cache']->driver());
        //                       获取命令选项的值
        return $this->worker->{$this->option('once') ? 'runNextJob' : 'daemon'}(
            //                      将所有队列工作者选项集合为一个单一对象
            $connection, $queue, $this->gatherWorkerOptions()
        );
    }

    /**
     * Gather all of the queue worker options as a single object.
     *
     * 将所有队列工作者选项集合为一个单一对象
     *
     * @return \Illuminate\Queue\WorkerOptions
     */
    protected function gatherWorkerOptions()
    {
        //创建一个新的工作者选项实例
        return new WorkerOptions(
            //获取命令选项的值
            $this->option('delay'), $this->option('memory'),
            $this->option('timeout'), $this->option('sleep'),
            $this->option('tries'), $this->option('force')
        );
    }

    /**
     * Listen for the queue events in order to update the console output.
     *
     * 监听队列事件，以更新控制台输出
     *
     * @return void
     */
    protected function listenForEvents()
    {
        //                   用分配器注册事件监听器
        $this->laravel['events']->listen(JobProcessed::class, function ($event) {
            $this->writeOutput($event->job, false);//为队列工作人员编写状态输出
        });

        $this->laravel['events']->listen(JobFailed::class, function ($event) {
            $this->writeOutput($event->job, true);

            $this->logFailedJob($event);//存储一个失败的作业事件
        });
    }

    /**
     * Write the status output for the queue worker.
     *
     * 为队列工作人员编写状态输出
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  bool  $failed
     * @return void
     */
    protected function writeOutput(Job $job, $failed)
    {
        if ($failed) {
            //写信息到输出增加了最后一个换行符      获取当前日期和时间的Carbon实例                               获取队列作业类的解析名称
            $this->output->writeln('<error>['.Carbon::now()->format('Y-m-d H:i:s').'] Failed:</error> '.$job->resolveName());
        } else {
            $this->output->writeln('<info>['.Carbon::now()->format('Y-m-d H:i:s').'] Processed:</info> '.$job->resolveName());
        }
    }

    /**
     * Store a failed job event.
     *
     * 存储一个失败的作业事件
     *
     * @param  JobFailed  $event
     * @return void
     */
    protected function logFailedJob(JobFailed $event)
    {
        //                   将一个失败的作业记录到存储中
        $this->laravel['queue.failer']->log(
            //                           获取该作业所属的队列的名称
            $event->connectionName, $event->job->getQueue(),
            //     获取该作业的原始字符串
            $event->job->getRawBody(), $event->exception
        );
    }

    /**
     * Get the queue name for the worker.
     *
     * 获取该worker的队列名称
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        //        获取命令选项的值
        return $this->option('queue') ?: $this->laravel['config']->get(
            "queue.connections.{$connection}.queue", 'default'
        );
    }

    /**
     * Determine if the worker should run in maintenance mode.
     *
     * 确定该工人是否应该在维护模式下运行
     *
     * @return bool
     */
    protected function downForMaintenance()
    {
        //        获取命令选项的值                         确定应用程序当前是否用于维护
        return $this->option('force') ? false : $this->laravel->isDownForMaintenance();
    }
}
