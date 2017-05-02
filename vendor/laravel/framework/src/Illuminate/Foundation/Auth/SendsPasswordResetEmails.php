<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

trait SendsPasswordResetEmails
{
    /**
     * Display the form to request a password reset link.
     *
     * 显示表单以请求密码重置链接
     *
     * @return \Illuminate\Http\Response
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send a reset link to the given user.
     *
     * 发送一个重置链接给指定的用户
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        //使用给定的规则验证给定的请求
        $this->validate($request, ['email' => 'required|email']);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        //
        // 我们将把密码重置链接发送给这个用户
        // 一旦我们尝试发送链接，我们将检查响应，然后查看我们需要向用户显示的消息
        // 最后，我们会发出适当的回应
        //
        //        让代理在密码重置期间被使用   将密码重置链接发送给用户
        $response = $this->broker()->sendResetLink(
            $request->only('email')//从输入数据中获取包含所提供的键的子集
        );

        return $response == Password::RESET_LINK_SENT
                    ? $this->sendResetLinkResponse($response)//获得成功的密码重置链接的响应
                    : $this->sendResetLinkFailedResponse($request, $response);//获取失败密码重置链接的响应
    }

    /**
     * Get the response for a successful password reset link.
     *
     * 获得成功的密码重置链接的响应
     *
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendResetLinkResponse($response)
    {
        //创建一个新的重定向响应到以前的位置->把数据闪存到会话中
        return back()->with('status', trans($response));
    }

    /**
     * Get the response for a failed password reset link.
     *
     * 获取失败密码重置链接的响应
     *
     * @param  \Illuminate\Http\Request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        //创建一个新的重定向响应到以前的位置->将错误的容器闪存到会话中
        return back()->withErrors(
            //          翻译给定的信息
            ['email' => trans($response)]
        );
    }

    /**
     * Get the broker to be used during password reset.
     *
     * 让代理在密码重置期间被使用
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();//按名称获取密码代理实例
    }
}
