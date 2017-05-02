<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use League\Flysystem\MountManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Adapter\Local as LocalAdapter;

class VendorPublishCommand extends Command
{
    /**
     * The filesystem instance.
     *
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The console command signature.
     *
     * 控制台命令签名
     *
     * @var string
     */
    protected $signature = 'vendor:publish {--force : Overwrite any existing files.}
                    {--provider= : The service provider that has assets you want to publish.}
                    {--tag=* : One or many tags that have assets you want to publish.}';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Publish any publishable assets from vendor packages';

    /**
     * Create a new command instance.
     *
     * 创建一个新的命令实例
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
        //      获取命令选项的值
        $tags = $this->option('tag') ?: [null];

        foreach ((array) $tags as $tag) {
            $this->publishTag($tag);
        }
    }

    /**
     * Publishes the assets for a tag.
     *
     * 为标签发布资产
     *
     * @param  string  $tag
     * @return mixed
     */
    protected function publishTag($tag)
    {
        //       获取所有发布的路径
        foreach ($this->pathsToPublish($tag) as $from => $to) {
            $this->publishItem($from, $to);//将给定的项目发布到给定的位置
        }
        //将字符串写入信息输出
        $this->info('Publishing complete.');
    }

    /**
     * Get all of the paths to publish.
     *
     * 获取所有发布的路径
     *
     * @param  string  $tag
     * @return array
     */
    protected function pathsToPublish($tag)
    {
        //                       获取发布的路径
        return ServiceProvider::pathsToPublish(
            //获取命令选项的值
            $this->option('provider'), $tag
        );
    }

    /**
     * Publish the given item from and to the given location.
     *
     * 将给定的项目发布到给定的位置
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishItem($from, $to)
    {
        //确定给定路径是否为文件
        if ($this->files->isFile($from)) {
            return $this->publishFile($from, $to);//将文件发布到给定的路径
        } elseif ($this->files->isDirectory($from)) {//确定给定路径是否为目录
            return $this->publishDirectory($from, $to);//将目录发布到给定的目录
        }

        $this->error("Can't locate path: <{$from}>");//将字符串写入错误输出
    }

    /**
     * Publish the file to the given path.
     *
     * 将文件发布到给定的路径
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishFile($from, $to)
    {
        //确定文件或目录是否存在              获取命令选项的值
        if (! $this->files->exists($to) || $this->option('force')) {
            $this->createParentDirectory(dirname($to));//如果需要，创建目录来存放已发布的文件
            //将文件复制到新位置
            $this->files->copy($from, $to);
            //向控制台写入状态消息
            $this->status($from, $to, 'File');
        }
    }

    /**
     * Publish the directory to the given directory.
     *
     * 将目录发布到给定的目录
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishDirectory($from, $to)
    {
        //通过给定MountManager移动所有的文件
        $this->moveManagedFiles(new MountManager([
            'from' => new Flysystem(new LocalAdapter($from)),
            'to' => new Flysystem(new LocalAdapter($to)),
        ]));
        //向控制台写入状态消息
        $this->status($from, $to, 'Directory');
    }

    /**
     * Move all the files in the given MountManager.
     *
     * 通过给定MountManager移动所有的文件
     *
     * @param  \League\Flysystem\MountManager  $manager
     * @return void
     */
    protected function moveManagedFiles($manager)
    {
        // League\Flysystem\MountManager::listContents
        foreach ($manager->listContents('from://', true) as $file) {
            if ($file['type'] === 'file' && (! $manager->has('to://'.$file['path']) || $this->option('force'))) {
                $manager->put('to://'.$file['path'], $manager->read('from://'.$file['path']));
            }
        }
    }

    /**
     * Create the directory to house the published files if needed.
     *
     * 如果需要，创建目录来存放已发布的文件
     *
     * @param  string  $directory
     * @return void
     */
    protected function createParentDirectory($directory)
    {
        //确定给定路径是否为目录
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);//创建一个目录
        }
    }

    /**
     * Write a status message to the console.
     *
     * 向控制台写入状态消息
     *
     * @param  string  $from
     * @param  string  $to
     * @param  string  $type
     * @return void
     */
    protected function status($from, $to, $type)
    {
        $from = str_replace(base_path(), '', realpath($from));

        $to = str_replace(base_path(), '', realpath($to));
        //将字符串作为标准输出写入
        $this->line('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
    }
}
