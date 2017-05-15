<?php

namespace Illuminate\Database\Migrations;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;

class MigrationCreator
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
     * The registered post create hooks.
     *
     * 注册后的帖子创建钩子
     *
     * @var array
     */
    protected $postCreate = [];

    /**
     * Create a new migration creator instance.
     *
     * 创建一个新的迁移创建者实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Create a new migration at the given path.
     *
     * 在给定的路径中创建新的迁移
     *
     * @param  string  $name
     * @param  string  $path
     * @param  string  $table
     * @param  bool    $create
     * @return string
     * @throws \Exception
     */
    public function create($name, $path, $table = null, $create = false)
    {
        //确保使用给定名称的迁移还不存在
        $this->ensureMigrationDoesntAlreadyExist($name);

        // First we will get the stub file for the migration, which serves as a type
        // of template for the migration. Once we have those we will populate the
        // various place-holders, save the file, and run the post create event.
        //
        // 首先，我们将获取迁移的存根文件，它作为迁移的一种模板
        // 一旦我们有了这些，我们将填充各种各样的占位符，保存文件，并运行post创建事件
        //
        //        获取迁移桩文件
        $stub = $this->getStub($table, $create);
        //写入文件的内容
        $this->files->put(
            //获得迁移的完整路径
            $path = $this->getPath($name, $path),
            //在迁移存根中填充占位符
            $this->populateStub($name, $stub, $table)
        );

        // Next, we will fire any hooks that are supposed to fire after a migration is
        // created. Once that is done we'll be ready to return the full path to the
        // migration file so it can be used however it's needed by the developer.
        //
        // 接下来，在创建迁移之后，我们将触发任何应该触发的钩子
        // 一旦完成，我们将准备返回迁移文件的完整路径，这样它就可以被开发人员使用了
        //
        //         注册后的帖子创建钩子
        $this->firePostCreateHooks();

        return $path;
    }

    /**
     * Ensure that a migration with the given name doesn't already exist.
     * 确保使用给定名称的迁移还不存在
     *
     *
     * @param  string  $name
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function ensureMigrationDoesntAlreadyExist($name)
    {
        //                              获取一个迁移名称的类名
        if (class_exists($className = $this->getClassName($name))) {
            throw new InvalidArgumentException("A {$className} migration already exists.");
        }
    }

    /**
     * Get the migration stub file.
     *
     * 获取迁移桩文件
     *
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function getStub($table, $create)
    {
        if (is_null($table)) {
            //            获取文件的内容     获得通往桩的路径
            return $this->files->get($this->stubPath().'/blank.stub');
        }

        // We also have stubs for creating new tables and modifying existing tables
        // to save the developer some typing when they are creating a new tables
        // or modifying existing tables. We'll grab the appropriate stub here.
        //
        // 我们还拥有创建新表和修改现有表的存根，以便在创建新表或修改现有表时为开发人员节省一些输入
        // 我们将在这里获取适当的存根
        //
        else {
            $stub = $create ? 'create.stub' : 'update.stub';
            //           获取文件的内容            获得通往桩的路径
            return $this->files->get($this->stubPath()."/{$stub}");
        }
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * 在迁移存根中填充占位符
     *
     * @param  string  $name
     * @param  string  $stub
     * @param  string  $table
     * @return string
     */
    protected function populateStub($name, $stub, $table)
    {
        //                                 获取一个迁移名称的类名
        $stub = str_replace('DummyClass', $this->getClassName($name), $stub);

        // Here we will replace the table place-holders with the table specified by
        // the developer, which is useful for quickly creating a tables creation
        // or update migration from the console instead of typing it manually.
        //
        // 在这里，我们将用开发人员指定的表替换表占位符，这对于快速创建表创建或更新从控制台进行更新而不是手动输入是很有用的
        //
        if (! is_null($table)) {
            $stub = str_replace('DummyTable', $table, $stub);
        }

        return $stub;
    }

    /**
     * Get the class name of a migration name.
     *
     * 获取一个迁移名称的类名
     *
     * @param  string  $name
     * @return string
     */
    protected function getClassName($name)
    {
        //         将值转换为大驼峰
        return Str::studly($name);
    }

    /**
     * Get the full path to the migration.
     *
     * 获得迁移的完整路径
     *
     * @param  string  $name
     * @param  string  $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        //                 获取迁移的日期前缀
        return $path.'/'.$this->getDatePrefix().'_'.$name.'.php';
    }

    /**
     * Fire the registered post create hooks.
     *
     * 注册后的帖子创建钩子
     *
     * @return void
     */
    protected function firePostCreateHooks()
    {
        foreach ($this->postCreate as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Register a post migration create hook.
     *
     * 注册一个贴子迁移创建钩子
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function afterCreate(Closure $callback)
    {
        $this->postCreate[] = $callback;
    }

    /**
     * Get the date prefix for the migration.
     *
     * 获取迁移的日期前缀
     *
     * @return string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }

    /**
     * Get the path to the stubs.
     *
     * 获得通往桩的路径
     *
     * @return string
     */
    public function stubPath()
    {
        return __DIR__.'/stubs';
    }

    /**
     * Get the filesystem instance.
     *
     * 获取文件系统实例
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }
}
