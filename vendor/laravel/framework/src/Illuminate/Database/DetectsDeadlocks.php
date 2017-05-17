<?php

namespace Illuminate\Database;

use Exception;
use Illuminate\Support\Str;
//检测死锁
trait DetectsDeadlocks
{
    /**
     * Determine if the given exception was caused by a deadlock.
     *
     * 确定给定的异常是否由死锁引起
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function causedByDeadlock(Exception $e)
    {
        $message = $e->getMessage();
        //确定一个给定的字符串包含另一个字符串
        return Str::contains($message, [
            'Deadlock found when trying to get lock',
            'deadlock detected',
            'The database file is locked',
            'database is locked',
            'database table is locked',
            'A table in the database is locked',
            'has been chosen as the deadlock victim',
        ]);
    }
}
