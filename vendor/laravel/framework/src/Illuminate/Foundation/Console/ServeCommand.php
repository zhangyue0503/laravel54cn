<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\ProcessUtils;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\PhpExecutableFinder;

class ServeCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'serve';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Serve the application on the PHP development server';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     *
     * @throws \Exception
     */
    public function fire()
    {
        chdir($this->laravel->publicPath());//获取公共的/web目录路径
        //将字符串作为标准输出写入
        $this->line("<info>Laravel development server started:</info> <http://{$this->host()}:{$this->port()}>");
        //          获得完整的服务器命令
        passthru($this->serverCommand());
    }

    /**
     * Get the full server command.
     *
     * 获得完整的服务器命令
     *
     * @return string
     */
    protected function serverCommand()
    {
        return sprintf('%s -S %s:%s %s/server.php',
            //        转义字符串用作shell参数                      发现PHP可执行文件
            ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false)),
            $this->host(),//获取该命令的主机
            $this->port(),//获取该命令的端口
            //        转义字符串用作shell参数(得到Laravel安装的基本路径)
            ProcessUtils::escapeArgument($this->laravel->basePath())
        );
    }

    /**
     * Get the host for the command.
     *
     * 获取该命令的主机
     *
     * @return string
     */
    protected function host()
    {
        //返回给定选项名的选项值
        return $this->input->getOption('host');
    }

    /**
     * Get the port for the command.
     *
     * 获取该命令的端口
     *
     * @return string
     */
    protected function port()
    {
        //返回给定选项名的选项值
        return $this->input->getOption('port');
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
            ['host', null, InputOption::VALUE_OPTIONAL, 'The host address to serve the application on.', '127.0.0.1'],

            ['port', null, InputOption::VALUE_OPTIONAL, 'The port to serve the application on.', 8000],
        ];
    }
}
