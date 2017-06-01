<?php

namespace Illuminate\View\Concerns;

trait ManagesTranslations
{
    /**
     * The translation replacements for the translation being rendered.
     *
     * 翻译转换的翻译版本
     *
     * @var array
     */
    protected $translationReplacements = [];

    /**
     * Start a translation block.
     *
     * 开始翻译块
     *
     * @param  array  $replacements
     * @return void
     */
    public function startTranslation($replacements = [])
    {
        ob_start();

        $this->translationReplacements = $replacements;
    }

    /**
     * Render the current translation.
     *
     * 使当前的翻译
     *
     * @return string
     */
    public function renderTranslation()
    {
        //从容器中解析给定类型                          从JSON翻译文件获取给定键的翻译
        return $this->container->make('translator')->getFromJson(
            trim(ob_get_clean()), $this->translationReplacements
        );
    }
}
