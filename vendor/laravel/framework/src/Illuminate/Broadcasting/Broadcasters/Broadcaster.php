<?php

namespace Illuminate\Broadcasting\Broadcasters;

use ReflectionFunction;
use ReflectionParameter;
use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Routing\BindingRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;

abstract class Broadcaster implements BroadcasterContract
{
    /**
     * The registered channel authenticators.
     *
     * 注册的通道验证器
     *
     * @var array
     */
    protected $channels = [];

    /**
     * The binding registrar instance.
     *
     * 绑定注册器实例
     *
     * @var BindingRegistrar
     */
    protected $bindingRegistrar;

    /**
     * Register a channel authenticator.
     *
     * 注册通道身份验证器
     *
     * @param  string  $channel
     * @param  callable  $callback
     * @return $this
     */
    public function channel($channel, callable $callback)
    {
        $this->channels[$channel] = $callback;

        return $this;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * 对给定通道的传入请求进行身份验证
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $channel
     * @return mixed
     */
    protected function verifyUserCanAccessChannel($request, $channel)
    {
        foreach ($this->channels as $pattern => $callback) {
            //确定给定的字符串是否与给定的模式匹配
            if (! Str::is(preg_replace('/\{(.*?)\}/', '*', $pattern), $channel)) {
                continue;
            }
            //从给定的模式和通道中提取参数
            $parameters = $this->extractAuthParameters($pattern, $channel, $callback);
            //                               获取用户请求
            if ($result = $callback($request->user(), ...$parameters)) {
                //返回有效的身份验证响应
                return $this->validAuthenticationResponse($request, $result);
            }
        }

        throw new HttpException(403);
    }

    /**
     * Extract the parameters from the given pattern and channel.
     *
     * 从给定的模式和通道中提取参数
     *
     * @param  string  $pattern
     * @param  string  $channel
     * @param  callable  $callback
     * @return array
     */
    protected function extractAuthParameters($pattern, $channel, $callback)
    {
        $callbackParameters = (new ReflectionFunction($callback))->getParameters();
        //            从传入的通道名称中提取通道键                           创建不通过给定的真值测试的所有元素的集合
        return collect($this->extractChannelKeys($pattern, $channel))->reject(function ($value, $key) {
            return is_numeric($key);
            //在每个项目上运行map
        })->map(function ($value, $key) use ($callbackParameters) {
            //             解析给定的参数绑定
            return $this->resolveBinding($key, $value, $callbackParameters);
        })->values()->all();//重置基础阵列上的键->获取集合中的所有项目
    }

    /**
     * Extract the channel keys from the incoming channel name.
     *
     * 从传入的通道名称中提取通道键
     *
     * @param  string  $pattern
     * @param  string  $channel
     * @return array
     */
    protected function extractChannelKeys($pattern, $channel)
    {
        preg_match('/^'.preg_replace('/\{(.*?)\}/', '(?<$1>[^\.]+)', $pattern).'/', $channel, $keys);

        return $keys;
    }

    /**
     * Resolve the given parameter binding.
     *
     * 解析给定的参数绑定
     *
     * @param  string  $key
     * @param  string  $value
     * @param  array  $callbackParameters
     * @return mixed
     */
    protected function resolveBinding($key, $value, $callbackParameters)
    {
        //               如果适用，解析一个显式的参数绑定
        $newValue = $this->resolveExplicitBindingIfPossible($key, $value);
        //                                     如果适用，可以解析一个隐式参数绑定
        return $newValue === $value ? $this->resolveImplicitBindingIfPossible(
            $key, $value, $callbackParameters
        ) : $newValue;
    }

    /**
     * Resolve an explicit parameter binding if applicable.
     *
     * 如果适用，解析一个显式的参数绑定
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function resolveExplicitBindingIfPossible($key, $value)
    {
        // 获取模型绑定注册实例实例
        $binder = $this->binder();
        //                    为给定的绑定获取绑定回调
        if ($binder && $binder->getBindingCallback($key)) {
            //                            为给定的绑定获取绑定回调
            return call_user_func($binder->getBindingCallback($key), $value);
        }

        return $value;
    }

    /**
     * Resolve an implicit parameter binding if applicable.
     *
     * 如果适用，可以解析一个隐式参数绑定
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $callbackParameters
     * @return mixed
     */
    protected function resolveImplicitBindingIfPossible($key, $value, $callbackParameters)
    {
        foreach ($callbackParameters as $parameter) {
            //确定给定的键和参数是否隐式可绑定
            if (! $this->isImplicitlyBindable($key, $parameter)) {
                continue;
            }

            $model = $parameter->getClass()->newInstance();

            return $model->where($model->getRouteKeyName(), $value)->firstOr(function () {
                throw new HttpException(403);
            });
        }

        return $value;
    }

    /**
     * Determine if a given key and parameter is implicitly bindable.
     *
     * 确定给定的键和参数是否隐式可绑定
     *
     * @param  string  $key
     * @param  ReflectionParameter  $parameter
     * @return bool
     */
    protected function isImplicitlyBindable($key, $parameter)
    {
        return $parameter->name === $key && $parameter->getClass() &&
                $parameter->getClass()->isSubclassOf(Model::class);
    }

    /**
     * Format the channel array into an array of strings.
     *
     * 将通道数组格式化为字符串数组
     *
     * @param  array  $channels
     * @return array
     */
    protected function formatChannels(array $channels)
    {
        return array_map(function ($channel) {
            return (string) $channel;
        }, $channels);
    }

    /**
     * Get the model binding registrar instance.
     *
     * 获取模型绑定注册实例实例
     *
     * @return BindingRegistrar
     */
    protected function binder()
    {
        if (! $this->bindingRegistrar) {
            //                                 设置容器的全局可用实例->确定给定的抽象类型是否已绑定
            $this->bindingRegistrar = Container::getInstance()->bound(BindingRegistrar::class)
                //设置容器的全局可用实例->从容器中解析给定类型
                        ? Container::getInstance()->make(BindingRegistrar::class) : null;
        }

        return $this->bindingRegistrar;
    }
}
