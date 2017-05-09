<?php

namespace Illuminate\Console\Scheduling;

use Closure;
use Carbon\Carbon;
use Cron\CronExpression;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Mail\Mailer;
use Symfony\Component\Process\Process;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;

class Event
{
    use Macroable, ManagesFrequencies;

    /**
     * The cache store implementation.
     *
     * 缓存存储实现
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The command string.
     *
     * 命令字符串
     *
     * @var string
     */
    public $command;

    /**
     * The cron expression representing the event's frequency.
     *
     * 表示事件频率的cron表达式
     *
     * @var string
     */
    public $expression = '* * * * * *';

    /**
     * The timezone the date should be evaluated on.
     *
     * 时间区域应该对日期进行评估
     *
     * @var \DateTimeZone|string
     */
    public $timezone;

    /**
     * The user the command should run as.
     *
     * 该命令的用户应该运行
     *
     * @var string
     */
    public $user;

    /**
     * The list of environments the command should run under.
     *
     * 命令应该在下面运行的环境列表
     *
     * @var array
     */
    public $environments = [];

    /**
     * Indicates if the command should run in maintenance mode.
     *
     * 指示该命令是否应该在维护模式下运行
     *
     * @var bool
     */
    public $evenInMaintenanceMode = false;

    /**
     * Indicates if the command should not overlap itself.
     *
     * 指示该命令是否不应该重叠
     *
     * @var bool
     */
    public $withoutOverlapping = false;

    /**
     * Indicates if the command should run in background.
     *
     * 指示该命令是否应该在后台运行
     *
     * @var bool
     */
    public $runInBackground = false;

    /**
     * The array of filter callbacks.
     *
     * 筛选回调的数组
     *
     * @var array
     */
    protected $filters = [];

    /**
     * The array of reject callbacks.
     *
     * 拒绝回调的数组
     *
     * @var array
     */
    protected $rejects = [];

    /**
     * The location that output should be sent to.
     *
     * 输出应该发送到的位置
     *
     * @var string
     */
    public $output = '/dev/null';

    /**
     * Indicates whether output should be appended.
     *
     * 表示是否应该添加输出
     *
     * @var bool
     */
    public $shouldAppendOutput = false;

    /**
     * The array of callbacks to be run before the event is started.
     *
     * 在事件开始之前运行的回调数组
     *
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * The array of callbacks to be run after the event is finished.
     *
     * 事件结束后要运行的回调数组
     *
     * @var array
     */
    protected $afterCallbacks = [];

    /**
     * The human readable description of the event.
     *
     * 人类可读的事件描述
     *
     * @var string
     */
    public $description;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @param  string  $command
     * @return void
     */
    public function __construct(Cache $cache, $command)
    {
        $this->cache = $cache;
        $this->command = $command;
        //根据操作系统获得默认的输出
        $this->output = $this->getDefaultOutput();
    }

    /**
     * Get the default output depending on the OS.
     *
     * 根据操作系统获得默认的输出
     *
     * @return string
     */
    public function getDefaultOutput()
    {
        return (DIRECTORY_SEPARATOR == '\\') ? 'NUL' : '/dev/null';
    }

    /**
     * Run the given event.
     *
     * 运行给定的事件
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function run(Container $container)
    {
        if ($this->withoutOverlapping) {
            //在缓存中存储一个项      为调度的命令获取互斥的名称
            $this->cache->put($this->mutexName(), true, 1440);
        }

        $this->runInBackground
                    ? $this->runCommandInBackground($container)//在后台运行命令
                    : $this->runCommandInForeground($container);//在前台运行命令
    }

    /**
     * Get the mutex name for the scheduled command.
     *
     * 为调度的命令获取互斥的名称
     *
     * @return string
     */
    public function mutexName()
    {
        return 'framework'.DIRECTORY_SEPARATOR.'schedule-'.sha1($this->expression.$this->command);
    }

    /**
     * Run the command in the foreground.
     *
     * 在前台运行命令
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    protected function runCommandInForeground(Container $container)
    {
        //为事件调用所有的“前”回调
        $this->callBeforeCallbacks($container);

        (new Process(
            //构建命令字符串
            $this->buildCommand(), base_path(), null, null, null
        ))->run();//运行进程
        //为事件调用所有的“after”回调
        $this->callAfterCallbacks($container);
    }

    /**
     * Run the command in the background.
     *
     * 在后台运行命令
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    protected function runCommandInBackground(Container $container)
    {
        //为事件调用所有的“前”回调
        $this->callBeforeCallbacks($container);

        (new Process(
            //构建命令字符串
            $this->buildCommand(), base_path(), null, null, null
        ))->run();//运行进程
    }

    /**
     * Call all of the "before" callbacks for the event.
     *
     * 为事件调用所有的“前”回调
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function callBeforeCallbacks(Container $container)
    {
        foreach ($this->beforeCallbacks as $callback) {
            $container->call($callback);//调用给定的闭包/类@方法并注入它的依赖项
        }
    }

    /**
     * Call all of the "after" callbacks for the event.
     *
     * 为事件调用所有的“after”回调
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function callAfterCallbacks(Container $container)
    {
        foreach ($this->afterCallbacks as $callback) {
            $container->call($callback);//调用给定的闭包/类@方法并注入它的依赖项
        }
    }

    /**
     * Build the command string.
     *
     * 构建命令字符串
     *
     * @return string
     */
    public function buildCommand()
    {
        //                           为给定的事件构建命令
        return (new CommandBuilder)->buildCommand($this);
    }

