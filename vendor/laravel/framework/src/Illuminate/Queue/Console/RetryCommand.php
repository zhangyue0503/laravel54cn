<?php

namespace Illuminate\Queue\Console;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
//重试命令
class RetryCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'queue:retry';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Retry a failed queue job';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        //           获得重新尝试的作业id
        foreach ($this->getJobIds() as $id) {
            $this->retryJob($id);//使用给定的ID重试队列作业
            //将字符串写入信息输出
            $this->info("The failed job [{$id}] has been pushed back onto the queue!");
            //                            从存储中删除一个失败的作业
            $this->laravel['queue.failer']->forget($id);
        }
    }

    /**
     * Get the job IDs to be retried.
     *
     * 获得重新尝试的作业id
     *
     * @return array
     */
    protected function getJobIds()
    {
        //         获取一个命令参数的值
        $ids = $this->argument('id');

        if (count($ids) === 1 && $ids[0] === 'all') {
            //      从数组中提取数组值                 列出所有失败的工作
            $ids = Arr::pluck($this->laravel['queue.failer']->all(), 'id');
        }

        return $ids;
    }

    /**
     * Retry the queue job with the given ID.
     *
     * 使用给定的ID重试队列作业
     *
     * @param  string  $id
     * @return void
     */
    protected function retryJob($id)
    {
        //                                            获取一个失败的工作
        if (is_null($failed = $this->laravel['queue.failer']->find($id))) {
            //将字符串写入错误输出
            return $this->error("No failed job matches the given ID [{$id}].");
        }

        $failed = (object) $failed;
        //                      解析队列连接实例             将原始有效负载推到队列中
        $this->laravel['queue']->connection($failed->connection)->pushRaw(
            //重置载荷的尝试
            $this->resetAttempts($failed->payload), $failed->queue
        );
    }

    /**
     * Reset the payload attempts.
     *
     * 重置载荷的尝试
     *
     * Applicable to Redis jobs which store attempts in their payload.
     *
     * 适用于在有效负载中存储尝试的Redis作业
     *
     * @param  string  $payload
     * @return string
     */
    protected function resetAttempts($payload)
    {
        $payload = json_decode($payload, true);

        if (isset($payload['attempts'])) {
            $payload['attempts'] = 0;
        }

        return json_encode($payload);
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
            ['id', InputArgument::IS_ARRAY, 'The ID of the failed job'],
        ];
    }
}
