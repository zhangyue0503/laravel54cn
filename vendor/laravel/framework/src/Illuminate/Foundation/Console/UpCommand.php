<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
//up命令
class UpCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'up';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Bring the application out of maintenance mode';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        //                    获取存储目录路径
        @unlink($this->laravel->storagePath().'/framework/down');
        //将字符串写入信息输出
        $this->info('Application is now live.');
    }
}
