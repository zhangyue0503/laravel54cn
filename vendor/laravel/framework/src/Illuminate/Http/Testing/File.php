<?php

namespace Illuminate\Http\Testing;

use Illuminate\Http\UploadedFile;

class File extends UploadedFile
{
    /**
     * The name of the file.
     *
     * 文件名称
     *
     * @var string
     */
    public $name;

    /**
     * The temporary file resource.
     *
     * 临时文件资源
     *
     * @var resource
     */
    public $tempFile;

    /**
     * The "size" to report.
     *
     * “大小”报告
     *
     * @var int
     */
    public $sizeToReport;

    /**
     * Create a new file instance.
     *
     * 创建一个新的文件实例
     *
     * @param  string  $name
     * @param  resource  $tempFile
     * @return void
     */
    public function __construct($name, $tempFile)
    {
        $this->name = $name;
        $this->tempFile = $tempFile;
        //接受提供的信息上传文件的PHP全局$_FILE
        parent::__construct(
            //获取临时文件的路径              获取文件的MIME类型
            $this->tempFilePath(), $name, $this->getMimeType(),
            //获取临时文件的路径
            filesize($this->tempFilePath()), $error = null, $test = true
        );
    }

    /**
     * Create a new fake file.
     *
     * 创建一个新的假文件
     *
     * @param  string  $name
     * @param  int  $kilobytes
     * @return \Illuminate\Http\Testing\File
     */
    public static function create($name, $kilobytes = 0)
    {
        //创建一个新的假文件
        return (new FileFactory)->create($name, $kilobytes);
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
    public static function image($name, $width = 10, $height = 10)
    {
        //创建一个新的假文件
        return (new FileFactory)->image($name, $width, $height);
    }

    /**
     * Set the "size" of the file in kilobytes.
     *
     * 设置在千字节的“大小”文件
     *
     * @param  int  $kilobytes
     * @return $this
     */
    public function size($kilobytes)
    {
        $this->sizeToReport = $kilobytes * 1024;

        return $this;
    }

    /**
     * Get the size of the file.
     *
     * 获取文件大小
     *
     * @return int
     */
    public function getSize()
    {
        return $this->sizeToReport ?: parent::getSize();
    }

    /**
     * Get the MIME type for the file.
     *
     * 获取文件的MIME类型
     *
     * @return string
     */
    public function getMimeType()
    {
        //根据文件的扩展获取一个文件的MIME类型
        return MimeType::from($this->name);
    }

    /**
     * Get the path to the temporary file.
     *
     * 获取临时文件的路径
     *
     * @return string
     */
    protected function tempFilePath()
    {
        //获得网页的各meta项目信息
        return stream_get_meta_data($this->tempFile)['uri'];
    }
}
