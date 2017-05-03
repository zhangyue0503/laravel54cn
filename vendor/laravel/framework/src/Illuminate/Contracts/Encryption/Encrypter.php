<?php

namespace Illuminate\Contracts\Encryption;

interface Encrypter
{
    /**
     * Encrypt the given value.
     *
     * @param  string  $value
     * @param  bool  $serialize
     * @return string
     */
    public function encrypt($value, $serialize = true);

    /**
     * Decrypt the given value.
     *
     * 对给定值进行解密
     *
     * @param  string  $payload
     * @param  bool  $unserialize
     * @return string
     */
    public function decrypt($payload, $unserialize = true);
}
