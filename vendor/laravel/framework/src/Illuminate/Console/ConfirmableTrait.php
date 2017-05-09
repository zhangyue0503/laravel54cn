<?php

namespace Illuminate\Console;

use Closure;

trait ConfirmableTrait
{
    /**
     * Confirm before proceeding with the action.
     *
     * 在继续操作之前确认
     *
     * This method only asks for confirmation in production.
     *
     * 这种方法只要求在生产中确认
     *
     * @param  string  $warning
     * @param  \Closure|bool|null  $callback
     * @return bool
     */
    public function confirmToProceed($warning = 'Application In Production!', $callback = null)
    {
        //                                    获取默认的确认回调
        $callback = is_null($callback) ? $this->getDefaultConfirmCallback() : $callback;

        $shouldConfirm = $callback instanceof Closure ? call_user_func($callback) : $callback;

        if ($shouldConfirm) {
            //获取命令选项的值
            if ($this->option('force')) {
                return true;
            }
            //编写一个字符串作为注释输出
            $this->comment(str_repeat('*', strlen($warning) + 12));
            $this->comment('*     '.$warning.'     *');
            $this->comment(str_repeat('*', strlen($warning) + 12));
            $this->output->writeln('');
            //与用户确认一个问题
            $confirmed = $this->confirm('Do you really wish to run this command?');

            if (! $confirmed) {
                $this->comment('Command Cancelled!');//编写一个字符串作为注释输出

                return false;
            }
        }

        return true;
    }

    /**
     * Get the default confirmation callback.
     *
     * 获取默认的确认回调
     *
     * @return \Closure
     */
    protected function getDefaultConfirmCallback()
    {
        return function () {
            //获取Laravel应用程序实例
            return $this->getLaravel()->environment() == 'production';
        };
    }
}
