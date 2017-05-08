<?php

namespace Illuminate\Cache\Console;

use Illuminate\Console\Command;
use Illuminate\Cache\CacheManager;

class ForgetCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名称
     *
     * @var string
     */
    protected $signature = 'cache:forget {key : The key to remove} {store? : The store to remove the key from}';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Remove an item from the cache';

    /**
     * The cache manager instance.
     *
     * 缓存管理器实例
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * Create a new cache clear command instance.
     *
     * 创建一个新的缓存清除命令实例
     *
     * @param  \Illuminate\Cache\CacheManager  $cache
     * @return void
     */
    public function __construct(CacheManager $cache)
    {
        //创建一个新的控制台命令实例
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        //以名称获取缓存存储实例(获取一个命令参数的值)->从缓存中删除一个项目(获取一个命令参数的值)
        $this->cache->store($this->argument('store'))->forget(
            $this->argument('key')
        );
        //将字符串写入信息输出          获取一个命令参数的值
        $this->info('The ['.$this->argument('key').'] key has been removed from the cache.');
    }
}
