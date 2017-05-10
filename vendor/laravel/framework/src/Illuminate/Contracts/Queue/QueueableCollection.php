<?php

namespace Illuminate\Contracts\Queue;

interface QueueableCollection
{
    /**
     * Get the type of the entities being queued.
     *
     * 获取正在排队的实体的类型
     *
     * @return string|null
     */
    public function getQueueableClass();

    /**
     * Get the identifiers for all of the entities.
     *
     * 获取所有实体的标识符
     *
     * @return array
     */
    public function getQueueableIds();
}
