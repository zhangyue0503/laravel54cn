<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * BinaryFileResponse represents an HTTP response delivering a file.
 *
 * BinaryFileResponse表示传递文件的HTTP响应
 *
 * @author Niklas Fiekas <niklas.fiekas@tu-clausthal.de>
 * @author stealth35 <stealth35-php@live.fr>
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Jordan Alliot <jordan.alliot@gmail.com>
 * @author Sergey Linnik <linniksa@gmail.com>
 */
class BinaryFileResponse extends Response
{
    protected static $trustXSendfileTypeHeader = false;

    /**
     * @var File
     */
    protected $file;
    protected $offset;
    protected $maxlen;
    protected $deleteFileAfterSend = false;

    /**
     * Constructor.
     *
     * 构造函数
     *
     * @param \SplFileInfo|string $file               The file to stream   文件流
     * @param int                 $status             The response status code 响应码
     * @param array               $headers            An array of response headers 响应头数组
     * @param bool                $public             Files are public by default 文件默认为公共
     * @param null|string         $contentDisposition The type of Content-Disposition to set automatically with the filename 以文件名自动设置内容配置的类型
     * @param bool                $autoEtag           Whether the ETag header should be automatically set 是否应自动设置ETag头
     * @param bool                $autoLastModified   Whether the Last-Modified header should be automatically set 是否应自动设置上次修改的头
     */
    public function __construct($file, $status = 200, $headers = array(), $public = true, $contentDisposition = null, $autoEtag = false, $autoLastModified = true)
    {
        parent::__construct(null, $status, $headers);

        $this->setFile($file, $contentDisposition, $autoEtag, $autoLastModified); //将文件设置为流

        if ($public) {
            $this->setPublic(); //标记响应为“public”
        }
    }

    /**
     *
     * 静态绑定构造BinaryFileResponse类
     *
     * @param \SplFileInfo|string $file               The file to stream
     * @param int                 $status             The response status code
     * @param array               $headers            An array of response headers
     * @param bool                $public             Files are public by default
     * @param null|string         $contentDisposition The type of Content-Disposition to set automatically with the filename
     * @param bool                $autoEtag           Whether the ETag header should be automatically set
     * @param bool                $autoLastModified   Whether the Last-Modified header should be automatically set
     *
     * @return static
     */
    public static function create($file = null, $status = 200, $headers = array(), $public = true, $contentDisposition = null, $autoEtag = false, $autoLastModified = true)
    {
        return new static($file, $status, $headers, $public, $contentDisposition, $autoEtag, $autoLastModified);
    }

    /**
     * Sets the file to stream.
     *
     * 将文件设置为流
     *
     * @param \SplFileInfo|string $file               The file to stream
     * @param string              $contentDisposition
     * @param bool                $autoEtag
     * @param bool                $autoLastModified
     *
     * @return $this
     *
     * @throws FileException
     */
    public function setFile($file, $contentDisposition = null, $autoEtag = false, $autoLastModified = true)
    {
        if (!$file instanceof File) {
            if ($file instanceof \SplFileInfo) {
                $file = new File($file->getPathname());
            } else {
                $file = new File((string) $file);
            }
        }

        if (!$file->isReadable()) {
            throw new FileException('File must be readable.');
        }

        $this->file = $file;

        if ($autoEtag) {
            $this->setAutoEtag(); //自动设置ETag头根据文件的校验
        }

        if ($autoLastModified) {
            $this->setAutoLastModified(); //根据文件修改日期自动设置最后修改标头
        }

        if ($contentDisposition) {
            $this->setContentDisposition($contentDisposition); //在内容配置集的标题与给定的文件名
        }

        return $this;
    }

    /**
     * Gets the file.
     *
     * 获取文件
     *
     * @return File The file to stream
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Automatically sets the Last-Modified header according the file modification date.
     * 根据文件修改日期自动设置最后修改标头
     */
    public function setAutoLastModified()
    {
        $this->setLastModified(\DateTime::createFromFormat('U', $this->file->getMTime()));

        return $this;
    }

