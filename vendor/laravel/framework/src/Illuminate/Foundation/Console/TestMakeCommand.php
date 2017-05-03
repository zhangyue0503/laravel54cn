<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
//创建测试命令
class TestMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $signature = 'make:test {name : The name of the class} {--unit : Create a unit test}';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new test class';

    /**
     * The type of class being generated.
     *
     * 生成的类类型
     *
     * @var string
     */
    protected $type = 'Test';

    /**
     * Get the stub file for the generator.
     *
     * 获取生成器的桩文件
     *
     * @return string
     */
    protected function getStub()
    {
        //获取命令选项的值
        if ($this->option('unit')) {
            return __DIR__.'/stubs/unit-test.stub';
        } else {
            return __DIR__.'/stubs/test.stub';
        }
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
        //    替换字符串中第一次出现的给定值        获取类的根名称空间
        $name = str_replace_first($this->rootNamespace(), '', $name);
        //            得到Laravel安装的基本路径
        return $this->laravel->basePath().'/tests'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Get the default namespace for the class.
     *
     * 获取类的默认名称空间
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        //    获取命令选项的值
        if ($this->option('unit')) {
            return $rootNamespace.'\Unit';
        } else {
            return $rootNamespace.'\Feature';
        }
    }

    /**
     * Get the root namespace for the class.
     *
     * 获取类的根名称空间
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return 'Tests';
    }
}
