<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
//创建策略命令
class PolicyMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:policy';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new policy class';

    /**
     * The type of class being generated.
     *
     * 生成的类类型
     *
     * @var string
     */
    protected $type = 'Policy';

    /**
     * Build the class with the given name.
     *
     * 用给定的名称构建类
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);//用给定的名称构建类

        $model = $this->option('model');//获取命令选项的值
        //                替换给定桩的模型
        return $model ? $this->replaceModel($stub, $model) : $stub;
    }

    /**
     * Replace the model for the given stub.
     *
     * 替换给定桩的模型
     *
     * @param  string  $stub
     * @param  string  $model
     * @return string
     */
    protected function replaceModel($stub, $model)
    {
        $model = str_replace('/', '\\', $model);
        //    确定给定的子字符串是否属于给定的字符串
        if (Str::startsWith($model, '\\')) {
            $stub = str_replace('NamespacedDummyModel', trim($model, '\\'), $stub);
        } else {
            //                                                获取应用程序的命名空间
            $stub = str_replace('NamespacedDummyModel', $this->laravel->getNamespace().$model, $stub);
        }

        $model = class_basename(trim($model, '\\'));

        $stub = str_replace('DummyModel', $model, $stub);
        //                                   转换值为驼峰命名
        $stub = str_replace('dummyModel', Str::camel($model), $stub);
        //                                    获取一个英语单词的复数形式(转换值为驼峰命名)
        return str_replace('dummyPluralModel', Str::plural(Str::camel($model)), $stub);
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
        return $this->option('model')//获取命令选项的值
                    ? __DIR__.'/stubs/policy.stub'
                    : __DIR__.'/stubs/policy.plain.stub';
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
        return $rootNamespace.'\Policies';
    }

    /**
     * Get the console command arguments.
     *
     * 获得控制台命令参数
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the policy applies to.'],
        ];
    }
}
