<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;

trait RegistersUsers
{
    use RedirectsUsers;

    /**
     * Show the application registration form.
     *
     * 显示应用程序注册表
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm()
    {
        //获取给定视图的得到视图内容
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
	 *
	 * 办理申请注册申请
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        //得到一个接收注册请求验证器(获取请求的所有输入和文件)->根据所提供的规则验证给定的数据
        $this->validator($request->all())->validate();
        //                                  在有效注册之后创建一个新的用户实例(获取请求的所有输入和文件)
        event(new Registered($user = $this->create($request->all())));
        //在注册时使用该保护->将用户登录到应用程序中
        $this->guard()->login($user);
        //        用户已经注册了?:得到重定向器的实例(获取post注册/登录重定向路径)
        return $this->registered($request, $user)
                        ?: redirect($this->redirectPath());
    }

    /**
     * Get the guard to be used during registration.
     *
     * 在注册时使用该保护
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();//试图从本地缓存中得到守卫
    }

    /**
     * The user has been registered.
     *
     * 用户已经注册了
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        //
    }
}
