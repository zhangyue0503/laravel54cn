<?php

namespace Illuminate\Contracts\Hashing;

interface Hasher
{
    /**
     * Hash the given value.
     *
     * 哈希给定的值
     *
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function make($value, array $options = []);

    /**
     * Check the given plain value against a hash.
     *
     * 检查给定的普通值与散列值
     *
     * @param  string  $value
     * @param  string  $hashedValue
     * @param  array   $options
     * @return bool
     */
    public function check($value, $hashedValue, array $options = []);

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param  string  $hashedValue
     * @param  array   $options
     * @return bool
     */
    public function needsRehash($hashedValue, array $options = []);
}
