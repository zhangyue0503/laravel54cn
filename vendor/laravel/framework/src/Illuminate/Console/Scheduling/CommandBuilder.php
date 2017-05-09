<?php

namespace Illuminate\Console\Scheduling;

use Illuminate\Console\Application;
use Symfony\Component\Process\ProcessUtils;

class CommandBuilder
{
    /**
     * Build the command for the given event.
     *
     * 为给定的事件构建命令
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    public function buildCommand(Event $event)
    {
        if ($event->runInBackground) {
            return $this->buildBackgroundCommand($event);//构建用于在后台运行事件的命令
        } else {
            return $this->buildForegroundCommand($event);//构建用于在前台运行事件的命令
        }
    }

    /**
     * Build the command for running the event in the foreground.
     *
     * 构建用于在前台运行事件的命令
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    protected function buildForegroundCommand(Event $event)
    {
        //转义字符串用作shell参数
        $output = ProcessUtils::escapeArgument($event->output);
        //使用正确的用户完成事件的命令语法
        return $this->ensureCorrectUser(
            $event, $event->command.($event->shouldAppendOutput ? ' >> ' : ' > ').$output.' 2>&1'
        );
    }

    /**
     * Build the command for running the event in the foreground.
     *
     * 构建用于在后台运行事件的命令
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @return string
     */
    protected function buildBackgroundCommand(Event $event)
    {
        //转义字符串用作shell参数
        $output = ProcessUtils::escapeArgument($event->output);

        $redirect = $event->shouldAppendOutput ? ' >> ' : ' > ';
        //将给定的命令格式化为完全限定的可执行命令
        $finished = Application::formatCommandString('schedule:finish').' "'.$event->mutexName().'"';
        //使用正确的用户完成事件的命令语法
        return $this->ensureCorrectUser($event,
            '('.$event->command.$redirect.$output.' 2>&1 '.(windows_os() ? '&' : ';').' '.$finished.') > '
            //转义字符串用作shell参数(根据操作系统获得默认的输出)
            .ProcessUtils::escapeArgument($event->getDefaultOutput()).' 2>&1 &'
        );
    }

    /**
     * Finalize the event's command syntax with the correct user.
     *
     * 使用正确的用户完成事件的命令语法
     *
     * @param  \Illuminate\Console\Scheduling\Event  $event
     * @param  string  $command
     * @return string
     */
    protected function ensureCorrectUser(Event $event, $command)
    {
        return $event->user && ! windows_os() ? 'sudo -u '.$event->user.' -- sh -c \''.$command.'\'' : $command;
    }
}
