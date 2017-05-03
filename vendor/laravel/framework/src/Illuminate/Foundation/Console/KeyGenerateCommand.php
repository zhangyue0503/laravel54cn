<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
//密钥生成命令
class KeyGenerateCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * 控制台命令的名称和签名
     *
     * @var string
     */
    protected $signature = 'key:generate
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Set the application key';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        $key = $this->generateRandomKey();

        if ($this->option('show')) {
            return $this->line('<comment>'.$key.'</comment>');
        }

        // Next, we will replace the application key in the environment file so it is
        // automatically setup for this developer. This key gets generated using a
        // secure random byte generator and is later base64 encoded for storage.
        if (! $this->setKeyInEnvironmentFile($key)) {
            return;
        }

        $this->laravel['config']['app.key'] = $key;

        $this->info("Application key [$key] set successfully.");
    }

    /**
     * Generate a random key for the application.
     *
     * 为应用程序生成一个随机密钥
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:'.base64_encode(random_bytes(
            $this->laravel['config']['app.cipher'] == 'AES-128-CBC' ? 16 : 32
        ));
    }

    /**
     * Set the application key in the environment file.
     *
     * 在环境文件中设置应用程序键
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $currentKey = $this->laravel['config']['app.key'];
        //                                      在继续操作之前确认
        if (strlen($currentKey) !== 0 && (! $this->confirmToProceed())) {
            return false;
        }
        //用给定的键编写一个新的环境文件
        $this->writeNewEnvironmentFileWith($key);

        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * 用给定的键编写一个新的环境文件
     *
     * @param  string  $key
     * @return void
     */
    protected function writeNewEnvironmentFileWith($key)
    {
        //                          获取环境文件的完全限定路径
        file_put_contents($this->laravel->environmentFilePath(), preg_replace(
            $this->keyReplacementPattern(),//得到一个正则表达式模式匹配环境APP_KEY任何随机密钥
            'APP_KEY='.$key,
            file_get_contents($this->laravel->environmentFilePath())//获取环境文件的完全限定路径
        ));
    }

    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     *
     * 得到一个正则表达式模式匹配环境APP_KEY任何随机密钥
     *
     * @return string
     */
    protected function keyReplacementPattern()
    {
        $escaped = preg_quote('='.$this->laravel['config']['app.key'], '/');

        return "/^APP_KEY{$escaped}/m";
    }
}
