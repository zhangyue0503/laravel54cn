<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Command;
//计划完成命令
class ScheduleFinishCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $signature = 'schedule:finish {id}';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Handle the completion of a scheduled command';

    /**
     * Indicates whether the command should be shown in the Artisan command list.
     *
     * 指示该命令是否应该显示在工匠命令列表中
     *
     * @var bool
     */
    protected $hidden = true;

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
        //                把所有的事件都安排好->在每个项目上运行过滤器
        collect($this->schedule->events())->filter(function ($value) {
            //      为调度的命令获取互斥的名称       获取一个命令参数的值
            return $value->mutexName() == $this->argument('id');
        })->each->callAfterCallbacks($this->laravel);//为事件调用所有的“after”回调
    }
}
