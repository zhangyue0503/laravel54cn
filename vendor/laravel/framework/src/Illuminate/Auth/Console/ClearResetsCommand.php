<?php

namespace Illuminate\Auth\Console;

use Illuminate\Console\Command;
//清除重置命令
class ClearResetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * 控制台命令的名称和签名
     *
     * @var string
     */
    protected $signature = 'auth:clear-resets {name? : The name of the password broker}';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Flush expired password reset tokens';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        //                           尝试从本地缓存中获取代理(获取一个命令参数的值)->获取密码重置令牌存储库实现->删除过期的令牌
        $this->laravel['auth.password']->broker($this->argument('name'))->getRepository()->deleteExpired();
        //将字符串写入信息输出
        $this->info('Expired reset tokens cleared!');
    }
}
