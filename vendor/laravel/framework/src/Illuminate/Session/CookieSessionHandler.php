<?php

namespace Illuminate\Session;

use Carbon\Carbon;
use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;

class CookieSessionHandler implements SessionHandlerInterface
{
    /**
     * The cookie jar instance.
     *
     * cookie jar实例
     *
     * @var \Illuminate\Contracts\Cookie\Factory
     */
    protected $cookie;

    /**
     * The request instance.
     *
     * 请求实例
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * The number of minutes the session should be valid.
     *
     * 在缓存中存储数据的分钟数
     *
     * @var int
     */
    protected $minutes;

    /**
     * Create a new cookie driven handler instance.
     *
     * 创建一个新的cookie驱动的处理程序实例
     *
     * @param  \Illuminate\Contracts\Cookie\QueueingFactory  $cookie
     * @param  int  $minutes
     * @return void
     */
    public function __construct(CookieJar $cookie, $minutes)
    {
        $this->cookie = $cookie;
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
        //                            返回参数通过key名
        $value = $this->request->cookies->get($sessionId) ?: '';

        if (! is_null($decoded = json_decode($value, true)) && is_array($decoded)) {
            //                              获取当前日期和时间的Carbon实例
            if (isset($decoded['expires']) && Carbon::now()->getTimestamp() <= $decoded['expires']) {
                return $decoded['data'];
            }
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        //          用下一个响应发送一个cookie来发送
        $this->cookie->queue($sessionId, json_encode([
            'data' => $data,
            //获取当前日期和时间的Carbon实例  添加几分钟到实例
            'expires' => Carbon::now()->addMinutes($this->minutes)->getTimestamp(),
        ]), $this->minutes);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        //      用下一个响应发送一个cookie来发送    过期的cookie
        $this->cookie->queue($this->cookie->forget($sessionId));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        return true;
    }

    /**
     * Set the request instance.
     *
     * 设置请求实例
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
}
