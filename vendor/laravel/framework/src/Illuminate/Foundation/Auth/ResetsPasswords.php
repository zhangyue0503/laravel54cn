<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

trait ResetsPasswords
{
    use RedirectsUsers;

    /**
     * Display the password reset view for the given token.
     *
     * 显示给定令牌的密码重置视图
     *
     * If no token is present, display the link request form.
     *
     * 如果没有标记，则显示链接请求表单
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(Request $request, $token = null)
    {
        //  获取给定视图的得到视图内容->将数据添加到视图中
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Reset the given user's password.
     *
     * 重置给定用户的密码
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request)
    {
        //使用给定的规则验证给定的请求(,获取密码重置验证规则,获取密码重置验证错误消息)
        $this->validate($request, $this->rules(), $this->validationErrorMessages());

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        //
        // 在这里，我们将尝试重置用户的密码
        // 如果成功，我们将更新实际用户模型的密码，并将其持久化到数据库中
        // 否则，我们将解析错误并返回响应
        //
        // 让代理在密码重置期间被使用->重置给定令牌的密码
        $response = $this->broker()->reset(
            //从请求中获取密码重置凭证
            $this->credentials($request), function ($user, $password) {
                //重置给定用户的密码
                $this->resetPassword($user, $password);
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        //
        // 如果成功重置密码，我们将把用户重定向回应用程序的主身份验证视图
        // 如果出现错误，我们可以将它们重定向回它们来自错误消息的位置
        //
        return $response == Password::PASSWORD_RESET
                    ? $this->sendResetResponse($response)//获得成功密码重置的响应
                    : $this->sendResetFailedResponse($request, $response);//获取失败密码重置的响应
    }

    /**
     * Get the password reset validation rules.
     *
     * 获取密码重置验证规则
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ];
    }

    /**
     * Get the password reset validation error messages.
     *
     * 获取密码重置验证错误消息
     *
     * @return array
     */
    protected function validationErrorMessages()
    {
        return [];
    }

    /**
     * Get the password reset credentials from the request.
     *
     * 从请求中获取密码重置凭证
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only(//从输入数据中获取包含所提供的键的子集
            'email', 'password', 'password_confirmation', 'token'
        );
    }

    /**
     * Reset the given user's password.
     *
     * 重置给定用户的密码
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        // 用属性数组填充模型。从批量赋值
        $user->forceFill([
            'password' => bcrypt($password),//哈希给定的值
            'remember_token' => Str::random(60),//生成一个更真实的“随机”alpha数字字符串
        ])->save();//将模型保存到数据库中
        //在密码重置期间使用该保护->将用户登录到应用程序中
        $this->guard()->login($user);
    }

    /**
     * Get the response for a successful password reset.
     *
     * 获得成功密码重置的响应
     *
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendResetResponse($response)
    {
        //得到重定向器的实例(获取post注册/登录重定向路径)
        return redirect($this->redirectPath())
                            //把数据闪存到会话中
                            ->with('status', trans($response));
    }

    /**
     * Get the response for a failed password reset.
     *
     * 获取失败密码重置的响应
     *
     * @param  \Illuminate\Http\Request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        //得到重定向器的实例->创建一个新的重定向响应到以前的位置
        return redirect()->back()
                    ->withInput($request->only('email'))//在会话中闪存输入的数组(从输入数据中获取包含所提供的键的子集)
                    ->withErrors(['email' => trans($response)]);//将错误的容器闪存到会话中
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

    /**
     * Get the guard to be used during password reset.
     *
     * 在密码重置期间使用该保护
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();//试图从本地缓存中得到守卫
    }
}
