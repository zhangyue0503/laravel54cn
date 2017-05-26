<?php

namespace Illuminate\Session;

use Carbon\Carbon;
use SessionHandlerInterface;
use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;

class FileSessionHandler implements SessionHandlerInterface
{
    /**
     * The filesystem instance.
     *
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The path where sessions should be stored.
     *
     * 应该存储会话的路径
     *
     * @var string
     */
    protected $path;

    /**
     * The number of minutes the session should be valid.
     *
     * 会话的分钟数应该是有效的
     *
     * @var int
     */
    protected $minutes;

    /**
     * Create a new file driven handler instance.
     *
     * 创建一个新的文件驱动处理程序实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $path
     * @param  int  $minutes
     * @return void
     */
    public function __construct(Filesystem $files, $path, $minutes)
    {
        $this->path = $path;
        $this->files = $files;
        $this->minutes = $minutes;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        //确定文件或目录是否存在
        if ($this->files->exists($path = $this->path.'/'.$sessionId)) {
            //                        获取当前日期和时间的Carbon实例 从实例中删除几分钟
            if (filemtime($path) >= Carbon::now()->subMinutes($this->minutes)->getTimestamp()) {
                return $this->files->get($path, true);//获取文件的内容
            }
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        //写入文件的内容
        $this->files->put($this->path.'/'.$sessionId, $data, true);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        //在给定的路径中删除该文件
        $this->files->delete($this->path.'/'.$sessionId);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        $files = Finder::create()//创建一个新的查找器
                    ->in($this->path)//搜索符合定义规则的文件和目录
                    ->files()//仅将匹配限制为文件
                    ->ignoreDotFiles(true)//不包括“隐藏”的目录和文件(从一个点开始)
                    ->date('<= now - '.$lifetime.' seconds');//为文件日期添加测试(最后修改)

        foreach ($files as $file) {
            //在给定的路径中删除该文件
            $this->files->delete($file->getRealPath());
        }
    }
}
