<?php

namespace Illuminate\Contracts\Auth;

interface CanResetPassword
{
    /**
     * Get the e-mail address where password reset links are sent.
     *
     * 获取密码重置链接的电子邮件地址
     *
     * @return string
     */
    public function getEmailForPasswordReset();

    /**
     * Send the password reset notification.
     *
     * 发送密码重置通知
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token);
}
