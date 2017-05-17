<?php

namespace Illuminate\Filesystem;

use ErrorException;
use FilesystemIterator;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class Filesystem
{
    use Macroable;

    /**
     * Determine if a file or directory exists.
	 *
	 * 确定文件或目录是否存在
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path)
    {
        return file_exists($path);
    }

    /**
     * Get the contents of a file.
     *
     * 获取文件的内容
     *
     * @param  string  $path
     * @param  bool  $lock
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function get($path, $lock = false)
    {
        //确定给定路径是否为文件
        if ($this->isFile($path)) {
            //             获取具有共享访问权限的文件的内容
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    /**
     * Get contents of a file with shared access.
     *
     * 获取具有共享访问权限的文件的内容
     *
     * @param  string  $path
     * @return string
     */
    public function sharedGet($path)
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);
                    //                           获取给定文件的文件大小
                    $contents = fread($handle, $this->size($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }

    /**
     * Get the returned value of a file.
	 *
	 * 获取文件的返回值
     *
     * @param  string  $path
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getRequire($path)
    {
        if ($this->isFile($path)) { // 确定给定路径是否为文件
            return require $path; //require 文件
        }

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    /**
     * Require the given file once.
     *
     * 要求给定的文件一次
     *
     * @param  string  $file
     * @return mixed
     */
    public function requireOnce($file)
    {
        require_once $file;
    }

    /**
     * Write the contents of a file.
     *
     * 写入文件的内容
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  bool  $lock
     * @return int
     */
    public function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    /**
     * Prepend to a file.
     *
     * 预先加载到一个文件
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public function prepend($path, $data)
    {
        //确定文件或目录是否存在
        if ($this->exists($path)) {
            //写入文件的内容                    获取文件的内容
            return $this->put($path, $data.$this->get($path));
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
     * @return int
     */
    public function append($path, $data)
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }

    /**
     * Get or set UNIX mode of a file or directory.
     *
     * 获取或设置文件或目录的UNIX模式
     *
     * @param  string  $path
     * @param  int  $mode
     * @return mixed
     */
    public function chmod($path, $mode = null)
    {
        if ($mode) {
            return chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
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
                if (! @unlink($path)) {
                    $success = false;
                }
            } catch (ErrorException $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Move a file to a new location.
     *
     * 将文件移动到新位置
     *
     * @param  string  $path
     * @param  string  $target
     * @return bool
     */
    public function move($path, $target)
    {
        return rename($path, $target);
    }

    /**
     * Copy a file to a new location.
     *
     * 将文件复制到新位置
     *
     * @param  string  $path
     * @param  string  $target
     * @return bool
     */
    public function copy($path, $target)
    {
        return copy($path, $target);
    }

    /**
     * Create a hard link to the target file or directory.
     *
     * 创建一个指向目标文件或目录的硬链接
     *
     * @param  string  $target
     * @param  string  $link
     * @return void
     */
    public function link($target, $link)
    {
        if (! windows_os()) {
            return symlink($target, $link);
        }
        //确定给定路径是否为目录
        $mode = $this->isDirectory($target) ? 'J' : 'H';

        exec("mklink /{$mode} \"{$link}\" \"{$target}\"");
    }

    /**
     * Extract the file name from a file path.
     *
     * 从文件路径中提取文件名
     *
     * @param  string  $path
     * @return string
     */
    public function name($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path.
     *
     * 从文件路径中提取跟踪名组件
     *
     * @param  string  $path
     * @return string
     */
    public function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path.
     *
     * 从文件路径中提取父目录
     *
     * @param  string  $path
     * @return string
     */
    public function dirname($path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Extract the file extension from a file path.
     *
     * 从文件路径中提取文件扩展名
     *
     * @param  string  $path
     * @return string
     */
    public function extension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the file type of a given file.
     *
     * 获取给定文件的文件类型
     *
     * @param  string  $path
     * @return string
     */
    public function type($path)
    {
        return filetype($path);
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
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
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
        return filesize($path);
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
        return filemtime($path);
    }

    /**
     * Determine if the given path is a directory.
     *
     * 确定给定路径是否为目录
     *
     * @param  string  $directory
     * @return bool
     */
    public function isDirectory($directory)
    {
        return is_dir($directory);
    }

    /**
     * Determine if the given path is readable.
     *
     * 确定给定的路径是否可读
     *
     * @param  string  $path
     * @return bool
     */
    public function isReadable($path)
    {
        return is_readable($path);
    }

    /**
     * Determine if the given path is writable.
     *
     * 确定给定路径是否可写
     *
     * @param  string  $path
     * @return bool
     */
    public function isWritable($path)
    {
        return is_writable($path);
    }

    /**
     * Determine if the given path is a file.
	 *
	 * 确定给定路径是否为文件
     *
     * @param  string  $file
     * @return bool
     */
    public function isFile($file)
    {
        return is_file($file);
    }

    /**
     * Find path names matching a given pattern.
     *
     * 找到匹配给定模式的路径名
     *
     * @param  string  $pattern
     * @param  int     $flags
     * @return array
     */
    public function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * Get an array of all files in a directory.
     *
     * 在一个目录中获取所有文件的数组
     *
     * @param  string  $directory
     * @return array
     */
    public function files($directory)
    {
        $glob = glob($directory.'/*');

        if ($glob === false) {
            return [];
        }

        // To get the appropriate files, we'll simply glob the directory and filter
        // out any "files" that are not truly files so we do not end up with any
        // directories in our list, but only true files within the directory.
        //
        // 得到适当的文件,我们将简单地水珠的目录和过滤掉任何“文件”,并不是真正的文件我们没有得到任何目录列表,但只有真正的文件目录中
        //
        return array_filter($glob, function ($file) {
            return filetype($file) == 'file';
        });
    }

    /**
     * Get all of the files from the given directory (recursive).
     *
     * 从给定目录中获取所有文件(递归)
     *
     * @param  string  $directory
     * @param  bool  $hidden
     * @return array
     */
    public function allFiles($directory, $hidden = false)
    {
        //                   创建一个新的查找器->仅将匹配限制为文件->不包括“隐藏”的目录和文件(从一个点开始)->搜索符合定义规则的文件和目录
        return iterator_to_array(Finder::create()->files()->ignoreDotFiles(! $hidden)->in($directory), false);
    }

    /**
     * Get all of the directories within a given directory.
     *
     * 在给定目录中获取所有目录
     *
     * @param  string  $directory
     * @return array
     */
    public function directories($directory)
    {
        $directories = [];
        //                   创建一个新的查找器->搜索符合定义规则的文件和目录->只限制与目录的匹配->为目录深度添加测试
        foreach (Finder::create()->in($directory)->directories()->depth(0) as $dir) {
            $directories[] = $dir->getPathname();
        }

        return $directories;
    }

    /**
     * Create a directory.
     *
     * 创建一个目录
     *
     * @param  string  $path
     * @param  int     $mode
     * @param  bool    $recursive
     * @param  bool    $force
     * @return bool
     */
    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Move a directory.
     *
     * 移动一个目录
     *
     * @param  string  $from
     * @param  string  $to
     * @param  bool  $overwrite
     * @return bool
     */
    public function moveDirectory($from, $to, $overwrite = false)
    {
        //                     确定给定路径是否为目录
        if ($overwrite && $this->isDirectory($to)) {
            if (! $this->deleteDirectory($to)) {//递归删除目录
                return false;
            }
        }

        return @rename($from, $to) === true;
    }

    /**
     * Copy a directory from one location to another.
     *
     * 将目录从一个位置复制到另一个位置
     *
     * @param  string  $directory
     * @param  string  $destination
     * @param  int     $options
     * @return bool
     */
    public function copyDirectory($directory, $destination, $options = null)
    {
        //确定给定路径是否为目录
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $options = $options ?: FilesystemIterator::SKIP_DOTS;

        // If the destination directory does not actually exist, we will go ahead and
        // create it recursively, which just gets the destination prepared to copy
        // the files over. Once we make the directory we'll proceed the copying.
        //
        // 如果目标目录实际上不存在，我们将继续递归地创建它，它只会让目的地准备好复制文件
        // 一旦我们创建了目录，我们将继续进行复制
        //
        //         确定给定路径是否为目录
        if (! $this->isDirectory($destination)) {
            //创建一个目录
            $this->makeDirectory($destination, 0777, true);
        }

        $items = new FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            // As we spin through items, we will check to see if the current file is actually
            // a directory or a file. When it is actually a directory we will need to call
            // back into this function recursively to keep copying these nested folders.
            //
            // 当我们旋转项目时，我们将检查当前文件是否实际是一个目录或一个文件
            // 当它实际上是一个目录时，我们需要递归地调用这个函数，以保持复制这些嵌套的文件夹
            //
            $target = $destination.'/'.$item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();
                //将目录从一个位置复制到另一个位置
                if (! $this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            }

            // If the current items is just a regular file, we will just copy this to the new
            // location and keep looping. If for some reason the copy fails we'll bail out
            // and return false, so the developer is aware that the copy process failed.
            //
            // 如果当前的项目只是一个常规文件，我们将把它复制到新的位置并保持循环
            // 如果由于某种原因，复制失败了，我们将会退出并返回false，因此开发人员知道复制过程失败了
            //
            else {
                //    将文件复制到新位置
                if (! $this->copy($item->getPathname(), $target)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Recursively delete a directory.
     *
     * 递归删除目录
     *
     * The directory itself may be optionally preserved.
     *
     * 目录本身可以被保留
     *
     * @param  string  $directory
     * @param  bool    $preserve
     * @return bool
     */
    public function deleteDirectory($directory, $preserve = false)
    {
        //       确定给定路径是否为目录
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-directory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            //
            // 如果条目是一个目录，我们就可以递归到函数中，然后删除子目录，否则我们只会删除文件，并不断地遍历每个文件，直到目录被清除
            //
            if ($item->isDir() && ! $item->isLink()) {
                //   递归删除目录
                $this->deleteDirectory($item->getPathname());
            }

            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and calling directories recursively, so we delete the real path.
            //
            // 如果项目只是一个文件,我们可以继续,删除它因为我们只是遍历和打蜡的所有文件在这个目录递归地调用目录,我们删除的路径
            //
            else {
                //在给定的路径中删除该文件
                $this->delete($item->getPathname());
            }
        }

        if (! $preserve) {
            @rmdir($directory);
        }

        return true;
    }

    /**
     * Empty the specified directory of all files and folders.
     *
     * 清空所有文件和文件夹的指定目录
     *
     * @param  string  $directory
     * @return bool
     */
    public function cleanDirectory($directory)
    {
        //递归删除目录
        return $this->deleteDirectory($directory, true);
    }
}
