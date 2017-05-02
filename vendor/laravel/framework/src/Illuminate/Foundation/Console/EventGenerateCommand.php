<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class EventGenerateCommand extends Command
{
    /**
     * The console command name.
     *
     * 控制台命令名
     *
     * @var string
     */
    protected $name = 'event:generate';

    /**
     * The console command description.
     *
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Generate the missing events and listeners based on registration';

    /**
     * Execute the console command.
     *
     * 执行控制台命令
     *
     * @return void
     */
    public function fire()
    {
        //                     如果服务提供者存在，返回注册服务提供者的实例
        $provider = $this->laravel->getProvider(EventServiceProvider::class);
        //         获取事件和处理程序
        foreach ($provider->listens() as $event => $listeners) {
            $this->makeEventAndListeners($event, $listeners);//为给定事件创建事件和侦听器
        }
        //将字符串写入信息输出
        $this->info('Events and listeners generated successfully!');
    }

    /**
     * Make the event and listeners for the given event.
     *
     * 为给定事件创建事件和侦听器
     *
     * @param  string  $event
     * @param  array  $listeners
     * @return void
     */
    protected function makeEventAndListeners($event, $listeners)
    {
        //确定一个给定的字符串包含另一个字符串
        if (! Str::contains($event, '\\')) {
            return;
        }
        //调用另一个控制台命令
        $this->callSilent('make:event', ['name' => $event]);
        //为给定的事件做侦听器
        $this->makeListeners($event, $listeners);
    }

    /**
     * Make the listeners for the given event.
     *
     * 为给定的事件做侦听器
     *
     * @param  string  $event
     * @param  array  $listeners
     * @return void
     */
    protected function makeListeners($event, $listeners)
    {
        foreach ($listeners as $listener) {
            $listener = preg_replace('/@.+$/', '', $listener);
            //调用另一个控制台命令
            $this->callSilent('make:listener', ['name' => $listener, '--event' => $event]);
        }
    }
}
