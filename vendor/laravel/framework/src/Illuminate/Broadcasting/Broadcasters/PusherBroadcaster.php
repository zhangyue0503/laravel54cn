<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Pusher;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Broadcasting\BroadcastException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PusherBroadcaster extends Broadcaster
{
    /**
     * The Pusher SDK instance.
     *
     * 推送SDK实例
     *
     * @var \Pusher
     */
    protected $pusher;

    /**
     * Create a new broadcaster instance.
     *
     * 创建一个新的广播实例
     *
     * @param  \Pusher  $pusher
     * @return void
     */
    public function __construct(Pusher $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * 对给定通道的传入请求进行身份验证
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function auth($request)
    {
        //确定给定的子字符串是否属于给定的字符串
        if (Str::startsWith($request->channel_name, ['private-', 'presence-']) &&
            ! $request->user()) {//获取用户请求
            throw new HttpException(403);
        }
        //确定给定的子字符串是否属于给定的字符串
        $channelName = Str::startsWith($request->channel_name, 'private-')
                            ? Str::replaceFirst('private-', '', $request->channel_name)//替换字符串中第一次出现的给定值
                            : Str::replaceFirst('presence-', '', $request->channel_name);//替换字符串中第一次出现的给定值
        //对给定通道的传入请求进行身份验证
        return parent::verifyUserCanAccessChannel(
            $request, $channelName
        );
    }

    /**
     * Return the valid authentication response.
     *
     * 返回有效的身份验证响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        //确定给定的子字符串是否属于给定的字符串
        if (Str::startsWith($request->channel_name, 'private')) {
            //解码给定的Pusher响应
            return $this->decodePusherResponse(
                $this->pusher->socket_auth($request->channel_name, $request->socket_id)
            );
        } else {
            //解码给定的Pusher响应
            return $this->decodePusherResponse(
                $this->pusher->presence_auth(
                    //                                              获取用户请求
                    $request->channel_name, $request->socket_id, $request->user()->getKey(), $result)
            );
        }
    }

    /**
     * Decode the given Pusher response.
     *
     * 解码给定的Pusher响应
     *
     * @param  mixed  $response
     * @return array
     */
    protected function decodePusherResponse($response)
    {
        return json_decode($response, true);
    }

    /**
     * Broadcast the given event.
     *
     * 广播给定事件
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        //从数组中获取值，并将其移除
        $socket = Arr::pull($payload, 'socket');

        $response = $this->pusher->trigger(
            //将通道数组格式化为字符串数组
            $this->formatChannels($channels), $event, $payload, $socket, true
        );

        if ((is_array($response) && $response['status'] >= 200 && $response['status'] <= 299)
            || $response === true) {
            return;
        }

        throw new BroadcastException(
            is_bool($response) ? 'Failed to connect to Pusher.' : $response['body']
        );
    }

    /**
     * Get the Pusher SDK instance.
     *
     * 获得Pusher SDK实例
     *
     * @return \Pusher
     */
    public function getPusher()
    {
        return $this->pusher;
    }
}
