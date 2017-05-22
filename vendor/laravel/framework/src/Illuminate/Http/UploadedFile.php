<?php

namespace Illuminate\Http;

use Illuminate\Support\Arr;
use Illuminate\Container\Container;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class UploadedFile extends SymfonyUploadedFile
{
    use FileHelpers, Macroable;

    /**
     * Begin creating a new file fake.
     *
     * 开始创建一个新文件
     *
     * @return \Illuminate\Http\Testing\FileFactory
     */
    public static function fake()
    {
        return new Testing\FileFactory;
    }

    /**
     * Store the uploaded file on a filesystem disk.
     *
     * 将上传的文件存储在文件系统磁盘上
     *
     * @param  string  $path
     * @param  array  $options
     * @return string|false
     */
    public function store($path, $options = [])
    {
        // 将上传的文件存储在文件系统磁盘上      为文件获取文件名       解析和格式化给定选项
        return $this->storeAs($path, $this->hashName(), $this->parseOptions($options));
    }

    /**
     * Store the uploaded file on a filesystem disk with public visibility.
     *
     * 将上传的文件存储在具有公共可见性的文件系统磁盘上
     *
     * @param  string  $path
     * @param  array  $options
     * @return string|false
     */
    public function storePublicly($path, $options = [])
    {
        //            解析和格式化给定选项
        $options = $this->parseOptions($options);

        $options['visibility'] = 'public';
        //将上传的文件存储在文件系统磁盘上      为文件获取文件名
        return $this->storeAs($path, $this->hashName(), $options);
    }

    /**
     * Store the uploaded file on a filesystem disk with public visibility.
     *
     * 将上传的文件存储在具有公共可见性的文件系统磁盘上
     *
     * @param  string  $path
     * @param  string  $name
     * @param  array  $options
     * @return string|false
     */
    public function storePubliclyAs($path, $name, $options = [])
    {
        //            解析和格式化给定选项
        $options = $this->parseOptions($options);

        $options['visibility'] = 'public';
        //将上传的文件存储在文件系统磁盘上
        return $this->storeAs($path, $name, $options);
    }

    /**
     * Store the uploaded file on a filesystem disk.
     *
     * 将上传的文件存储在文件系统磁盘上
     *
     * @param  string  $path
     * @param  string  $name
     * @param  array  $options
     * @return string|false
     */
    public function storeAs($path, $name, $options = [])
    {
        //               解析和格式化给定选项
        $options = $this->parseOptions($options);
        //从数组中获取值，并将其移除
        $disk = Arr::pull($options, 'disk');
        //设置容器的全局可用实例            从容器中解析给定类型               获得文件系统实现   将上传的文件存储在磁盘上，并带有一个给定的名称
        return Container::getInstance()->make(FilesystemFactory::class)->disk($disk)->putFileAs(//Illuminate\Filesystem\FilesystemAdapter
            $path, $this, $name, $options
        );
    }

    /**
     * Create a new file instance from a base instance.
     *
     * 从基础实例创建新的文件实例
     *
     * @param  \Symfony\Component\HttpFoundation\File\UploadedFile  $file
     * @param  bool $test
     * @return static
     */
    public static function createFromBase(SymfonyUploadedFile $file, $test = false)
    {
        return $file instanceof static ? $file : new static(
            $file->getPathname(),
            $file->getClientOriginalName(),//返回原始文件名
            $file->getClientMimeType(),//返回文件mime类型
            $file->getClientSize(),//返回文件大小
            $file->getError(),//返回上传错误
            $test
        );
    }

    /**
     * Parse and format the given options.
     *
     * 解析和格式化给定选项
     *
     * @param  array|string  $options
     * @return array
     */
    protected function parseOptions($options)
    {
        if (is_string($options)) {
            $options = ['disk' => $options];
        }

        return $options;
    }
}