    /**
     * Determine if the given event should run based on the Cron expression.
     *
     * 确定给定事件是否应该基于Cron表达式运行
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return bool
     */
    public function isDue($app)
    {
        //确定事件是否在维护模式下运行                 确定应用程序当前是否用于维护
        if (! $this->runsInMaintenanceMode() && $app->isDownForMaintenance()) {
            return false;
        }
        //确定Cron的表达是否通过
        return $this->expressionPasses() &&
            //确定事件是否在给定的环境中运行(获取或检查当前的应用程序环境)
               $this->runsInEnvironment($app->environment());
    }

    /**
     * Determine if the event runs in maintenance mode.
     *
     * 确定事件是否在维护模式下运行
     *
     * @return bool
     */
    public function runsInMaintenanceMode()
    {
        return $this->evenInMaintenanceMode;
    }

    /**
     * Determine if the Cron expression passes.
     *
     * 确定Cron的表达是否通过
     *
     * @return bool
     */
    protected function expressionPasses()
    {
        //获取当前日期和时间的Carbon实例
        $date = Carbon::now();

        if ($this->timezone) {
            //从字符串或对象中设置实例的时区
            $date->setTimezone($this->timezone);
        }

        return CronExpression::factory($this->expression)->isDue($date->toDateTimeString());
    }

    /**
     * Determine if the event runs in the given environment.
     *
     * 确定事件是否在给定的环境中运行
     *
     * @param  string  $environment
     * @return bool
     */
    public function runsInEnvironment($environment)
    {
        return empty($this->environments) || in_array($environment, $this->environments);
    }

