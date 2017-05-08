<?php

namespace Illuminate\Auth;

use Illuminate\Support\Str;

class Recaller
{
    /**
     * The "recaller" / "remember me" cookie string.
     *
     * “recaller”/“记住我”cookie字符串
     *
     * @var string
     */
    protected $recaller;

    /**
     * Create a new recaller instance.
     *
     * 创建一个新的recaller实例
     *
     * @param  string  $recaller
     * @return void
     */
    public function __construct($recaller)
    {
        $this->recaller = $recaller;
    }

    /**
     * Get the user ID from the recaller.
     *
     * 从recaller获取用户ID
     *
     * @return string
     */
    public function id()
    {
        return explode('|', $this->recaller, 2)[0];
    }

    /**
     * Get the "remember token" token from the recaller.
     *
     * 从recaller获得“记住牌”令牌
     *
     * @return string
     */
    public function token()
    {
        return explode('|', $this->recaller, 2)[1];
    }

    /**
     * Determine if the recaller is valid.
     *
     * 确定recaller是否有效
     *
     * @return bool
     */
    public function valid()
    {
        //    确定recaller是否为无效字符串           确定recaller是否有两个部分
        return $this->properString() && $this->hasBothSegments();
    }

    /**
     * Determine if the recaller is an invalid string.
     *
     * 确定recaller是否为无效字符串
     *
     * @return bool
     */
    protected function properString()
    {
        //                                     确定一个给定的字符串包含另一个字符串
        return is_string($this->recaller) && Str::contains($this->recaller, '|');
    }

    /**
     * Determine if the recaller has both segments.
     *
     * 确定recaller是否有两个部分
     *
     * @return bool
     */
    protected function hasBothSegments()
    {
        $segments = explode('|', $this->recaller);

        return count($segments) == 2 && trim($segments[0]) !== '' && trim($segments[1]) !== '';
    }
}
