<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Queue\InteractsWithTime;

class DatabaseJobRecord
{
    use InteractsWithTime;

    /**
     * The underlying job record.
     *
     * 底层的工作记录
     *
     * @var \StdClass
     */
    protected $record;

    /**
     * Create a new job record instance.
     *
     * 创建一个新的工作记录实例
     *
     * @param  \StdClass  $record
     * @return void
     */
    public function __construct($record)
    {
        $this->record = $record;
    }

    /**
     * Increment the number of times the job has been attempted.
     *
     * 增加工作尝试次数的次数
     *
     * @return int
     */
    public function increment()
    {
        $this->record->attempts++;

        return $this->record->attempts;
    }

    /**
     * Update the "reserved at" timestamp of the job.
     *
     * 更新作业的“reserved at”时间戳
     *
     * @return int
     */
    public function touch()
    {
        //                            将当前系统时间作为UNIX时间戳
        $this->record->reserved_at = $this->currentTime();

        return $this->record->reserved_at;
    }

    /**
     * Dynamically access the underlying job information.
     *
     * 动态访问底层工作信息
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->record->{$key};
    }
}
