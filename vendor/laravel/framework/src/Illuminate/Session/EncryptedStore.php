<?php

namespace Illuminate\Session;

use SessionHandlerInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class EncryptedStore extends Store
{
    /**
     * The encrypter instance.
     *
     * 加密实例
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Create a new session instance.
     *
     * 创建一个新的会话实例
     *
     * @param  string $name
     * @param  \SessionHandlerInterface $handler
     * @param  \Illuminate\Contracts\Encryption\Encrypter $encrypter
     * @param  string|null $id
     * @return void
     */
    public function __construct($name, SessionHandlerInterface $handler, EncrypterContract $encrypter, $id = null)
    {
        $this->encrypter = $encrypter;
        //创建一个新的会话实例
        parent::__construct($name, $handler, $id);
    }

    /**
     * Prepare the raw string data from the session for unserialization.
     *
     * 从会话中准备未序列化的原始字符串数据
     *
     * @param  string  $data
     * @return string
     */
    protected function prepareForUnserialize($data)
    {
        try {
            //                        对给定值进行解密
            return $this->encrypter->decrypt($data);
        } catch (DecryptException $e) {
            return serialize([]);
        }
    }

    /**
     * Prepare the serialized session data for storage.
     *
     * 准备用于存储的序列化会话数据
     *
     * @param  string  $data
     * @return string
     */
    protected function prepareForStorage($data)
    {
        //                    对给定值进行加密
        return $this->encrypter->encrypt($data);
    }

    /**
     * Get the encrypter instance.
     *
     * 得到加密实例
     *
     * @return \Illuminate\Contracts\Encryption\Encrypter
     */
    public function getEncrypter()
    {
        return $this->encrypter;
    }
}
