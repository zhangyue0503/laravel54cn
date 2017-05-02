<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class AppNameCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'app:name';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Set the application namespace';

    /**
     * The Composer class instance.
     *
     * Composer类实例
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * The filesystem instance.
     *
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Current root application namespace.
     *
     * 当前根应用程序命名空间
     *
     * @var string
     */
    protected $currentRoot;

    /**
     * Create a new key generator command.
     *
     * 创建一个新的密钥生成器命令
     *
     * @param  \Illuminate\Support\Composer  $composer
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Composer $composer, Filesystem $files)
    {
        parent::__construct();//创建一个新的控制台命令实例

        $this->files = $files;
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
        //获取应用程序的命名空间
        $this->currentRoot = trim($this->laravel->getNamespace(), '\\');

        $this->setAppDirectoryNamespace();//在app目录中的文件中设置名称空间
        $this->setBootstrapNamespaces();//设置引导名称空间
        $this->setConfigNamespaces();//在适当的配置文件中设置名称空间
        $this->setComposerNamespace();//设置PSR-4Composer文件中的名称空间
        $this->setDatabaseFactoryNamespaces();//在数据库工厂文件中设置名称空间

        $this->info('Application namespace set!');//将字符串写入信息输出

        $this->composer->dumpAutoloads();//再生Composer的自动加载文件

        $this->call('clear-compiled');//调用另一个控制台命令
    }

    /**
     * Set the namespace on the files in the app directory.
     *
     * 在app目录中的文件中设置名称空间
     *
     * @return void
     */
    protected function setAppDirectoryNamespace()
    {
        $files = Finder::create()//创建一个新的查找器
                            ->in($this->laravel['path'])//搜索符合定义规则的文件和目录
                            ->contains($this->currentRoot)//添加文件内容必须匹配的测试
                            ->name('*.php');//添加文件必须匹配的规则

        foreach ($files as $file) {
            //在给定的路径中替换应用程序名称空间
            $this->replaceNamespace($file->getRealPath());
        }
    }

    /**
     * Replace the App namespace at the given path.
     *
     * 在给定的路径中替换应用程序名称空间
     *
     * @param  string  $path
     * @return void
     */
    protected function replaceNamespace($path)
    {
        $search = [
            'namespace '.$this->currentRoot.';',
            $this->currentRoot.'\\',
        ];

        $replace = [
            'namespace '.$this->argument('name').';',//获取一个命令参数的值
            $this->argument('name').'\\',//获取一个命令参数的值
        ];
        //在给定文件中替换给定的字符串
        $this->replaceIn($path, $search, $replace);
    }

    /**
     * Set the bootstrap namespaces.
     *
     * 设置引导名称空间
     *
     * @return void
     */
    protected function setBootstrapNamespaces()
    {
        $search = [
            $this->currentRoot.'\\Http',
            $this->currentRoot.'\\Console',
            $this->currentRoot.'\\Exceptions',
        ];

        $replace = [
            $this->argument('name').'\\Http',//获取一个命令参数的值
            $this->argument('name').'\\Console',//获取一个命令参数的值
            $this->argument('name').'\\Exceptions',//获取一个命令参数的值
        ];
        //在给定文件中替换给定的字符串(获得 bootstrap/ app.php文件的路径,)
        $this->replaceIn($this->getBootstrapPath(), $search, $replace);
    }

    /**
     * Set the namespace in the appropriate configuration files.
     *
     * 在适当的配置文件中设置名称空间
     *
     * @return void
     */
    protected function setConfigNamespaces()
    {
        $this->setAppConfigNamespaces();//设置应用程序提供者命名空间
        $this->setAuthConfigNamespace();//设置身份验证用户名称空间
        $this->setServicesConfigNamespace();//设置服务用户名称空间
    }

    /**
     * Set the application provider namespaces.
     *
     * 设置应用程序提供者命名空间
     *
     * @return void
     */
    protected function setAppConfigNamespaces()
    {
        $search = [
            $this->currentRoot.'\\Providers',
            $this->currentRoot.'\\Http\\Controllers\\',
        ];

        $replace = [
            $this->argument('name').'\\Providers',//获取一个命令参数的值
            $this->argument('name').'\\Http\\Controllers\\',//获取一个命令参数的值
        ];
        //在给定文件中替换给定的字符串(获取给定配置文件的路径,)
        $this->replaceIn($this->getConfigPath('app'), $search, $replace);
    }

    /**
     * Set the authentication User namespace.
     *
     * 设置身份验证用户名称空间
     *
     * @return void
     */
    protected function setAuthConfigNamespace()
    {
        //在给定文件中替换给定的字符串
        $this->replaceIn(
            $this->getConfigPath('auth'),//获取给定配置文件的路径
            $this->currentRoot.'\\User',
            $this->argument('name').'\\User'//获取一个命令参数的值
        );
    }

    /**
     * Set the services User namespace.
     *
     * 设置服务用户名称空间
     *
     * @return void
     */
    protected function setServicesConfigNamespace()
    {
        //在给定文件中替换给定的字符串
        $this->replaceIn(
            $this->getConfigPath('services'),//获取给定配置文件的路径
            $this->currentRoot.'\\User',
            $this->argument('name').'\\User'//获取一个命令参数的值
        );
    }

    /**
     * Set the PSR-4 namespace in the Composer file.
     *
     * 设置PSR-4Composer文件中的名称空间
     *
     * @return void
     */
    protected function setComposerNamespace()
    {
        //在给定文件中替换给定的字符串
        $this->replaceIn(
            $this->getComposerPath(),//获取Composer.json文件的路径
            str_replace('\\', '\\\\', $this->currentRoot).'\\\\',
            str_replace('\\', '\\\\', $this->argument('name')).'\\\\'//获取一个命令参数的值
        );
    }

    /**
     * Set the namespace in database factory files.
     *
     * 在数据库工厂文件中设置名称空间
     *
     * @return void
     */
    protected function setDatabaseFactoryNamespaces()
    {
        //在给定文件中替换给定的字符串
        $this->replaceIn(
            $this->laravel->databasePath().'/factories/ModelFactory.php',//获取数据库目录的路径
            $this->currentRoot, $this->argument('name')//获取一个命令参数的值
        );
    }

    /**
     * Replace the given string in the given file.
     *
     * 在给定文件中替换给定的字符串
     *
     * @param  string  $path
     * @param  string|array  $search
     * @param  string|array  $replace
     * @return void
     */
    protected function replaceIn($path, $search, $replace)
    {
        //确定文件或目录是否存在
        if ($this->files->exists($path)) {
            //写入文件的内容
            $this->files->put($path, str_replace($search, $replace, $this->files->get($path)));
        }
    }

    /**
     * Get the path to the bootstrap/app.php file.
     *
     * 获得 bootstrap/ app.php文件的路径
     *
     * @return string
     */
    protected function getBootstrapPath()
    {
        //获取引导目录的路径
        return $this->laravel->bootstrapPath().'/app.php';
    }

    /**
     * Get the path to the Composer.json file.
     *
     * 获取Composer.json文件的路径
     *
     * @return string
     */
    protected function getComposerPath()
    {
        //得到Laravel安装的基本路径
        return $this->laravel->basePath().'/composer.json';
    }

    /**
     * Get the path to the given configuration file.
     *
     * 获取给定配置文件的路径
     *
     * @param  string  $name
     * @return string
     */
    protected function getConfigPath($name)
    {
        return $this->laravel['path.config'].'/'.$name.'.php';
    }

    /**
     * Get the console command arguments.
     *
     * 获取控制台命令参数
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The desired namespace.'],
        ];
    }
}
