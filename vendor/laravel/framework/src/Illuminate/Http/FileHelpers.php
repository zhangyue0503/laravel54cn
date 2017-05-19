<?php

namespace Illuminate\Http;

use Illuminate\Support\Str;

trait FileHelpers
{
    /**
     * The cache copy of the file's hash name.
     *
     * 文件的散列名称的缓存副本
     *
     * @var string
     */
    protected $hashName = null;

    /**
     * Get the fully qualified path to the file.
     *
     * 获取文件的完全限定路径
     *
     * @return string
     */
    public function path()
    {
        return $this->getRealPath();
    }

    /**
     * Get the file's extension.
     *
     * 获取文件的扩展名
     *
     * @return string
     */
    public function extension()
    {
        //根据mime类型返回扩展名
        return $this->guessExtension();
    }

    /**
     * Get the file's extension supplied by the client.
     *
     * 获取客户端提供的文件扩展名
     *
     * @return string
     */
    public function clientExtension()
    {
        //根据客户端mime类型返回扩展
        return $this->guessClientExtension();
    }

    /**
     * Get a filename for the file.
     *
     * 为文件获取文件名
     *
     * @param  string  $path
     * @return string
     */
    public function hashName($path = null)
    {
        if ($path) {
            $path = rtrim($path, '/').'/';
        }
        //                                             生成一个更真实的“随机”alpha数字字符串
        $hash = $this->hashName ?: $this->hashName = Str::random(40);
        //                          根据mime类型返回扩展名
        return $path.$hash.'.'.$this->guessExtension();
    }
}
