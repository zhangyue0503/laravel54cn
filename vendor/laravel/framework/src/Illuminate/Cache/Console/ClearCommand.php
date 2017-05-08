<?php

namespace Illuminate\Cache\Console;

use Illuminate\Console\Command;
use Illuminate\Cache\CacheManager;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ClearCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名称
     *
     * @var string
     */
    protected $name = 'cache:clear';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Flush the application cache';

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
        ////创建一个新的控制台命令实例
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
        //                        触发事件并调用监听器      获取一个命令参数的值              将标签传递给命令
        $this->laravel['events']->fire('cache:clearing', [$this->argument('store'), $this->tags()]);
        //获取命令的缓存实例->从缓存中删除所有项
        $this->cache()->flush();
        //                        触发事件并调用监听器      获取一个命令参数的值              将标签传递给命令
        $this->laravel['events']->fire('cache:cleared', [$this->argument('store'), $this->tags()]);
        //将字符串写入信息输出
        $this->info('Cache cleared successfully.');
    }

    /**
     * Get the cache instance for the command.
     *
     * 获取命令的缓存实例
     *
     * @return \Illuminate\Cache\Repository
     */
    protected function cache()
    {
        //               以名称获取缓存存储实例(获取一个命令参数的值)
        $cache = $this->cache->store($this->argument('store'));
        //            将标签传递给命令                    将标签传递给命令
        return empty($this->tags()) ? $cache : $cache->tags($this->tags());
    }

    /**
     * Get the tags passed to the command.
     *
     * 将标签传递给命令
     *
     * @return array
     */
    protected function tags()
    {
        //                                  获取命令选项的值
        return array_filter(explode(',', $this->option('tags')));
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
            ['store', InputArgument::OPTIONAL, 'The name of the store you would like to clear.'],
        ];
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
            ['tags', null, InputOption::VALUE_OPTIONAL, 'The cache tags you would like to clear.', null],
        ];
    }
}