    /**
     * Automatically sets the ETag header according to the checksum of the file.
     * 自动设置ETag头根据文件的校验
     */
    public function setAutoEtag()
    {
        $this->setEtag(sha1_file($this->file->getPathname()));

        return $this;
    }

    /**
     * Sets the Content-Disposition header with the given filename.
     *
     * 在内容配置集的标题与给定的文件名
     *
     * @param string $disposition      ResponseHeaderBag::DISPOSITION_INLINE or ResponseHeaderBag::DISPOSITION_ATTACHMENT
     * @param string $filename         Optionally use this filename instead of the real name of the file
     * @param string $filenameFallback A fallback filename, containing only ASCII characters. Defaults to an automatically encoded filename
     *
     * @return $this
     */
    public function setContentDisposition($disposition, $filename = '', $filenameFallback = '')
    {
        if ($filename === '') {
            $filename = $this->file->getFilename();
        }

        if ('' === $filenameFallback && (!preg_match('/^[\x20-\x7e]*$/', $filename) || false !== strpos($filename, '%'))) {
            $encoding = mb_detect_encoding($filename, null, true);

            for ($i = 0, $filenameLength = mb_strlen($filename, $encoding); $i < $filenameLength; ++$i) {
                $char = mb_substr($filename, $i, 1, $encoding);

                if ('%' === $char || ord($char) < 32 || ord($char) > 126) {
                    $filenameFallback .= '_';
                } else {
                    $filenameFallback .= $char;
                }
            }
        }

        $dispositionHeader = $this->headers->makeDisposition($disposition, $filename, $filenameFallback); //生成HTTP内容配置字段值
        $this->headers->set('Content-Disposition', $dispositionHeader); //根据名称设置头

        return $this;
    }

