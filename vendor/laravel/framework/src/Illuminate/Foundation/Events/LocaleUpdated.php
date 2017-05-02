<?php

namespace Illuminate\Foundation\Events;

class LocaleUpdated
{
    /**
     * The new locale.
     *
     * 新的语言环境
     *
     * @var string
     */
    public $locale;

    /**
     * Create a new event instance.
     *
     * 创建一个新的事件实例
     *
     * @param  string  $locale
     * @return void
     */
    public function __construct($locale)
    {
        $this->locale = $locale;
    }
}
