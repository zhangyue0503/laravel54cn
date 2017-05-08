<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Redis\Factory as Redis;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RedisBroadcaster extends Broadcaster
{
    /**
     * The Redis instance.
     *
     * Redis实例
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * The Redis connection to use for broadcasting.
     *
     * 用于广播的Redis连接
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new broadcaster instance.
     *
     * 创建一个新的广播实例
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @param  string  $connection
     * @return void
     */
    public function __construct(Redis $redis, $connection = null)
    {
        $this->redis = $redis;
        $this->connection = $connection;
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
        if (is_bool($result)) {
            return json_encode($result);
        }

        return json_encode(['channel_data' => [
            //                获取用户请求
            'user_id' => $request->user()->getKey(),
            'user_info' => $result,
        ]]);
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
        //                           通过名称获得一个Redis连接
        $connection = $this->redis->connection($this->connection);

        $payload = json_encode([
            'event' => $event,
            'data' => $payload,
            'socket' => Arr::pull($payload, 'socket'),//从数组中获取值，并将其移除
        ]);
        //将通道数组格式化为字符串数组
        foreach ($this->formatChannels($channels) as $channel) {
            $connection->publish($channel, $payload);
        }
    }
}
