<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
//移除失败的命令
class ForgetFailedCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'queue:forget';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Delete a failed queue job';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        //                        从存储中删除一个失败的作业       获取一个命令参数的值
        if ($this->laravel['queue.failer']->forget($this->argument('id'))) {
            //将字符串写入信息输出
            $this->info('Failed job deleted successfully!');
        } else {
            //将字符串写入错误输出
            $this->error('No failed job matches the given ID.');
        }
    }

    /**
     * Get the console command arguments.
     *
     * 获得控制台命令参数
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['id', InputArgument::REQUIRED, 'The ID of the failed job'],
        ];
    }
}
