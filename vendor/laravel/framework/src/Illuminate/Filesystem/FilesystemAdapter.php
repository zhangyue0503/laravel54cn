<?php

namespace Illuminate\Filesystem;

use RuntimeException;
use Illuminate\Http\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use League\Flysystem\AdapterInterface;
use PHPUnit\Framework\Assert as PHPUnit;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Adapter\Local as LocalAdapter;
use Illuminate\Contracts\Filesystem\Cloud as CloudFilesystemContract;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Contracts\Filesystem\FileNotFoundException as ContractFileNotFoundException;

class FilesystemAdapter implements FilesystemContract, CloudFilesystemContract
{
    /**
     * The Flysystem filesystem implementation.
     *
     * Flysystem文件系统实现
     *
     * @var \League\Flysystem\FilesystemInterface
     */
    protected $driver;

    /**
     * Create a new filesystem adapter instance.
     *
     * 创建新的文件系统适配器实例
     *
     * @param  \League\Flysystem\FilesystemInterface  $driver
     * @return void
     */
    public function __construct(FilesystemInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Assert that the given file exists.
     *
     * 断言给定的文件存在
     *
     * @param  string  $path
     * @return void
     */
    public function assertExists($path)
    {
        //      断言一个条件是正确的
        PHPUnit::assertTrue(
            //确定一个文件是否存在
            $this->exists($path), "Unable to find a file at path [{$path}]."
        );
    }

    /**
     * Assert that the given file does not exist.
     *
     * 断言给定的文件不存在
     *
     * @param  string  $path
     * @return void
     */
    public function assertMissing($path)
    {
        //断言一个条件是假的
        PHPUnit::assertFalse(
        //确定一个文件是否存在
            $this->exists($path), "Found unexpected file at path [{$path}]."
        );
    }

    /**
     * Determine if a file exists.
     *
     * 确定一个文件是否存在
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path)
    {
        //                   检查文件是否存在
        return $this->driver->has($path);
    }

    /**
     * Get the contents of a file.
     *
     * 获取文件的内容
     *
     * @param  string  $path
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function get($path)
    {
        try {
            //                 读取一个文件
            return $this->driver->read($path);
        } catch (FileNotFoundException $e) {
            throw new ContractFileNotFoundException($path, $e->getCode(), $e);
        }
    }

    /**
     * Write the contents of a file.
     *
     * 写入文件的内容
     *
     * @param  string  $path
     * @param  string|resource  $contents
     * @param  array  $options
     * @return bool
     */
    public function put($path, $contents, $options = [])
    {
        if (is_string($options)) {
            $options = ['visibility' => $options];
        }

        // If the given contents is actually a file or uploaded file instance than we will
        // automatically store the file using a stream. This provides a convenient path
        // for the developer to store streams without managing them manually in code.
        //
        // 如果给定的内容实际上是一个文件或上传的文件实例，那么我们将使用一个流自动存储该文件
        // 这为开发人员提供了一种方便的途径来存储流，而无需在代码中手动管理它们
        //
        if ($contents instanceof File ||
            $contents instanceof UploadedFile) {
            //将上传的文件存储在磁盘上
            return $this->putFile($path, $contents, $options);
        }

        return is_resource($contents)
                ? $this->driver->putStream($path, $contents, $options)//如果存在，创建一个文件或更新
                : $this->driver->put($path, $contents, $options);//如果存在，创建一个文件或更新
    }

    /**
     * Store the uploaded file on the disk.
     *
     * 将上传的文件存储在磁盘上
     *
     * @param  string  $path
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  array  $options
     * @return string|false
     */
    public function putFile($path, $file, $options = [])
    {
        //将上传的文件存储在磁盘上，并带有一个给定的名称     为文件获取文件名
        return $this->putFileAs($path, $file, $file->hashName(), $options);
    }

    /**
     * Store the uploaded file on the disk with a given name.
     *
     * 将上传的文件存储在磁盘上，并带有一个给定的名称
     *
     * @param  string  $path
     * @param  \Illuminate\Http\File|\Illuminate\Http\UploadedFile  $file
     * @param  string  $name
     * @param  array  $options
     * @return string|false
     */
    public function putFileAs($path, $file, $name, $options = [])
    {
        $stream = fopen($file->getRealPath(), 'r+');

        // Next, we will format the path of the file and store the file using a stream since
        // they provide better performance than alternatives. Once we write the file this
        // stream will get closed automatically by us so the developer doesn't have to.
        //
        // 接下来，我们将对文件的路径进行格式化，并使用流存储文件，因为它们提供了比其他选项更好的性能
        // 一旦我们写入文件，这个流就会自动被我们自动关闭所以开发者就不需要
        //
        //           写入文件的内容
        $result = $this->put(
            $path = trim($path.'/'.$name, '/'), $stream, $options
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $result ? $path : false;
    }

    /**
     * Get the visibility for the given path.
     *
     * 获得给定路径的可见性
     *
     * @param  string  $path
     * @return string
     */
    public function getVisibility($path)
    {
        //                 获取文件的可见性
        if ($this->driver->getVisibility($path) == AdapterInterface::VISIBILITY_PUBLIC) {
            return FilesystemContract::VISIBILITY_PUBLIC;
        }

        return FilesystemContract::VISIBILITY_PRIVATE;
    }

    /**
     * Set the visibility for the given path.
     *
     * 设置给定路径的可见性
     *
     * @param  string  $path
     * @param  string  $visibility
     * @return void
     */
    public function setVisibility($path, $visibility)
    {
        //              设置文件的可见性               解析给定的可见值
        return $this->driver->setVisibility($path, $this->parseVisibility($visibility));
    }

    /**
     * Prepend to a file.
     *
     * 预先加载到一个文件
     *
     * @param  string  $path
     * @param  string  $data
     * @param  string  $separator
     * @return int
     */
    public function prepend($path, $data, $separator = PHP_EOL)
    {
        //确定一个文件是否存在
        if ($this->exists($path)) {
            //写入文件的内容                              获取文件的内容
            return $this->put($path, $data.$separator.$this->get($path));
        }

        return $this->put($path, $data);
    }

    /**
     * Append to a file.
     *
     * 附加到文件
     *
     * @param  string  $path
     * @param  string  $data
     * @param  string  $separator
     * @return int
     */
    public function append($path, $data, $separator = PHP_EOL)
    {
        //确定一个文件是否存在
        if ($this->exists($path)) {
            //写入文件的内容            获取文件的内容
            return $this->put($path, $this->get($path).$separator.$data);
        }

        return $this->put($path, $data);
    }

    /**
     * Delete the file at a given path.
     *
     * 在给定的路径中删除该文件
     *
     * @param  string|array  $paths
     * @return bool
     */
    public function delete($paths)
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                //                删除一个文件
                if (! $this->driver->delete($path)) {
                    $success = false;
                }
            } catch (FileNotFoundException $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Copy a file to a new location.
     *
     * 将文件复制到新位置
     *
     * @param  string  $from
     * @param  string  $to
     * @return bool
     */
    public function copy($from, $to)
    {
        //                   复制一个文件
        return $this->driver->copy($from, $to);
    }

    /**
     * Move a file to a new location.
     *
     * 将文件移动到新位置
     *
     * @param  string  $from
     * @param  string  $to
     * @return bool
     */
    public function move($from, $to)
    {
        //重命名一个文件
        return $this->driver->rename($from, $to);
    }

    /**
     * Get the file size of a given file.
     *
     * 获取给定文件的文件大小
     *
     * @param  string  $path
     * @return int
     */
    public function size($path)
    {
        //                      获取文件大小
        return $this->driver->getSize($path);
    }

    /**
     * Get the mime-type of a given file.
     *
     * 得到给定文件的mime类型
     *
     * @param  string  $path
     * @return string|false
     */
    public function mimeType($path)
    {
        //                    得到文件的mime类型
        return $this->driver->getMimetype($path);
    }

    /**
     * Get the file's last modification time.
     *
     * 获取文件的最后修改时间
     *
     * @param  string  $path
     * @return int
     */
    public function lastModified($path)
    {
        //               获取文件的时间戳
        return $this->driver->getTimestamp($path);
    }

    /**
     * Get the URL for the file at the given path.
     *
     * 获取给定路径上的文件的URL
     *
     * @param  string  $path
     * @return string
     */
    public function url($path)
    {
        $adapter = $this->driver->getAdapter();//获取适配器

        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        } elseif ($adapter instanceof AwsS3Adapter) {
            return $this->getAwsUrl($adapter, $path);//获取给定路径上的文件的URL
        } elseif ($adapter instanceof LocalAdapter) {
            return $this->getLocalUrl($path);//获取给定路径上的文件的URL
        } else {
            throw new RuntimeException('This driver does not support retrieving URLs.');
        }
    }

    /**
     * Get the URL for the file at the given path.
     *
     * 获取给定路径上的文件的URL
     *
     * @param  \League\Flysystem\AwsS3v3\AwsS3Adapter  $adapter
     * @param  string  $path
     * @return string
     */
    protected function getAwsUrl($adapter, $path)
    {
        return $adapter->getClient()->getObjectUrl(
            //                             获得路径前缀
            $adapter->getBucket(), $adapter->getPathPrefix().$path
        );
    }

    /**
     * Get the URL for the file at the given path.
     *
     * 获取给定路径上的文件的URL
     *
     * @param  string  $path
     * @return string
     */
    protected function getLocalUrl($path)
    {
        $config = $this->driver->getConfig();//获取文件系统连接配置

        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        //
        // 如果在磁盘配置上设置了一个显式的基本URL，那么我们将使用它作为基本URL，而不是默认路径
        // 这使得开发人员能够完全控制文件系统生成的url的基本路径
        //
        if ($config->has('url')) {
            return trim($config->get('url'), '/').'/'.ltrim($path, '/');
        }

        $path = '/storage/'.$path;

        // If the path contains "storage/public", it probably means the developer is using
        // the default disk to generate the path instead of the "public" disk like they
        // are really supposed to use. We will remove the public from this path here.
        //
        // 如果路径包含“存储/公众”,这可能意味着开发人员使用默认的磁盘生成路径,而不是“公共”磁盘像他们真的应该使用
        // 我们将把公众从这条道路上移除
        //
        //确定一个给定的字符串包含另一个字符串
        if (Str::contains($path, '/storage/public/')) {
            //替换字符串中第一次出现的给定值
            return Str::replaceFirst('/public/', '/', $path);
        } else {
            return $path;
        }
    }

    /**
     * Get an array of all files in a directory.
     *
     * 在一个目录中获取所有文件的数组
     *
     * @param  string|null  $directory
     * @param  bool  $recursive
     * @return array
     */
    public function files($directory = null, $recursive = false)
    {
        //目录的目录内容
        $contents = $this->driver->listContents($directory, $recursive);
        //按类型过滤目录内容
        return $this->filterContentsByType($contents, 'file');
    }

    /**
     * Get all of the files from the given directory (recursive).
     *
     * 从给定目录中获取所有文件(递归)
     *
     * @param  string|null  $directory
     * @return array
     */
    public function allFiles($directory = null)
    {
        //在一个目录中获取所有文件的数组
        return $this->files($directory, true);
    }

    /**
     * Get all of the directories within a given directory.
     *
     * 在给定目录中获取所有目录
     *
     * @param  string|null  $directory
     * @param  bool  $recursive
     * @return array
     */
    public function directories($directory = null, $recursive = false)
    {
        //目录的目录内容
        $contents = $this->driver->listContents($directory, $recursive);
        //按类型过滤目录内容
        return $this->filterContentsByType($contents, 'dir');
    }

    /**
     * Get all (recursive) of the directories within a given directory.
     *
     * 在给定目录中获取目录的所有(递归)
     *
     * @param  string|null  $directory
     * @return array
     */
    public function allDirectories($directory = null)
    {
        //在给定目录中获取所有目录
        return $this->directories($directory, true);
    }

    /**
     * Create a directory.
     *
     * 创建一个目录
     *
     * @param  string  $path
     * @return bool
     */
    public function makeDirectory($path)
    {
        //创建一个目录
        return $this->driver->createDir($path);
    }

    /**
     * Recursively delete a directory.
     *
     * 递归删除目录
     *
     * @param  string  $directory
     * @return bool
     */
    public function deleteDirectory($directory)
    {
        //                  删除一个目录
        return $this->driver->deleteDir($directory);
    }

    /**
     * Get the Flysystem driver.
     *
     * 获得Flysystem驱动
     *
     * @return \League\Flysystem\FilesystemInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Filter directory contents by type.
     *
     * 按类型过滤目录内容
     *
     * @param  array  $contents
     * @param  string  $type
     * @return array
     */
    protected function filterContentsByType($contents, $type)
    {
        //创建一个新的集合实例，如果该值不是一个准备好的
        return Collection::make($contents)
            ->where('type', $type)//按给定键值对筛选项目
            ->pluck('path')//获取给定键的值
            ->values()//重置基础阵列上的键
            ->all();//获取集合中的所有项目
    }

    /**
     * Parse the given visibility value.
     *
     * 解析给定的可见值
     *
     * @param  string|null  $visibility
     * @return string|null
     *
     * @throws \InvalidArgumentException
     */
    protected function parseVisibility($visibility)
    {
        if (is_null($visibility)) {
            return;
        }

        switch ($visibility) {
            case FilesystemContract::VISIBILITY_PUBLIC:
                return AdapterInterface::VISIBILITY_PUBLIC;
            case FilesystemContract::VISIBILITY_PRIVATE:
                return AdapterInterface::VISIBILITY_PRIVATE;
        }

        throw new InvalidArgumentException('Unknown visibility: '.$visibility);
    }

    /**
     * Pass dynamic methods call onto Flysystem.
     *
     * 通过动态方法调用Flysystem
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, array $parameters)
    {
        return call_user_func_array([$this->driver, $method], $parameters);
    }
}
