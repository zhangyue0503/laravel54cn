<?php

namespace Illuminate\Support;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;
use Symfony\Component\Process\PhpExecutableFinder;

class Composer
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
     * The working path to regenerate from.
     *
     * 再生的工作路径
     *
     * @var string
     */
    protected $workingPath;

    /**
     * Create a new Composer manager instance.
     *
     * 创建新的Composer管理器实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string|null  $workingPath
     * @return void
     */
    public function __construct(Filesystem $files, $workingPath = null)
    {
        $this->files = $files;
        $this->workingPath = $workingPath;
    }

    /**
     * Regenerate the Composer autoloader files.
     *
     * 再生Composer的自动加载文件
     *
     * @param  string  $extra
     * @return void
     */
    public function dumpAutoloads($extra = '')
    {
        $process = $this->getProcess();//得到一个新的symfony进程实例
        //   设置要执行的命令行            获取环境中的composer命令
        $process->setCommandLine(trim($this->findComposer().' dump-autoload '.$extra));
        //运行进程
        $process->run();
    }

    /**
     * Regenerate the optimized Composer autoloader files.
     *
     * 再生优化的Composer的自动加载文件
     *
     * @return void
     */
    public function dumpOptimized()
    {
        //再生Composer的自动加载文件
        $this->dumpAutoloads('--optimize');
    }

    /**
     * Get the composer command for the environment.
     *
     * 获取环境中的composer命令
     *
     * @return string
     */
    protected function findComposer()
    {
        // 确定文件或目录是否存在
        if ($this->files->exists($this->workingPath.'/composer.phar')) {
            //       转义字符串用作shell参数       专为PHP可执行文件设计的可执行搜索器  发现PHP可执行文件
            return ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false)).' composer.phar';
        }

        return 'composer';
    }

    /**
     * Get a new Symfony process instance.
     *
     * 得到一个新的symfony进程实例
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function getProcess()
    {
        //                                             设置进程的运行时间
        return (new Process('', $this->workingPath))->setTimeout(null);
    }

    /**
     * Set the working path used by the class.
     *
     * 设置类所使用的工作路径
     *
     * @param  string  $path
     * @return $this
     */
    public function setWorkingPath($path)
    {
        $this->workingPath = realpath($path);

        return $this;
    }
}
