<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;

class SeederMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:seeder';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new seeder class';

    /**
     * The type of class being generated.
     *
     * 生成的类类型
     *
     * @var string
     */
    protected $type = 'Seeder';

    /**
     * The Composer instance.
     *
     * Composer实例
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new command instance.
     *
     * 创建一个新的命令实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        //创建一个新的控制器创建者命令实例
        parent::__construct($files);

        $this->composer = $composer;
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
        //执行控制台命令
        parent::fire();
        //再生Composer的自动加载文件
        $this->composer->dumpAutoloads();
    }

    /**
     * Get the stub file for the generator.
     *
     * 获取生成器的桩文件
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/seeder.stub';
    }

    /**
     * Get the destination class path.
     *
     * 获取目标类路径
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        //                      获取数据库目录的路径
        return $this->laravel->databasePath().'/seeds/'.$name.'.php';
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * 根据根名称空间解析类名和格式
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        return $name;
    }
}
