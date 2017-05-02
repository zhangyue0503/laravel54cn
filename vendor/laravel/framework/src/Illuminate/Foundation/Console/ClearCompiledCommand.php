<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class ClearCompiledCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'clear-compiled';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Remove the compiled class file';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        $servicesPath = $this->laravel->getCachedServicesPath();//获取缓存的services.php文件路径

        if (file_exists($servicesPath)) {
            @unlink($servicesPath);
        }
        //将字符串写入信息输出
        $this->info('The compiled services file has been removed.');
    }
}
