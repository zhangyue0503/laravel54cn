<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Contracts\Console\Kernel;

trait InteractsWithConsole
{
    /**
     * Call artisan command and return code.
     *
     * 调用手工命令和返回代码
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return int
     */
    public function artisan($command, $parameters = [])
    {
        //                          按名称运行一个Artisan控制台命令
        return $this->app[Kernel::class]->call($command, $parameters);
    }
}
