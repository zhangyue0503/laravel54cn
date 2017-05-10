<?php

namespace Illuminate\Contracts\Bus;
//分配
interface Dispatcher
{
    /**
     * Dispatch a command to its appropriate handler.
     *
     * 向适当的处理程序分派一个命令
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatch($command);

    /**
     * Dispatch a command to its appropriate handler in the current process.
	 *
	 * 在当前进程中向其适当的处理程序发送命令
	 * * 在当前进程中将命令发送给适当的处理模块
     *
     * @param  mixed  $command
     * @param  mixed  $handler
     * @return mixed
     */
    public function dispatchNow($command, $handler = null);

    /**
     * Set the pipes commands should be piped through before dispatching.
     *
     * 设置管道命令应该在分派之前通过管道完成
     *
     * @param  array  $pipes
     * @return $this
     */
    public function pipeThrough(array $pipes);
}
