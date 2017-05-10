<?php

namespace Illuminate\Contracts\Queue;

interface QueueableEntity
{
    /**
     * Get the queueable identity for the entity.
     *
     * 为实体获取可排队的标识
     *
     * @return mixed
     */
    public function getQueueableId();
}
