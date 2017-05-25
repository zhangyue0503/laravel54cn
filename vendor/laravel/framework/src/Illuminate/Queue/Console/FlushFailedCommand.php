<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
//刷新失败的命令
class FlushFailedCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'queue:flush';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Flush all of the failed queue jobs';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        $this->laravel['queue.failer']->flush();//从存储中清除所有失败的作业
        //将字符串写入信息输出
        $this->info('All failed jobs deleted successfully!');
    }
}
