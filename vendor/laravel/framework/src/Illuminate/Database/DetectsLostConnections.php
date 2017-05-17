<?php

namespace Illuminate\Database;

use Exception;
use Illuminate\Support\Str;
//检测连接丢失
trait DetectsLostConnections
{
    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * 确定给定的异常是否由丢失的连接引起
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function causedByLostConnection(Exception $e)
    {
        $message = $e->getMessage();
        //确定一个给定的字符串包含另一个字符串
        return Str::contains($message, [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
        ]);
    }
}
