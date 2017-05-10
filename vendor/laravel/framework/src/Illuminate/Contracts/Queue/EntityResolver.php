<?php

namespace Illuminate\Contracts\Queue;

interface EntityResolver
{
    /**
     * Resolve the entity for the given ID.
     *
     * 为给定的ID解析实体
     *
     * @param  string  $type
     * @param  mixed  $id
     * @return mixed
     */
    public function resolve($type, $id);
}
