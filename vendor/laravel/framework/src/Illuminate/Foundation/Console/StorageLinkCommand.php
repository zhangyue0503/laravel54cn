<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class StorageLinkCommand extends Command
{
    /**
     * The console command signature.
     *
     * 控制台命令签名
     *
     * @var string
     */
    protected $signature = 'storage:link';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a symbolic link from "public/storage" to "storage/app/public"';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        if (file_exists(public_path('storage'))) {//获取公用文件夹的路径
            return $this->error('The "public/storage" directory already exists.');//将字符串写入错误输出
        }
        //从容器中解析给定类型->创建一个指向目标文件或目录的硬链接
        $this->laravel->make('files')->link(
            //获取存储文件夹的路径          获取公用文件夹的路径
            storage_path('app/public'), public_path('storage')
        );
        //将字符串写入信息输出
        $this->info('The [public/storage] directory has been linked.');
    }
}
