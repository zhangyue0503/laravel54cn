<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
//调度运行命令
class ScheduleRunCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'schedule:run';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Run the scheduled commands';

    /**
     * The schedule instance.
     *
     * 调度实例
     *
     * @var \Illuminate\Console\Scheduling\Schedule
     */
    protected $schedule;

    /**
     * Create a new command instance.
     *
     * 创建一个新的命令实例
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
        //创建一个新的控制台命令实例
        parent::__construct();
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
        $eventsRan = false;
        //                      把所有的事情都安排在日程表上
        foreach ($this->schedule->dueEvents($this->laravel) as $event) {
            //确定过滤器是否通过了事件
            if (! $event->filtersPass($this->laravel)) {
                continue;
            }
            //将字符串作为标准输出写入
            $this->line('<info>Running scheduled command:</info> '.$event->getSummaryForDisplay());
            //运行给定的事件
            $event->run($this->laravel);

            $eventsRan = true;
        }

        if (! $eventsRan) {
            //将字符串写入信息输出
            $this->info('No scheduled commands are ready to run.');
        }
    }
}