    /**
     * Determine if the filters pass for the event.
     *
     * 确定过滤器是否通过了事件
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return bool
     */
    public function filtersPass($app)
    {
        foreach ($this->filters as $callback) {
            //调用给定的闭包/类@方法并注入它的依赖项
            if (! $app->call($callback)) {
                return false;
            }
        }

        foreach ($this->rejects as $callback) {
            //调用给定的闭包/类@方法并注入它的依赖项
            if ($app->call($callback)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Send the output of the command to a given location.
     *
     * 将命令的输出发送到给定的位置
     *
     * @param  string  $location
     * @param  bool  $append
     * @return $this
     */
    public function sendOutputTo($location, $append = false)
    {
        $this->output = $location;

        $this->shouldAppendOutput = $append;

        return $this;
    }

    /**
     * Append the output of the command to a given location.
     *
     * 将命令的输出附加到给定的位置
     *
     * @param  string  $location
     * @return $this
     */
    public function appendOutputTo($location)
    {
        //将命令的输出发送到给定的位置
        return $this->sendOutputTo($location, true);
    }

    /**
     * E-mail the results of the scheduled operation.
     *
     * 电子邮件预定操作的结果
     *
     * @param  array|mixed  $addresses
     * @param  bool  $onlyIfOutputExists
     * @return $this
     *
     * @throws \LogicException
     */
    public function emailOutputTo($addresses, $onlyIfOutputExists = false)
    {
        //确保通过电子邮件获取输出
        $this->ensureOutputIsBeingCapturedForEmail();

        $addresses = is_array($addresses) ? $addresses : func_get_args();
        //注册一个回调，以便在操作之后被调用
        return $this->then(function (Mailer $mailer) use ($addresses, $onlyIfOutputExists) {
            //电子邮件收件人的输出事件
            $this->emailOutput($mailer, $addresses, $onlyIfOutputExists);
        });
    }

    /**
     * E-mail the results of the scheduled operation if it produces output.
     *
     * 电子邮件预定操作的结果如果它产生输出
     *
     * @param  array|mixed  $addresses
     * @return $this
     *
     * @throws \LogicException
     */
    public function emailWrittenOutputTo($addresses)
    {
        //电子邮件预定操作的结果
        return $this->emailOutputTo($addresses, true);
    }

    /**
     * Ensure that output is being captured for email.
     *
     * 确保通过电子邮件获取输出
     *
     * @return void
     */
    protected function ensureOutputIsBeingCapturedForEmail()
    {
        //                                               根据操作系统获得默认的输出
        if (is_null($this->output) || $this->output == $this->getDefaultOutput()) {
            //将命令的输出发送到给定的位置                         为调度的命令获取互斥的名称
            $this->sendOutputTo(storage_path('logs/schedule-'.sha1($this->mutexName()).'.log'));
        }
    }

    /**
     * E-mail the output of the event to the recipients.
     *
     * 电子邮件收件人的输出事件
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @param  array  $addresses
     * @param  bool  $onlyIfOutputExists
     * @return void
     */
    protected function emailOutput(Mailer $mailer, $addresses, $onlyIfOutputExists = false)
    {
        $text = file_get_contents($this->output);

        if ($onlyIfOutputExists && empty($text)) {
            return;
        }
        //仅在原始文本部分发送一条新消息
        $mailer->raw($text, function ($m) use ($addresses) {
            //                            获取输出结果的电子邮件主题行
            $m->to($addresses)->subject($this->getEmailSubject());
        });
    }

    /**
     * Get the e-mail subject line for output results.
     *
     * 获取输出结果的电子邮件主题行
     *
     * @return string
     */
    protected function getEmailSubject()
    {
        if ($this->description) {
            return $this->description;
        }

        return 'Scheduled Job Output';
    }

    /**
     * Register a callback to ping a given URL before the job runs.
     *
     * 在作业运行之前，注册一个回调来ping一个给定的URL
     *
     * @param  string  $url
     * @return $this
     */
    public function pingBefore($url)
    {
        //在操作之前注册要调用的回调
        return $this->before(function () use ($url) {
            (new HttpClient)->get($url);
        });
    }

    /**
     * Register a callback to ping a given URL after the job runs.
     *
     * 在作业运行后，注册一个回调以ping一个给定的URL
     *
     * @param  string  $url
     * @return $this
     */
    public function thenPing($url)
    {
        //注册一个回调，以便在操作之后被调用
        return $this->then(function () use ($url) {
            (new HttpClient)->get($url);
        });
    }

    /**
     * State that the command should run in background.
     *
     * 声明该命令应该在后台运行
     *
     * @return $this
     */
    public function runInBackground()
    {
        $this->runInBackground = true;

        return $this;
    }

    /**
     * Set which user the command should run as.
     *
     * 设置该命令应该运行的用户
     *
     * @param  string  $user
     * @return $this
     */
    public function user($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Limit the environments the command should run in.
     *
     * 限制命令应该运行的环境
     *
     * @param  array|mixed  $environments
     * @return $this
     */
    public function environments($environments)
    {
        $this->environments = is_array($environments) ? $environments : func_get_args();

        return $this;
    }

    /**
     * State that the command should run even in maintenance mode.
     *
     * 声明命令应该在维护模式下运行
     *
     * @return $this
     */
    public function evenInMaintenanceMode()
    {
        $this->evenInMaintenanceMode = true;

        return $this;
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * 不要让事件相互重叠
     *
     * @return $this
     */
    public function withoutOverlapping()
    {
        $this->withoutOverlapping = true;
        //注册一个回调，以便在操作之后被调用
        return $this->then(function () {
            //从缓存中删除一个项目(为调度的命令获取互斥的名称)
            $this->cache->forget($this->mutexName());
            //注册一个回调以进一步筛选调度
        })->skip(function () {
            //确定缓存中是否存在某个项(为调度的命令获取互斥的名称)
            return $this->cache->has($this->mutexName());
        });
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * 注册一个回调以进一步筛选调度
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function when(Closure $callback)
    {
        $this->filters[] = $callback;

        return $this;
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * 注册一个回调以进一步筛选调度
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function skip(Closure $callback)
    {
        $this->rejects[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called before the operation.
     *
     * 在操作之前注册要调用的回调
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function before(Closure $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called after the operation.
     *
     * 注册一个回调，以便在操作之后被调用
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function after(Closure $callback)
    {
        //注册一个回调，以便在操作之后被调用
        return $this->then($callback);
    }

    /**
     * Register a callback to be called after the operation.
     *
     * 注册一个回调，以便在操作之后被调用
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function then(Closure $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Set the human-friendly description of the event.
     *
     * 对事件进行友好的描述
     *
     * @param  string  $description
     * @return $this
     */
    public function name($description)
    {
        //对事件进行友好的描述
        return $this->description($description);
    }

    /**
     * Set the human-friendly description of the event.
     *
     * 对事件进行友好的描述
     *
     * @param  string  $description
     * @return $this
     */
    public function description($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the summary of the event for display.
     *
     * 获取显示事件的摘要
     *
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) {
            return $this->description;
        }
        //构建命令字符串
        return $this->buildCommand();
    }

    /**
     * Get the Cron expression for the event.
     *
     * 得到Cron的表达式
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }
}