    /**
     * {@inheritdoc}
     * 在发送给客户端之前准备响应
     */
    public function prepare(Request $request)
    {
        $this->headers->set('Content-Length', $this->file->getSize());

        if (!$this->headers->has('Accept-Ranges')) {
            // Only accept ranges on safe HTTP methods  只接受安全HTTP方法的范围
            $this->headers->set('Accept-Ranges', $request->isMethodSafe(false) ? 'bytes' : 'none');
        }

        if (!$this->headers->has('Content-Type')) {
            //                                     返回文件的mime类型
            $this->headers->set('Content-Type', $this->file->getMimeType() ?: 'application/octet-stream');
        }

        if ('HTTP/1.0' !== $request->server->get('SERVER_PROTOCOL')) {
            $this->setProtocolVersion('1.1'); //设置HTTP协议版本
        }

        $this->ensureIEOverSSLCompatibility($request); //检查是否需要删除缓存控制的SSL加密下载时使用IE 9

        $this->offset = 0;
        $this->maxlen = -1;

        if (self::$trustXSendfileTypeHeader && $request->headers->has('X-Sendfile-Type')) {
            // Use X-Sendfile, do not send any content. 使用X-Sendfile，不发送任何内容
            $type = $request->headers->get('X-Sendfile-Type');
            $path = $this->file->getRealPath();
            // Fall back to scheme://path for stream wrapped locations.
            if (false === $path) {
                $path = $this->file->getPathname();
            }
            if (strtolower($type) === 'x-accel-redirect') {
                // Do X-Accel-Mapping substitutions. 做X-Accel-Mapping替换
                // @link http://wiki.nginx.org/X-accel#X-Accel-Redirect
                foreach (explode(',', $request->headers->get('X-Accel-Mapping', '')) as $mapping) {
                    $mapping = explode('=', $mapping, 2);

                    if (2 === count($mapping)) {
                        $pathPrefix = trim($mapping[0]);
                        $location = trim($mapping[1]);

                        if (substr($path, 0, strlen($pathPrefix)) === $pathPrefix) {
                            $path = $location.substr($path, strlen($pathPrefix));
                            break;
                        }
                    }
                }
            }
            $this->headers->set($type, $path);
            $this->maxlen = 0;
        } elseif ($request->headers->has('Range')) { //断点续传
            // Process the range headers. 处理范围头
            //                                           如果具有有效的范围标头
            if (!$request->headers->has('If-Range') || $this->hasValidIfRangeHeader($request->headers->get('If-Range'))) {
                $range = $request->headers->get('Range');
                $fileSize = $this->file->getSize();

                list($start, $end) = explode('-', substr($range, 6), 2) + array(0);

                $end = ('' === $end) ? $fileSize - 1 : (int) $end;

                if ('' === $start) {
                    $start = $fileSize - $end;
                    $end = $fileSize - 1;
                } else {
                    $start = (int) $start;
                }

                if ($start <= $end) {
                    if ($start < 0 || $end > $fileSize - 1) {
                        $this->setStatusCode(416); //设置响应状态代码
                        $this->headers->set('Content-Range', sprintf('bytes */%s', $fileSize));
                    } elseif ($start !== 0 || $end !== $fileSize - 1) {
                        $this->maxlen = $end < $fileSize ? $end - $start + 1 : -1;
                        $this->offset = $start;

                        $this->setStatusCode(206); //设置响应状态代码
                        $this->headers->set('Content-Range', sprintf('bytes %s-%s/%s', $start, $end, $fileSize));
                        $this->headers->set('Content-Length', $end - $start + 1);
                    }
                }
            }
        }

        return $this;
    }
    //如果具有有效的范围标头
    private function hasValidIfRangeHeader($header)
    {
        if ($this->getEtag() === $header) {  //返回ETag HTTP标头的文本值
            return true;
        }

        if (null === $lastModified = $this->getLastModified()) {  //用一个DateTime实例返回的Last-Modified HTTP头
            return false;
        }

        return $lastModified->format('D, d M Y H:i:s').' GMT' === $header;
    }

    /**
     * Sends the file.
     *
     * 发送文件
     *
     * {@inheritdoc}
     */
    public function sendContent()
    {
        if (!$this->isSuccessful()) { //响应成功？
            return parent::sendContent(); //发送当前web响应的内容
        }

        if (0 === $this->maxlen) {
            return $this;
        }

        $out = fopen('php://output', 'wb');
        $file = fopen($this->file->getPathname(), 'rb');

        stream_copy_to_stream($file, $out, $this->maxlen, $this->offset);

        fclose($out);
        fclose($file);

        if ($this->deleteFileAfterSend) {
            unlink($this->file->getPathname());
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * 设置响应的内容
     *
     * @throws \LogicException when the content is not null
     */
    public function setContent($content)
    {
        if (null !== $content) {
            throw new \LogicException('The content cannot be set on a BinaryFileResponse instance.');
        }
    }

    /**
     * {@inheritdoc}
     *
     * 获取响应的内容
     *
     * @return false
     */
    public function getContent()
    {
        return false;
    }

    /**
     * Trust X-Sendfile-Type header.
     * 信任X-Sendfile-Type头
     */
    public static function trustXSendfileTypeHeader()
    {
        self::$trustXSendfileTypeHeader = true;
    }

    /**
     * If this is set to true, the file will be unlinked after the request is send
     *
     * 如果设置为true，该文件将被链接的请求后发送
     *
     * Note: If the X-Sendfile header is used, the deleteFileAfterSend setting will not be used.
     *
     * 注意：如果X-Sendfile头使用的deleteFileAfterSend设置将不能使用
     *
     * @param bool $shouldDelete
     *
     * @return $this
     */
    public function deleteFileAfterSend($shouldDelete)
    {
        $this->deleteFileAfterSend = $shouldDelete;

        return $this;
    }
}
