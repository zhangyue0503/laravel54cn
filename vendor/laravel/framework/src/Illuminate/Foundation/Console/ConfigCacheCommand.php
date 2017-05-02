<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;

class ConfigCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'config:cache';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a cache file for faster configuration loading';

    /**
     * The filesystem instance.
     *
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new config cache command instance.
     *
     * 创建一个新的配置缓存命令实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();//创建一个新的控制台命令实例

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        //调用另一个控制台命令
        $this->call('config:clear');
        //启动应用程序配置的新副本
        $config = $this->getFreshConfiguration();
        //写入文件的内容
        $this->files->put(
            //获取配置的缓存文件的路径
            $this->laravel->getCachedConfigPath(), '<?php return '.var_export($config, true).';'.PHP_EOL
        );
        //将字符串写入信息输出
        $this->info('Configuration cached successfully!');
    }

    /**
     * Boot a fresh copy of the application configuration.
     *
     * 启动应用程序配置的新副本
     *
     * @return array
     */
    protected function getFreshConfiguration()
    {
        //                    获取引导目录的路径
        $app = require $this->laravel->bootstrapPath().'/app.php';
        //从容器中解析给定类型                          引导HTTP请求的应用程序
        $app->make(ConsoleKernelContract::class)->bootstrap();

        return $app['config']->all();//获取应用程序的所有配置项
    }
}
