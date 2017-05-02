<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Console\Kernel as KernelContract;

class QueuedCommand implements ShouldQueue
{
    /**
     * The data to pass to the Artisan command.
     *
     * 传递给Artisan命令的数据
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * 创建一个新的作业实例
     *
     * @param  array  $data
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Handle the job.
     *
     * 处理工作
     *
     * @param  \Illuminate\Contracts\Console\Kernel  $kernel
     * @return void
     */
    public function handle(KernelContract $kernel)
    {
        call_user_func_array([$kernel, 'call'], $this->data);
    }
}
