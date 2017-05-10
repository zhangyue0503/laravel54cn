<?php

namespace Illuminate\Contracts\Translation;

interface Translator
{
    /**
     * Get the translation for a given key.
     *
     * 获得给定键的翻译
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return mixed
     */
    public function trans($key, array $replace = [], $locale = null);

    /**
     * Get a translation according to an integer value.
     *
     * 根据一个整数值得到一个翻译
     *
     * @param  string  $key
     * @param  int|array|\Countable  $number
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function transChoice($key, $number, array $replace = [], $locale = null);

    /**
     * Get the default locale being used.
     *
     * 使用默认的语言环境
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set the default locale.
     *
     * 设置默认的语言环境
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale);
}
