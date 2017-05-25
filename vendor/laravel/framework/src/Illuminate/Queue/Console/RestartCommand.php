<?php

namespace Illuminate\Queue\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
//重启命令
class RestartCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'queue:restart';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Restart queue worker daemons after their current job';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        //                  在缓存中无限期地存储一个项                    获取当前日期和时间的Carbon实例
        $this->laravel['cache']->forever('illuminate:queue:restart', Carbon::now()->getTimestamp());
        //将字符串写入信息输出
        $this->info('Broadcasting queue restart signal.');
    }
}
