<?php

namespace Illuminate\Http;

use BadMethodCallException;
use Illuminate\Support\Str;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Contracts\Support\MessageProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse as BaseRedirectResponse;
//继承自Symfony\Component\HttpFoundation\RedirectResponse
class RedirectResponse extends BaseRedirectResponse
{
    use ResponseTrait, Macroable {
        Macroable::__call as macroCall;
    }

    /**
     * The request instance.
     *
     * 请求实例
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The session store implementation.
     *
     * 会话存储实现
     *
     * @var \Illuminate\Session\Store
     */
    protected $session;

    /**
     * Flash a piece of data to the session.
     *
     * 把数据闪存到会话中
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return \Illuminate\Http\RedirectResponse
     */
    public function with($key, $value = null)
    {
        $key = is_array($key) ? $key : [$key => $value];

        foreach ($key as $k => $v) {
            $this->session->flash($k, $v);//在会话中闪存一个键/值对
        }

        return $this;
    }

    /**
     * Add multiple cookies to the response.
     *
     * 在响应中添加多个cookie
     *
     * @param  array  $cookies
     * @return $this
     */
    public function withCookies(array $cookies)
    {
        foreach ($cookies as $cookie) {
            //               设置cookie
            $this->headers->setCookie($cookie);
        }

        return $this;
    }

    /**
     * Flash an array of input to the session.
     *
     * 在会话中闪存输入的数组
     *
     * @param  array  $input
     * @return $this
     */
    public function withInput(array $input = null)
    {
        //    在会话中输入一个输入数组      从给定输入数组中删除所有上传的文件
        $this->session->flashInput($this->removeFilesFromInput(
            //                                     从请求中检索输入项
            ! is_null($input) ? $input : $this->request->input()
        ));

        return $this;
    }

    /**
     * Remove all uploaded files form the given input array.
     *
     * 从给定输入数组中删除所有上传的文件
     *
     * @param  array  $input
     * @return array
     */
    protected function removeFilesFromInput(array $input)
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                //                      从给定输入数组中删除所有上传的文件
                $input[$key] = $this->removeFilesFromInput($value);
            }

            if ($value instanceof SymfonyUploadedFile) {
                unset($input[$key]);
            }
        }

        return $input;
    }

    /**
     * Flash an array of input to the session.
     *
     * 在会话中显示输入的数组
     *
     * @return $this
     */
    public function onlyInput()
    {
        //  在会话中闪存输入的数组                 获取包含来自输入数据的值的所提供键的子集
        return $this->withInput($this->request->only(func_get_args()));
    }

    /**
     * Flash an array of input to the session.
     *
     * 在会话中显示输入的数组
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exceptInput()
    {
        //  在会话中闪存输入的数组              获取除指定数组项之外的所有输入
        return $this->withInput($this->request->except(func_get_args()));
    }

    /**
     * Flash a container of errors to the session.
     *
     * 将错误的容器闪存到会话中
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array|string  $provider
     * @param  string  $key
     * @return $this
     */
    public function withErrors($provider, $key = 'default')
    {
        //            将给定的错误解析为适当的值
        $value = $this->parseErrors($provider);
        //在会话中闪存一个键/值对
        $this->session->flash(//从会话中获取项目
            'errors', $this->session->get('errors', new ViewErrorBag)->put($key, $value)
        );

        return $this;
    }

    /**
     * Parse the given errors into an appropriate value.
     *
     * 将给定的错误解析为适当的值
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array|string  $provider
     * @return \Illuminate\Support\MessageBag
     */
    protected function parseErrors($provider)
    {
        if ($provider instanceof MessageProvider) {
            //                  从实例中获取消息
            return $provider->getMessageBag();
        }

        return new MessageBag((array) $provider);
    }

    /**
     * Get the original response content.
     *
     * 获取原始的响应内容
     *
     * @return null
     */
    public function getOriginalContent()
    {
        //
    }

    /**
     * Get the request instance.
     *
     * 获取请求实例
     *
     * @return \Illuminate\Http\Request|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the request instance.
     *
     * 设置请求实例
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the session store implementation.
     *
     * 获得会话存储实现
     *
     * @return \Illuminate\Session\Store|null
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set the session store implementation.
     *
     * 设置会话存储实现
     *
     * @param  \Illuminate\Session\Store  $session
     * @return void
     */
    public function setSession(SessionStore $session)
    {
        $this->session = $session;
    }

    /**
     * Dynamically bind flash data in the session.
     *
     * 在会话中动态绑定flash数据
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        //        检查宏是否已注册
        if (static::hasMacro($method)) {
            //              动态调用类的调用
            return $this->macroCall($method, $parameters);
        }
        //确定给定的子字符串是否属于给定的字符串
        if (Str::startsWith($method, 'with')) {
            //     把数据闪存到会话中  将字符串转换为蛇形命名
            return $this->with(Str::snake(substr($method, 4)), $parameters[0]);
        }

        throw new BadMethodCallException(
            "Method [$method] does not exist on Redirect."
        );
    }
}
