<?php

namespace Illuminate\Foundation\Console;

use RuntimeException;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ViewClearCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'view:clear';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Clear all compiled view files';

    /**
     * The filesystem instance.
     *
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new config clear command instance.
     *
     * 创建一个新的配置清除命令实例
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
        $path = $this->laravel['config']['view.compiled'];

        if (! $path) {
            throw new RuntimeException('View path not found.');
        }
        //                   找到匹配给定模式的路径名
        foreach ($this->files->glob("{$path}/*") as $view) {
            $this->files->delete($view);//在给定的路径中删除该文件
        }
        //将字符串写入信息输出
        $this->info('Compiled views cleared!');
    }
}
