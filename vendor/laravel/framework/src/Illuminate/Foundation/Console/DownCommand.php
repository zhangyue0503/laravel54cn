<?php

namespace Illuminate\Foundation\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;

class DownCommand extends Command
{
    /**
     * The console command signature.
     *
     * 控制台命令签名
     *
     * @var string
     */
    protected $signature = 'down {--message= : The message for the maintenance mode. }
                                 {--retry= : The number of seconds after which the request may be retried.}';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Put the application into maintenance mode';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        file_put_contents(
            $this->laravel->storagePath().'/framework/down',//获取存储目录路径
            json_encode($this->getDownFilePayload(), JSON_PRETTY_PRINT)//将有效负载放到“down”文件中
        );

        $this->comment('Application is now in maintenance mode.');//编写一个字符串作为注释输出
    }

    /**
     * Get the payload to be placed in the "down" file.
     *
     * 将有效负载放到“down”文件中
     *
     * @return array
     */
    protected function getDownFilePayload()
    {
        return [
            'time' => Carbon::now()->getTimestamp(),//获取当前日期和时间的Carbon实例
            'message' => $this->option('message'),//获取命令选项的值
            'retry' => $this->getRetryTime(),
        ];
    }

    /**
     * Get the number of seconds the client should wait before retrying their request.
     *
     * 获得客户在重新尝试他们的请求之前应该等待的秒数
     *
     * @return int|null
     */
    protected function getRetryTime()
    {
        $retry = $this->option('retry');//获取命令选项的值

        return is_numeric($retry) && $retry > 0 ? (int) $retry : null;
    }
}
