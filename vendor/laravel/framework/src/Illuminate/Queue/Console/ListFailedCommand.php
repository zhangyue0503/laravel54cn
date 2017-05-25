<?php

namespace Illuminate\Queue\Console;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;
//失败的列表命令
class ListFailedCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'queue:failed';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'List all of the failed queue jobs';

    /**
     * The table headers for the command.
     *
     * 命令的表头
     *
     * @var array
     */
    protected $headers = ['ID', 'Connection', 'Queue', 'Class', 'Failed At'];

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        //                    将失败的作业编译成可显示的格式
        if (count($jobs = $this->getFailedJobs()) == 0) {
            //          将字符串写入信息输出
            return $this->info('No failed jobs!');
        }
        //在控制台中显示失败的作业
        $this->displayFailedJobs($jobs);
    }

    /**
     * Compile the failed jobs into a displayable format.
     *
     * 将失败的作业编译成可显示的格式
     *
     * @return array
     */
    protected function getFailedJobs()
    {
        //                              列出所有失败的工作
        $failed = $this->laravel['queue.failer']->all();
        //                在每个项目上运行map
        return collect($failed)->map(function ($failed) {
            //                解析失败的作业行
            return $this->parseFailedJob((array) $failed);
        })->filter()->all();//在每个项目上运行过滤器->获取集合中的所有项目
    }

    /**
     * Parse the failed job row.
     *
     * 解析失败的作业行
     *
     * @param  array  $failed
     * @return array
     */
    protected function parseFailedJob(array $failed)
    {
        //                   获取指定数组，除了指定的数组项
        $row = array_values(Arr::except($failed, ['payload', 'exception']));
        //                         从有效负载中提取出失败的作业名
        array_splice($row, 3, 0, $this->extractJobName($failed['payload']));

        return $row;
    }

    /**
     * Extract the failed job name from payload.
     *
     * 从有效负载中提取出失败的作业名
     *
     * @param  string  $payload
     * @return string|null
     */
    private function extractJobName($payload)
    {
        $payload = json_decode($payload, true);

        if ($payload && (! isset($payload['data']['command']))) {
            //         使用“点”符号从数组中获取一个项
            return Arr::get($payload, 'job');
        } elseif ($payload && isset($payload['data']['command'])) {
            return $this->matchJobName($payload);//匹配有效负载的工作名称
        }
    }

    /**
     * Match the job name from the payload.
     *
     * 匹配有效负载的工作名称
     *
     * @param  array  $payload
     * @return string
     */
    protected function matchJobName($payload)
    {
        preg_match('/"([^"]+)"/', $payload['data']['command'], $matches);

        if (isset($matches[1])) {
            return $matches[1];
        } else {
            //使用“点”符号从数组中获取一个项
            return Arr::get($payload, 'job');
        }
    }

    /**
     * Display the failed jobs in the console.
     *
     * 在控制台中显示失败的作业
     *
     * @param  array  $jobs
     * @return void
     */
    protected function displayFailedJobs(array $jobs)
    {
        //格式输入到文本表
        $this->table($this->headers, $jobs);
    }
}
