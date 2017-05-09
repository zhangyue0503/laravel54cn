<?php

namespace Illuminate\Cache;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Filesystem\Filesystem;

class FileStore implements Store
{
    use RetrievesMultipleKeys;

    /**
     * The Illuminate Filesystem instance.
     *
     * Illuminate文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The file cache directory.
     *
     * 文件缓存目录
     *
     * @var string
     */
    protected $directory;

    /**
     * Create a new file cache store instance.
     *
     * 创建一个新的文件缓存存储实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $directory
     * @return void
     */
    public function __construct(Filesystem $files, $directory)
    {
        $this->files = $files;
        $this->directory = $directory;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * 通过键从缓存中检索一个项
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        // 使用“点”符号从数组中获取一个项(通过键从缓存中检索一个项和过期时间,)
        return Arr::get($this->getPayload($key), 'data');
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * 在缓存中存储一个条目，在给定的时间内
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  float|int  $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        // 如果需要，创建文件缓存目录(获取给定缓存键的完整路径)
        $this->ensureCacheDirectoryExists($path = $this->path($key));
        //写入文件的内容
        $this->files->put(
            //      根据给定的时间来获得过期时间
            $path, $this->expiration($minutes).serialize($value), true
        );
    }

    /**
     * Create the file cache directory if necessary.
     *
     * 如果需要，创建文件缓存目录
     *
     * @param  string  $path
     * @return void
     */
    protected function ensureCacheDirectoryExists($path)
    {
        //          确定文件或目录是否存在
        if (! $this->files->exists(dirname($path))) {
            //创建一个目录
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Increment the value of an item in the cache.
     *
     * 增加缓存中的项的值
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        //通过键从缓存中检索一个项和过期时间
        $raw = $this->getPayload($key);
        //用给定的值调用给定的闭包，然后返回值
        return tap(((int) $raw['data']) + $value, function ($newValue) use ($key, $raw) {
            //在缓存中存储一个条目，在给定的时间内
            $this->put($key, $newValue, $raw['time']);
        });
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * 在缓存中减去一个项目的值
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        //增加缓存中的项的值
        return $this->increment($key, $value * -1);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * 在缓存中无限期地存储一个项
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        //在缓存中存储一个条目，在给定的时间内
        $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * 从缓存中删除一个项目
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        //确定文件或目录是否存在(获取给定缓存键的完整路径)
        if ($this->files->exists($file = $this->path($key))) {
            //             在给定的路径中删除该文件
            return $this->files->delete($file);
        }

        return false;
    }

    /**
     * Remove all items from the cache.
     *
     * 从缓存中删除所有项
     *
     * @return bool
     */
    public function flush()
    {
        //              确定给定路径是否为目录
        if (! $this->files->isDirectory($this->directory)) {
            return false;
        }
        //            在给定目录中获取所有目录
        foreach ($this->files->directories($this->directory) as $directory) {
            //                递归删除目录
            if (! $this->files->deleteDirectory($directory)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retrieve an item and expiry time from the cache by key.
     *
     * 通过键从缓存中检索一个项和过期时间
     *
     * @param  string  $key
     * @return array
     */
    protected function getPayload($key)
    {
        $path = $this->path($key);

        // If the file doesn't exists, we obviously can't return the cache so we will
        // just return null. Otherwise, we'll get the contents of the file and get
        // the expiration UNIX timestamps from the start of the file's contents.
        //
        // 如果文件不存在，我们显然无法返回缓存，因此我们将返回null
        // 否则，我们将获得文件的内容，并从文件内容的开始获得过期的UNIX时间戳
        //
        try {
            $expire = substr(
                //                    获取文件的内容
                $contents = $this->files->get($path, true), 0, 10
            );
        } catch (Exception $e) {
            //为缓存获取默认的空有效载荷
            return $this->emptyPayload();
        }

        // If the current time is greater than expiration timestamps we will delete
        // the file and return null. This helps clean up the old files and keeps
        // this directory much cleaner for us as old files aren't hanging out.
        //
        // 如果当前时间大于过期时间戳，我们将删除该文件并返回null
        // 这有助于清理旧文件，并使这个目录更干净，因为旧文件不会挂掉
        //
        //获取当前日期和时间的Carbon实例
        if (Carbon::now()->getTimestamp() >= $expire) {
            $this->forget($key);//从缓存中删除一个项目

            return $this->emptyPayload();//为缓存获取默认的空有效载荷
        }

        $data = unserialize(substr($contents, 10));

        // Next, we'll extract the number of minutes that are remaining for a cache
        // so that we can properly retain the time for things like the increment
        // operation that may be performed on this cache on a later operation.
        //
        // 接下来,我们将提取剩余分钟数为一个缓存,这样我们可以适当保留时间的增量操作可能对这个缓存后执行操作
        //
        //                    获取当前日期和时间的Carbon实例
        $time = ($expire - Carbon::now()->getTimestamp()) / 60;

        return compact('data', 'time');
    }

    /**
     * Get a default empty payload for the cache.
     *
     * 为缓存获取默认的空有效载荷
     *
     * @return array
     */
    protected function emptyPayload()
    {
        return ['data' => null, 'time' => null];
    }

    /**
     * Get the full path for the given cache key.
     *
     * 获取给定缓存键的完整路径
     *
     * @param  string  $key
     * @return string
     */
    protected function path($key)
    {
        $parts = array_slice(str_split($hash = sha1($key), 2), 0, 2);

        return $this->directory.'/'.implode('/', $parts).'/'.$hash;
    }

    /**
     * Get the expiration time based on the given minutes.
     *
     * 根据给定的时间来获得过期时间
     *
     * @param  float|int  $minutes
     * @return int
     */
    protected function expiration($minutes)
    {
        //        获取当前日期和时间的Carbon实例
        $time = Carbon::now()->getTimestamp() + (int) ($minutes * 60);

        return $minutes === 0 || $time > 9999999999 ? 9999999999 : (int) $time;
    }

    /**
     * Get the Filesystem instance.
     *
     * 获取文件系统实例
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

    /**
     * Get the working directory of the cache.
     *
     * 获取缓存的工作目录
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Get the cache key prefix.
     *
     * 获取高速缓存键前缀
     *
     * @return string
     */
    public function getPrefix()
    {
        return '';
    }
}
