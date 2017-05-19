<?php

namespace Illuminate\Http\Testing;

class FileFactory
{
    /**
     * Create a new fake file.
     *
     * 创建一个新的假文件
     *
     * @param  string  $name
     * @param  int  $kilobytes
     * @return \Illuminate\Http\Testing\File
     */
    public function create($name, $kilobytes = 0)
    {
        //用给定的值调用给定的闭包，然后返回值  创建一个新的文件实例
        return tap(new File($name, tmpfile()), function ($file) use ($kilobytes) {
            $file->sizeToReport = $kilobytes * 1024;
        });
    }

    /**
     * Create a new fake image.
     *
     * 创建一个新的假图片
     *
     * @param  string  $name
     * @param  int  $width
     * @param  int  $height
     * @return \Illuminate\Http\Testing\File
     */
    public function image($name, $width = 10, $height = 10)
    {
        //创建一个新的文件实例       生成一个给定宽度和高度的虚拟图像
        return new File($name, $this->generateImage($width, $height));
    }

    /**
     * Generate a dummy image of the given width and height.
     *
     * 生成一个给定宽度和高度的虚拟图像
     *
     * @param  int  $width
     * @param  int  $height
     * @return resource
     */
    protected function generateImage($width, $height)
    {
        ////用给定的值调用给定的闭包，然后返回值
        return tap(tmpfile(), function ($temp) use ($width, $height) {
            imagepng(imagecreatetruecolor($width, $height), $temp);
        });
    }
}
