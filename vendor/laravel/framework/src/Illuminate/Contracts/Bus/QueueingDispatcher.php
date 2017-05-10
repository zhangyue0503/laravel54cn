<?php

namespace Illuminate\Contracts\Bus;

interface QueueingDispatcher extends Dispatcher
{
    /**
     * Dispatch a command to its appropriate handler behind a queue.
     *
     * 向队列后面的适当处理程序分派一个命令
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function dispatchToQueue($command);
}
