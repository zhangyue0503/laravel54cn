<?php

namespace Illuminate\Queue;

use Exception;
use Throwable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class Worker
{
    /**
     * The queue manager instance.
     *
     * 队列管理器实例
     *
     * @var \Illuminate\Queue\QueueManager
     */
    protected $manager;

    /**
     * The event dispatcher instance.
     *
     * 事件调度器实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The cache repository implementation.
     *
     * 缓存库实现
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The exception handler instance.
     *
     * 异常处理程序实例
     *
     * @var \Illuminate\Foundation\Exceptions\Handler
     */
    protected $exceptions;

    /**
     * Indicates if the worker should exit.
     *
     * 指示工人是否应该退出
     *
     * @var bool
     */
    public $shouldQuit = false;

    /**
     * Indicates if the worker is paused.
     *
     * 指示工人是否暂停工作
     *
     * @var bool
     */
    public $paused = false;

    /**
     * Create a new queue worker.
     *
     * 创建一个新的队列工作者
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $exceptions
     * @return void
     */
    public function __construct(QueueManager $manager,
                                Dispatcher $events,
                                ExceptionHandler $exceptions)
    {
        $this->events = $events;
        $this->manager = $manager;
        $this->exceptions = $exceptions;
    }

    /**
     * Listen to the given queue in a loop.
     *
     * 在循环中监听给定的队列
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    public function daemon($connectionName, $queue, WorkerOptions $options)
    {
        $this->listenForSignals();//为流程启用异步信号

        $lastRestart = $this->getTimestampOfLastQueueRestart();//获取最后一个队列重新启动时间戳，或null

        while (true) {
            // Before reserving any jobs, we will make sure this queue is not paused and
            // if it is we will just pause this worker for a given amount of time and
            // make sure we do not need to kill this worker process off completely.
            //
            // 在保留任何作业之前，我们将确保该队列没有暂停，如果是这样，我们将在给定的时间内暂停这个工作，并确保我们不需要完全终止这个工作进程
            //
            //       确定这个守护进程是否应该在这个迭代过程中进行处理
            if (! $this->daemonShouldRun($options)) {
                //为当前循环暂停工作
                $this->pauseWorker($options, $lastRestart);

                continue;
            }

            // First, we will attempt to get the next job off of the queue. We will also
            // register the timeout handler and reset the alarm for this job so it is
            // not stuck in a frozen state forever. Then, we can fire off this job.
            //
            // 首先，我们将尝试从队列中获取下一个作业。我们还将注册超时处理程序，并为该作业重新设置警报，这样它就不会永远处于冻结状态
            // 然后，我们可以解雇这份工作
            //
            //         从队列连接获取下一个作业
            $job = $this->getNextJob(
                //解析队列连接实例
                $this->manager->connection($connectionName), $queue
            );
            //注册工人超时处理程序(PHP 7.1+)
            $this->registerTimeoutHandler($job, $options);

            // If the daemon should run (not in maintenance mode, etc.), then we can run
            // fire off this job for processing. Otherwise, we will need to sleep the
            // worker so no more jobs are processed until they should be processed.
            //
            // 如果这个守护进程运行(不是在维护模式，等等)，那么我们可以对这个作业进行处理以进行处理
            // 否则，我们将需要对工人进行睡眠，这样就不会再处理作业，直到处理完毕
            //
            if ($job) {
                //过程中给定的工作
                $this->runJob($job, $connectionName, $options);
            } else {
                //在给定的时间内休眠脚本
                $this->sleep($options->sleep);
            }

            // Finally, we will check to see if we have exceeded our memory limits or if
            // the queue should restart based on other indications. If so, we'll stop
            // this worker and let whatever is "monitoring" it restart the process.
            //
            // 最后，我们将检查是否已经超出了我们的内存限制，或者是否应该根据其他指示重新启动队列
            // 如果是这样，我们将停止该工作，并让任何“监视”它重新启动该进程
            //
            //     如果有必要，停止这个过程
            $this->stopIfNecessary($options, $lastRestart);
        }
    }

    /**
     * Register the worker timeout handler (PHP 7.1+).
     *
     * 注册工人超时处理程序(PHP 7.1+)
     *
     * @param  \Illuminate\Contracts\Queue\Job|null  $job
     * @param  WorkerOptions  $options
     * @return void
     */
    protected function registerTimeoutHandler($job, WorkerOptions $options)
    {
        //                                 确定是否支持“async”信号
        if ($options->timeout > 0 && $this->supportsAsyncSignals()) {
            // We will register a signal handler for the alarm signal so that we can kill this
            // process if it is running too long because it has frozen. This uses the async
            // signals supported in recent versions of PHP to accomplish it conveniently.
            //
            // 我们将为警报信号注册一个信号处理程序，这样如果运行时间过长，我们就可以终止这个进程，因为它已经被冻结了
            // 这使用了最近PHP版本中支持的异步信号，以方便地完成它
            //
            pcntl_signal(SIGALRM, function () {
                $this->kill(1);//杀了这个过程
            });
            //                为给定的作业获得适当的超时
            pcntl_alarm($this->timeoutForJob($job, $options) + $options->sleep);
        }
    }

    /**
     * Get the appropriate timeout for the given job.
     *
     * 为给定的作业获得适当的超时
     *
     * @param  \Illuminate\Contracts\Queue\Job|null  $job
     * @param  WorkerOptions  $options
     * @return int
     */
    protected function timeoutForJob($job, WorkerOptions $options)
    {
        //                      工作可以运行的秒数
        return $job && ! is_null($job->timeout()) ? $job->timeout() : $options->timeout;
    }

    /**
     * Determine if the daemon should process on this iteration.
     *
     * 确定这个守护进程是否应该在这个迭代过程中进行处理
     *
     * @param  WorkerOptions  $options
     * @return bool
     */
    protected function daemonShouldRun(WorkerOptions $options)
    {
        //                         确定应用程序是否处于维护模式
        return ! (($this->manager->isDownForMaintenance() && ! $options->force) ||
            $this->paused ||
            //        发送事件并调用侦听器
            $this->events->until(new Events\Looping) === false);
    }

    /**
     * Pause the worker for the current loop.
     *
     * 为当前循环暂停工作
     *
     * @param  WorkerOptions  $options
     * @param  int  $lastRestart
     * @return void
     */
    protected function pauseWorker(WorkerOptions $options, $lastRestart)
    {
        //在给定的时间内休眠脚本
        $this->sleep($options->sleep > 0 ? $options->sleep : 1);
        //如果有必要，停止这个过程
        $this->stopIfNecessary($options, $lastRestart);
    }

    /**
     * Stop the process if necessary.
     *
     * 如果有必要，停止这个过程
     *
     * @param  WorkerOptions  $options
     * @param  int  $lastRestart
     */
    protected function stopIfNecessary(WorkerOptions $options, $lastRestart)
    {
        if ($this->shouldQuit) {
            $this->kill();//杀了这个过程
        }
        //确定是否已经超过了内存限制
        if ($this->memoryExceeded($options->memory)) {
            $this->stop(12);//停止监听，摆脱脚本
        } elseif ($this->queueShouldRestart($lastRestart)) {
            $this->stop();
        }
    }

    /**
     * Process the next job on the queue.
     *
     * 处理队列上的下一个作业
     *
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    public function runNextJob($connectionName, $queue, WorkerOptions $options)
    {
        //从队列连接获取下一个作业
        $job = $this->getNextJob(
            //        解析队列连接实例
            $this->manager->connection($connectionName), $queue
        );

        // If we're able to pull a job off of the stack, we will process it and then return
        // from this method. If there is no job on the queue, we will "sleep" the worker
        // for the specified number of seconds, then keep processing jobs after sleep.
        //
        // 如果我们能够从堆栈中取出一个作业，我们将处理它，然后从这个方法返回
        // 如果队列中没有作业，我们将“休眠”该员工数秒，然后在睡眠后继续处理作业
        //
        if ($job) {
            //            过程中给定的工作
            return $this->runJob($job, $connectionName, $options);
        }
        //在给定的时间内休眠脚本
        $this->sleep($options->sleep);
    }

    /**
     * Get the next job from the queue connection.
     *
     * 从队列连接获取下一个作业
     *
     * @param  \Illuminate\Contracts\Queue\Queue  $connection
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    protected function getNextJob($connection, $queue)
    {
        try {
            foreach (explode(',', $queue) as $queue) {
                //                           从队列中取出下一个作业
                if (! is_null($job = $connection->pop($queue))) {
                    return $job;
                }
            }
        } catch (Exception $e) {
            //报告或记录异常
            $this->exceptions->report($e);
        } catch (Throwable $e) {
            $this->exceptions->report(new FatalThrowableError($e));
        }
    }

    /**
     * Process the given job.
     *
     * 过程中给定的工作
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  string  $connectionName
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    protected function runJob($job, $connectionName, WorkerOptions $options)
    {
        try {
            //从队列中获得给定的作业
            return $this->process($connectionName, $job, $options);
        } catch (Exception $e) {
            //报告或记录异常
            $this->exceptions->report($e);
        } catch (Throwable $e) {
            $this->exceptions->report(new FatalThrowableError($e));
        }
    }

    /**
     * Process the given job from the queue.
     *
     * 从队列中获得给定的作业
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     *
     * @throws \Throwable
     */
    public function process($connectionName, $job, WorkerOptions $options)
    {
        try {
            // First we will raise the before job event and determine if the job has already ran
            // over the its maximum attempt limit, which could primarily happen if the job is
            // continually timing out and not actually throwing any exceptions from itself.
            //
            // 首先，我们将提高之前的工作事件，并确定该职位是否已经超过了其最大限度的极限，如果这项工作不断地超时，而实际上并没有抛出任何异常，那么这一任务可能会发生
            //
            //    提高之前的队列作业事件
            $this->raiseBeforeJobEvent($connectionName, $job);
            //       如果超过了最大允许的尝试，就把给定的作业标记为失败
            $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
                $connectionName, $job, (int) $options->maxTries
            );

            // Here we will fire off the job and let it process. We will catch any exceptions so
            // they can be reported to the developers logs, etc. Once the job is finished the
            // proper events will be fired to let any listeners know this job has finished.
            //
            // 在这里，我们将解雇这份工作，并让它进行处理。我们将捕获任何异常，以便将它们报告给开发人员的日志，等等
            // 一旦工作完成，适当的事件将被触发，以让任何侦听器知道该工作已经完成
            //
            //      触发工作
            $job->fire();
            //提高后队列作业事件
            $this->raiseAfterJobEvent($connectionName, $job);
        } catch (Exception $e) {
            //处理作业运行时发生的异常
            $this->handleJobException($connectionName, $job, $options, $e);
        } catch (Throwable $e) {
            $this->handleJobException(
                $connectionName, $job, $options, new FatalThrowableError($e)
            );
        }
    }

    /**
     * Handle an exception that occurred while the job was running.
     *
     * 处理作业运行时发生的异常
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    protected function handleJobException($connectionName, $job, WorkerOptions $options, $e)
    {
        try {
            // First, we will go ahead and mark the job as failed if it will exceed the maximum
            // attempts it is allowed to run the next time we process it. If so we will just
            // go ahead and mark it as failed now so we do not have to release this again.
            //
            // 首先，我们将继续标记作业，如果它超过了我们下一次处理它时允许运行的最大尝试，那么它将会失败
            // 如果是这样的话，我们就把它标记为失败，这样我们就不用再发布了
            //
            //     如果超过了最大允许的尝试，就把给定的作业标记为失败
            $this->markJobAsFailedIfWillExceedMaxAttempts(
                $connectionName, $job, (int) $options->maxTries, $e
            );
            //提高异常发生队列作业事件
            $this->raiseExceptionOccurredJobEvent(
                $connectionName, $job, $e
            );
        } finally {
            // If we catch an exception, we will attempt to release the job back onto the queue
            // so it is not lost entirely. This'll let the job be retried at a later time by
            // another listener (or this same one). We will re-throw this exception after.
            //
            // 如果我们捕获一个异常，我们将尝试将作业重新发布到队列中，这样它就不会完全丢失
            // 这将使作业在稍后由另一个侦听器(或同样的)重新尝试。我们将在后面重新抛出这个异常
            //
            //      确定该作业是否已被删除
            if (! $job->isDeleted()) {
                //将作业放回队列中
                $job->release($options->delay);
            }
        }

        throw $e;
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * 如果超过了最大允许的尝试，就把给定的作业标记为失败
     *
     * This will likely be because the job previously exceeded a timeout.
     *
     * 这可能是因为之前的工作超过了超时
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  int  $maxTries
     * @return void
     */
    protected function markJobAsFailedIfAlreadyExceedsMaxAttempts($connectionName, $job, $maxTries)
    {
        //                    尝试一份工作的次数
        $maxTries = ! is_null($job->maxTries()) ? $job->maxTries() : $maxTries;
        //                     获得工作尝试过的次数
        if ($maxTries === 0 || $job->attempts() <= $maxTries) {
            return;
        }
        //将指定的工作标记为失败并提出相关事件
        $this->failJob($connectionName, $job, $e = new MaxAttemptsExceededException(
            'A queued job has been attempted too many times. The job may have previously timed out.'
        ));

        throw $e;
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * 如果超过了最大允许的尝试，就把给定的作业标记为失败
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  int  $maxTries
     * @param  \Exception  $e
     * @return void
     */
    protected function markJobAsFailedIfWillExceedMaxAttempts($connectionName, $job, $maxTries, $e)
    {
        //                        尝试一份工作的次数
        $maxTries = ! is_null($job->maxTries()) ? $job->maxTries() : $maxTries;
        //                    获得工作尝试过的次数
        if ($maxTries > 0 && $job->attempts() >= $maxTries) {
            //将指定的工作标记为失败并提出相关事件
            $this->failJob($connectionName, $job, $e);
        }
    }

    /**
     * Mark the given job as failed and raise the relevant event.
     *
     * 将指定的工作标记为失败并提出相关事件
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function failJob($connectionName, $job, $e)
    {
        //删除作业，调用“失败”方法，并提高失败的作业事件
        return FailingJob::handle($connectionName, $job, $e);
    }

    /**
     * Raise the before queue job event.
     *
     * 提高之前的队列作业事件
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseBeforeJobEvent($connectionName, $job)
    {
        //触发事件并调用监听器
        $this->events->fire(new Events\JobProcessing(
            $connectionName, $job
        ));
    }

    /**
     * Raise the after queue job event.
     *
     * 提高后队列作业事件
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseAfterJobEvent($connectionName, $job)
    {
        //触发事件并调用监听器
        $this->events->fire(new Events\JobProcessed(
            $connectionName, $job
        ));
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * 提高异常发生队列作业事件
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function raiseExceptionOccurredJobEvent($connectionName, $job, $e)
    {
        //触发事件并调用监听器
        $this->events->fire(new Events\JobExceptionOccurred(
            $connectionName, $job, $e
        ));
    }

    /**
     * Raise the failed queue job event.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function raiseFailedJobEvent($connectionName, $job, $e)
    {
        //触发事件并调用监听器
        $this->events->fire(new Events\JobFailed(
            $connectionName, $job, $e
        ));
    }

    /**
     * Determine if the queue worker should restart.
     *
     * 确定队列工作人员是否应该重新启动
     *
     * @param  int|null  $lastRestart
     * @return bool
     */
    protected function queueShouldRestart($lastRestart)
    {
        //获取最后一个队列重新启动时间戳，或null
        return $this->getTimestampOfLastQueueRestart() != $lastRestart;
    }

    /**
     * Get the last queue restart timestamp, or null.
     *
     * 获取最后一个队列重新启动时间戳，或null
     *
     * @return int|null
     */
    protected function getTimestampOfLastQueueRestart()
    {
        if ($this->cache) {
            //通过键从缓存中检索一个项
            return $this->cache->get('illuminate:queue:restart');
        }
    }

    /**
     * Enable async signals for the process.
     *
     * 为流程启用异步信号
     *
     * @return void
     */
    protected function listenForSignals()
    {
        //确定是否支持“async”信号
        if ($this->supportsAsyncSignals()) {
            pcntl_async_signals(true);

            pcntl_signal(SIGTERM, function () {
                $this->shouldQuit = true;
            });

            pcntl_signal(SIGUSR2, function () {
                $this->paused = true;
            });

            pcntl_signal(SIGCONT, function () {
                $this->paused = false;
            });
        }
    }

    /**
     * Determine if "async" signals are supported.
     *
     * 确定是否支持“async”信号
     *
     * @return bool
     */
    protected function supportsAsyncSignals()
    {
        return version_compare(PHP_VERSION, '7.1.0') >= 0 &&
               extension_loaded('pcntl');
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * 确定是否已经超过了内存限制
     *
     * @param  int   $memoryLimit
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
     * @param  int  $status
     * @return void
     */
    public function stop($status = 0)
    {
        //           触发事件并调用监听器
        $this->events->fire(new Events\WorkerStopping);

        exit($status);
    }

    /**
     * Kill the process.
     *
     * 杀了这个过程
     *
     * @param  int  $status
     * @return void
     */
    public function kill($status = 0)
    {
        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * 在给定的时间内休眠脚本
     *
     * @param  int   $seconds
     * @return void
     */
    public function sleep($seconds)
    {
        sleep($seconds);
    }

    /**
     * Set the cache repository implementation.
     *
     * 设置缓存存储库实现
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function setCache(CacheContract $cache)
    {
        $this->cache = $cache;
    }
}
