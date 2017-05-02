<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait AuthenticatesUsers
{
    use RedirectsUsers, ThrottlesLogins;

    /**
     * Show the application's login form.
     *
     * 显示应用程序的登录表单
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * 处理应用程序的登录请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        //验证用户的登录请求
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        //
        // 如果类是用ThrottlesLogins特性，我们可以自动油门该应用程序的登录尝试
        // 我们将以客户机的用户名和IP地址为重点，将这些请求应用到这个应用程序中
        //
        //      确定用户是否有太多失败的登录尝试
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);//当锁定发生时，触发一个事件

            return $this->sendLockoutResponse($request);//在确定用户被锁定后重定向用户
        }
        //尝试将用户登录到应用程序
        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);//在用户被验证后发送响应
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        //
        // 如果登录尝试失败，我们将增加尝试登录的次数，并将用户重定向到登录表单
        // 当然，当这个用户超过他们的最大数量的尝试，他们将被锁定
        //
        //         增加用户的登录尝试
        $this->incrementLoginAttempts($request);

        //          获取失败的登录响应实例
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Validate the user login request.
     *
     * 验证用户的登录请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        //使用给定的规则验证给定的请求
        $this->validate($request, [
            //获得控制器使用的登录用户名
            $this->username() => 'required', 'password' => 'required',
        ]);
    }

    /**
     * Attempt to log the user into the application.
     *
     * 尝试将用户登录到应用程序
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        //在身份验证过程中使用该保护   尝试使用给定的凭据对用户进行身份验证
        return $this->guard()->attempt(
            //从请求中获得所需的授权凭证          确定该请求是否包含一个输入项的非空值
            $this->credentials($request), $request->has('remember')
        );
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * 从请求中获得所需的授权凭证
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        //从输入数据中获取包含所提供的键的子集  获得控制器使用的登录用户名
        return $request->only($this->username(), 'password');
    }

    /**
     * Send the response after the user was authenticated.
     *
     * 在用户被验证后发送响应
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function sendLoginResponse(Request $request)
    {
        //获取与请求关联的会话   生成一个新的会话标识符
        $request->session()->regenerate();
        //为给定的用户凭证清除登录锁
        $this->clearLoginAttempts($request);
        //用户已经通过了身份验证               在身份验证过程中使用该保护  获取当前经过身份验证的用户
        return $this->authenticated($request, $this->guard()->user())
                //    创建一个新的重定向响应到先前预定的位置  获取post注册/登录重定向路径
                ?: redirect()->intended($this->redirectPath());
    }

    /**
     * The user has been authenticated.
     *
     * 用户已经通过了身份验证
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        //
    }

    /**
     * Get the failed login response instance.
     *
     * 获取失败的登录响应实例
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        //           获得控制器使用的登录用户名
        $errors = [$this->username() => trans('auth.failed')];
        //        确定当前请求是否可能需要JSON响应
        if ($request->expectsJson()) {
            //  从应用程序返回新的响应   从应用程序返回一个新的JSON响应
            return response()->json($errors, 422);
        }
        //得到重定向器的实例 创建一个新的重定向响应到以前的位置
        return redirect()->back()
            //在会话中闪存输入的数组(从输入数据中获取包含所提供的键的子集(获得控制器使用的登录用户名,))
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);//将错误的容器闪存到会话中
    }

    /**
     * Get the login username to be used by the controller.
     *
     * 获得控制器使用的登录用户名
     *
     * @return string
     */
    public function username()
    {
        return 'email';
    }

    /**
     * Log the user out of the application.
     *
     * 记录用户退出应用程序
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        //在身份验证过程中使用该保护->记录用户退出应用程序
        $this->guard()->logout();
        //获取与请求关联的会话->从会话中移除所有项目
        $request->session()->flush();
        //获取与请求关联的会话->生成一个新的会话标识符
        $request->session()->regenerate();
        //得到重定向器的实例
        return redirect('/');
    }

    /**
     * Get the guard to be used during authentication.
     *
     * 在身份验证过程中使用该保护
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();//试图从本地缓存中得到守卫
    }
}
