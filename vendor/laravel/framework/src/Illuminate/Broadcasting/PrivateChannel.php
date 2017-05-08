<?php

namespace Illuminate\Broadcasting;
//私有频道
class PrivateChannel extends Channel
{
    /**
     * Create a new channel instance.
     *
     * Create a new channel instance.
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name)
    {
        parent::__construct('private-'.$name);
    }
}
