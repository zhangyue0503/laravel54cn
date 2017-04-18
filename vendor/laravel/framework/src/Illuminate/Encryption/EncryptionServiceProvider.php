<?php

namespace Illuminate\Encryption;

use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        //在容器中注册共享绑定
        $this->app->singleton('encrypter', function ($app) {
            $config = $app->make('config')->get('app'); //获取app的配置

            // If the key starts with "base64:", we will need to decode the key before handing
            // it off to the encrypter. Keys may be base-64 encoded for presentation and we
            // want to make sure to convert them back to the raw bytes before encrypting.
            //
            // 如果键启动“base64：”我们需要解码密钥之前提交到加密
            // 密钥可base-64编码提示我们要确保将它们转换回原始字节加密之前
            //
            if (Str::startsWith($key = $config['key'], 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }
            //返回加解密对象
            return new Encrypter($key, $config['cipher']);
        });
    }
}
