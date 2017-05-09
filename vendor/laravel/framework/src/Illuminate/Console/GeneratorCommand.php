<?php

namespace Illuminate\Console;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

abstract class GeneratorCommand extends Command
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
     * The type of class being generated.
     *
     * 生成的类类型
     *
     * @var string
     */
    protected $type;

    /**
     * Create a new controller creator command instance.
     *
     * 创建一个新的控制器创建者命令实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        //创建一个新的控制台命令实例
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Get the stub file for the generator.
     *
     * 获取生成器的桩文件
     *
     * @return string
     */
    abstract protected function getStub();

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return bool|null
     */
    public function fire()
    {
        //根据根名称空间解析类名和格式(从输入中获取所需的类名)
        $name = $this->qualifyClass($this->getNameInput());
        //获取目标类路径
        $path = $this->getPath($name);

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        //
        // 首先，我们将检查这个类是否已经存在
        // 如果是这样，我们就不希望创建类并覆盖用户的代码
        // 因此，我们将对代码进行保护，这样代码就不会受到影响
        // 否则，我们将继续生成这个类的文件
        //
        //确定这个类是否已经存在
        if ($this->alreadyExists($this->getNameInput())) {
            $this->error($this->type.' already exists!');

            return false;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        //
        // 接下来，我们将生成到这个类文件应该被写入的位置的路径
        // 然后，我们将构建类并在存根文件上进行适当的替换，以便获得正确格式化的名称空间和类名
        //
        //如果有必要，为类构建目录
        $this->makeDirectory($path);
        //写入文件的内容(,用给定的名称构建类)
        $this->files->put($path, $this->buildClass($name));
        //将字符串写入信息输出
        $this->info($this->type.' created successfully.');
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
        //                    获取类的根名称空间
        $rootNamespace = $this->rootNamespace();
        //确定给定的子字符串是否属于给定的字符串
        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        $name = str_replace('/', '\\', $name);
        //根据根名称空间解析类名和格式
        return $this->qualifyClass(
            //获取类的默认名称空间
            $this->getDefaultNamespace(trim($rootNamespace, '\\')).'\\'.$name
        );
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
        return $rootNamespace;
    }

    /**
     * Determine if the class already exists.
     *
     * 确定这个类是否已经存在
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        //确定文件或目录是否存在(获取目标类路径(根据根名称空间解析类名和格式))
        return $this->files->exists($this->getPath($this->qualifyClass($rawName)));
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
        //                         获取类的根名称空间
        $name = str_replace_first($this->rootNamespace(), '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Build the directory for the class if necessary.
     *
     * 如果有必要，为类构建目录
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        //确定给定路径是否为目录
        if (! $this->files->isDirectory(dirname($path))) {
            //创建一个目录
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

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
        //获取文件的内容(获取生成器的桩文件)
        $stub = $this->files->get($this->getStub());
        //替换给定桩的名称空间->替换给定桩的类名
        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Replace the namespace for the given stub.
     *
     * 替换给定桩的名称空间
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace(
            ['DummyNamespace', 'DummyRootNamespace'],
            //获得给定类的完整名称空间，不使用类名     获取类的根名称空间
            [$this->getNamespace($name), $this->rootNamespace()],
            $stub
        );

        return $this;
    }

    /**
     * Get the full namespace for a given class, without the class name.
     *
     * 获得给定类的完整名称空间，不使用类名
     *
     * @param  string  $name
     * @return string
     */
    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Replace the class name for the given stub.
     *
     * 替换给定桩的类名
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        //                  获得给定类的完整名称空间，不使用类名
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        return str_replace('DummyClass', $class, $stub);
    }

    /**
     * Get the desired class name from the input.
     *
     * 从输入中获取所需的类名
     *
     * @return string
     */
    protected function getNameInput()
    {
        //                获取一个命令参数的值
        return trim($this->argument('name'));
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
        //获取应用程序的命名空间
        return $this->laravel->getNamespace();
    }

    /**
     * Get the console command arguments.
     *
     * 获得控制台命令参数
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the class'],
        ];
    }
}
