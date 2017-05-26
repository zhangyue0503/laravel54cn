<?php

namespace Illuminate\Routing\Console;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
//控制器创建命令
class ControllerMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'make:controller';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Create a new controller class';

    /**
     * The type of class being generated.
     *
     * 生成的类类型
     *
     * @var string
     */
    protected $type = 'Controller';

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
        if ($this->option('model')) {
            return __DIR__.'/stubs/controller.model.stub';
        } elseif ($this->option('resource')) {
            return __DIR__.'/stubs/controller.stub';
        }

        return __DIR__.'/stubs/controller.plain.stub';
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
        return $rootNamespace.'\Http\Controllers';
    }

    /**
     * Build the class with the given name.
     *
     * 用给定的名称构建类
     *
     * Remove the base controller import if we are already in base namespace.
     *
     * 如果我们已经在基本名称空间中，就删除基本控制器导入
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        //                        获得给定类的完整名称空间，不使用类名
        $controllerNamespace = $this->getNamespace($name);

        $replace = [];
        //获取命令选项的值
        if ($this->option('model')) {
            //                获得完全限定的模型类名
            $modelClass = $this->parseModel($this->option('model'));

            if (! class_exists($modelClass)) {
                //与用户确认一个问题
                if ($this->confirm("A {$modelClass} model does not exist. Do you want to generate it?", true)) {
                    //调用另一个控制台命令
                    $this->call('make:model', ['name' => $modelClass]);
                }
            }

            $replace = [
                'DummyFullModelClass' => $modelClass,
                'DummyModelClass' => class_basename($modelClass),
                'DummyModelVariable' => lcfirst(class_basename($modelClass)),
            ];
        }

        $replace["use {$controllerNamespace}\Controller;\n"] = '';

        return str_replace(
            //                                               用给定的名称构建类
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Get the fully-qualified model class name.
     *
     * 获得完全限定的模型类名
     *
     * @param  string  $model
     * @return string
     */
    protected function parseModel($model)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        $model = trim(str_replace('/', '\\', $model), '\\');
        //确定给定的子字符串是否属于给定的字符串                       获取应用程序的命名空间
        if (! Str::startsWith($model, $rootNamespace = $this->laravel->getNamespace())) {
            $model = $rootNamespace.$model;
        }

        return $model;
    }

    /**
     * Get the console command options.
     *
     * 获得控制台命令选项
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            //            表示一个命令行选项
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given model.'],

            ['resource', 'r', InputOption::VALUE_NONE, 'Generate a resource controller class.'],
        ];
    }
}
