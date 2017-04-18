<?php

use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\Cookie\Factory as CookieFactory;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;

if (! function_exists('abort')) {
    /**
     * Throw an HttpException with the given data.
     *
     * 通过给定的数据抛出HttpException
     *
     * @param  int     $code
     * @param  string  $message
     * @param  array   $headers
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    function abort($code, $message = '', array $headers = [])
    {
        app()->abort($code, $message, $headers); //通过给定的数据抛出HttpException异常
    }
}

if (! function_exists('abort_if')) {
    /**
     * Throw an HttpException with the given data if the given condition is true.
     *
     * 如果条件为真通过给定的数据抛出HttpException
     *
     * @param  bool    $boolean
     * @param  int     $code
     * @param  string  $message
     * @param  array   $headers
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    function abort_if($boolean, $code, $message = '', array $headers = [])
    {
        if ($boolean) {
            abort($code, $message, $headers); //通过给定的数据抛出HttpException异常
        }
    }
}

if (! function_exists('abort_unless')) {
    /**
     * Throw an HttpException with the given data unless the given condition is true.
     *
     * 如果条件为假通过给定的数据抛出HttpException
     *
     * @param  bool    $boolean
     * @param  int     $code
     * @param  string  $message
     * @param  array   $headers
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    function abort_unless($boolean, $code, $message = '', array $headers = [])
    {
        if (! $boolean) {
            abort($code, $message, $headers); //通过给定的数据抛出HttpException异常
        }
    }
}

if (! function_exists('action')) {
    /**
     * Generate the URL to a controller action.
     *
     * 生成URL到一个控制器的动作
     *
     * @param  string  $name
     * @param  array   $parameters
     * @param  bool    $absolute
     * @return string
     */
    function action($name, $parameters = [], $absolute = true)
    {
        // Illuminate\Routing\UrlGenerator::action()获取控制器动作的URL
        return app('url')->action($name, $parameters, $absolute);
    }
}

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * 获取可用容器实例
     *
     * @param  string  $abstract
     * @return mixed|\Illuminate\Foundation\Application
     */
    function app($abstract = null)
    {
        if (is_null($abstract)) {
            return Container::getInstance(); //获取可用容器实例
        }

        return Container::getInstance()->make($abstract); //获取可用容器实例解析对象返回
    }
}

if (! function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     *
     * 获取应用程序文件夹的路径
     *
     * @param  string  $path
     * @return string
     */
    function app_path($path = '')
    {
        //Illuminate\Foundation\Application::path() 获取应用程序"app"目录的路径
        return app('path').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('asset')) {
    /**
     * Generate an asset path for the application.
     *
     * 为应用程序生成asset路径
     *
     * @param  string  $path
     * @param  bool    $secure
     * @return string
     */
    function asset($path, $secure = null)
    {
        // Illuminate\Routing\UrlGenerator::asset()生成应用程序asset的URL
        return app('url')->asset($path, $secure);
    }
}

if (! function_exists('auth')) {
    /**
     * Get the available auth instance.
     *
     * 获取可用的验证实例
     *
     * @param  string|null  $guard
     * @return \Illuminate\Contracts\Auth\Factory|\Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     */
    function auth($guard = null)
    {
        if (is_null($guard)) {
            return app(AuthFactory::class);
        } else {
            return app(AuthFactory::class)->guard($guard); //通过名称获取守护实例
        }
    }
}

if (! function_exists('back')) {
    /**
     * Create a new redirect response to the previous location.
     *
     * 创建一个新的重定向响应到以前的位置
     *
     * @param  int    $status
     * @param  array  $headers
     * @param  mixed  $fallback
     * @return \Illuminate\Http\RedirectResponse
     */
    function back($status = 302, $headers = [], $fallback = false)
    {
        //   \Illuminate\Routing\Redirector::back()创建一个新的重定向响应到以前的位置
        return app('redirect')->back($status, $headers, $fallback);
    }
}

if (! function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     *
     * 获取安装基础的路径
     *
     * @param  string  $path
     * @return string
     */
    function base_path($path = '')
    {
        //    获取Laravel安装的基础路径
        return app()->basePath().($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('bcrypt')) {
    /**
     * Hash the given value.
     *
     * 哈希给定的值
     *
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    function bcrypt($value, $options = [])
    {   //\Illuminate\Contracts\Hashing\Hasher::make()哈希给定的值
        return app('hash')->make($value, $options);
    }
}

if (! function_exists('broadcast')) {
    /**
     * Begin broadcasting an event.
     *
     * 开始广播一个事件
     *
     * @param  mixed|null  $event
     * @return \Illuminate\Broadcasting\PendingBroadcast|void
     */
    function broadcast($event = null)
    {
        return app(BroadcastFactory::class)->event($event);
    }
}

if (! function_exists('cache')) {
    /**
     * Get / set the specified cache value.
     *
     * 获取/设置指定的缓存值
     *
     * If an array is passed, we'll assume you want to put to the cache.
     *
     * 如果一个数组被传递，我们会假设你想放在缓存
     *
     * @param  dynamic  key|key,default|data,expiration|null
     * @return mixed
     *
     * @throws \Exception
     */
    function cache()
    {
        $arguments = func_get_args();

        if (empty($arguments)) { // 不传参数，返回\Illuminate\Cache\CacheManager
            return app('cache');
        }

        if (is_string($arguments[0])) { //一个字符串参数，获取缓存
            //                   试图从本地缓存获取存储区
            return app('cache')->get($arguments[0], isset($arguments[1]) ? $arguments[1] : null);
        }

        if (is_array($arguments[0])) {//如果第一个参数是数组
            if (! isset($arguments[1])) { //如果第二个参数不存在
                throw new Exception(
                    'You must set an expiration time when putting to the cache.'
                );
            }
            //设置缓存
            return app('cache')->put(key($arguments[0]), reset($arguments[0]), $arguments[1]);
        }
    }
}

if (! function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * 获取/设置指定的配置值
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * 如果一个数组被传递，我们会假设你想放在缓存
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function config($key = null, $default = null)
    {
        if (is_null($key)) { //如果为空，返回\Illuminate\Config\Repository
            return app('config');
        }

        if (is_array($key)) { //如果key是数组，设置配置
            return app('config')->set($key);
        }
        //        获取指定key的配置
        return app('config')->get($key, $default);
    }
}

if (! function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * 获取配置路径
     *
     * @param  string  $path
     * @return string
     */
    function config_path($path = '')
    {
        //          Application::configPath()
        return app()->make('path.config').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('cookie')) {
    /**
     * Create a new cookie instance.
     *
     * 创建一个新的cookie实例
     *
     * @param  string  $name
     * @param  string  $value
     * @param  int     $minutes
     * @param  string  $path
     * @param  string  $domain
     * @param  bool    $secure
     * @param  bool    $httpOnly
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    function cookie($name = null, $value = null, $minutes = 0, $path = null, $domain = null, $secure = false, $httpOnly = true)
    {
        $cookie = app(CookieFactory::class);

        if (is_null($name)) {
            return $cookie;
        }
        //       创建一个新的cookie实例
        return $cookie->make($name, $value, $minutes, $path, $domain, $secure, $httpOnly);
    }
}

if (! function_exists('csrf_field')) {
    /**
     * Generate a CSRF token form field.
     *
     * 生成一个CSRF令牌形式域
     *
     * @return \Illuminate\Support\HtmlString
     */
    function csrf_field()
    {
        //          创建一个新的HTML字符串实例
        return new HtmlString('<input type="hidden" name="_token" value="'.csrf_token().'">');
    }
}

if (! function_exists('csrf_token')) {
    /**
     * Get the CSRF token value.
     *
     * 得到的CSRF令牌值
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    function csrf_token()
    {
        $session = app('session'); //Illuminate\Session\Store

        if (isset($session)) {
            return $session->token();//得到的CSRF令牌值
        }

        throw new RuntimeException('Application session store not set.');
    }
}

if (! function_exists('database_path')) {
    /**
     * Get the database path.
     *
     * 获取数据库路径
     *
     * @param  string  $path
     * @return string
     */
    function database_path($path = '')
    {
        //              获取数据库目录的路径
        return app()->databasePath().($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('decrypt')) {
    /**
     * Decrypt the given value.
     *
     * 解密给定的值
     *
     * @param  string  $value
     * @return string
     */
    function decrypt($value)
    {
        // Illuminate\Encryption\Encrypter::decrypt()
        return app('encrypter')->decrypt($value);
    }
}

if (! function_exists('dispatch')) {
    /**
     * Dispatch a job to its appropriate handler.
     *
     * 把工作分派给适当的处理者
     *
     * @param  mixed  $job
     * @return mixed
     */
    function dispatch($job)
    {
        //                             触发事件并调用监听器
        return app(Dispatcher::class)->dispatch($job);
    }
}

if (! function_exists('elixir')) {
    /**
     * Get the path to a versioned Elixir file.
     *
     * 得到一个版本的Elixir文件的路径
     *
     * @param  string  $file
     * @param  string  $buildDirectory
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    function elixir($file, $buildDirectory = 'build')
    {
        static $manifest = [];
        static $manifestPath;

        if (empty($manifest) || $manifestPath !== $buildDirectory) {
            $path = public_path($buildDirectory.'/rev-manifest.json');

            if (file_exists($path)) {
                $manifest = json_decode(file_get_contents($path), true);
                $manifestPath = $buildDirectory;
            }
        }

        $file = ltrim($file, '/');

        if (isset($manifest[$file])) {
            return '/'.trim($buildDirectory.'/'.$manifest[$file], '/');
        }

        $unversioned = public_path($file);

        if (file_exists($unversioned)) {
            return '/'.trim($file, '/');
        }

        throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
    }
}

if (! function_exists('encrypt')) {
    /**
     * Encrypt the given value.
     *
     * 加密给定的值
     *
     * @param  string  $value
     * @return string
     */
    function encrypt($value)
    {
        // Illuminate\Encryption\Encrypter::encrypt()
        return app('encrypter')->encrypt($value);
    }
}

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
	 *
	 * 获取环境变量的值
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key); //获取环境变量的值

        if ($value === false) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }

        if (strlen($value) > 1 && Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (! function_exists('event')) {
    /**
     * Dispatch an event and call the listeners.
     *
     * 调度事件并调用侦听器
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    function event(...$args)
    {
        return app('events')->dispatch(...$args); //调用app的events
    }
}

if (! function_exists('factory')) {
    /**
     * Create a model factory builder for a given class, name, and amount.
     *
     * 为给定的类、名称和数量创建模型工厂生成器
     *
     * @param  dynamic  class|class,name|class,amount|class,name,amount
     * @return \Illuminate\Database\Eloquent\FactoryBuilder
     */
    function factory()
    {
        $factory = app(EloquentFactory::class);

        $arguments = func_get_args();

        if (isset($arguments[1]) && is_string($arguments[1])) {
            //    为给定的模型创建生成器
            return $factory->of($arguments[0], $arguments[1])->times(isset($arguments[2]) ? $arguments[2] : null);
        } elseif (isset($arguments[1])) {
            return $factory->of($arguments[0])->times($arguments[1]);
        } else {
            return $factory->of($arguments[0]);
        }
    }
}

if (! function_exists('info')) {
    /**
     * Write some information to the log.
     *
     * 写一些信息给日志
     *
     * @param  string  $message
     * @param  array   $context
     * @return void
     */
    function info($message, $context = [])
    {
        //\Illuminate\Log\Writer::info()将信息消息记录到日志
        return app('log')->info($message, $context);
    }
}

if (! function_exists('logger')) {
    /**
     * Log a debug message to the logs.
     *
     * 将调试消息记录到日志
     *
     * @param  string  $message
     * @param  array  $context
     * @return \Illuminate\Contracts\Logging\Log|null
     */
    function logger($message = null, array $context = [])
    {
        if (is_null($message)) { //没有message返回\Illuminate\Log\Writer
            return app('log');
        }
        //\Illuminate\Log\Writer::debug()将调试消息记录到日志
        return app('log')->debug($message, $context);
    }
}

if (! function_exists('method_field')) {
    /**
     * Generate a form field to spoof the HTTP verb used by forms.
     *
     * 生成表单字段以欺骗窗体使用的HTTP谓词
     *
     * @param  string  $method
     * @return \Illuminate\Support\HtmlString
     */
    function method_field($method)
    {
        return new HtmlString('<input type="hidden" name="_method" value="'.$method.'">');
    }
}

if (! function_exists('mix')) {
    /**
     * Get the path to a versioned Mix file.
     *
     * 得到一个版本的Mix文件的路径
     *
     * @param  string  $path
     * @param  string  $manifestDirectory
     * @return \Illuminate\Support\HtmlString
     *
     * @throws \Exception
     */
    function mix($path, $manifestDirectory = '')
    {
        static $manifest;

        if (! starts_with($path, '/')) {
            $path = "/{$path}";
        }

        if ($manifestDirectory && ! starts_with($manifestDirectory, '/')) {
            $manifestDirectory = "/{$manifestDirectory}";
        }

        if (file_exists(public_path($manifestDirectory.'/hot'))) {
            return new HtmlString("http://localhost:8080{$path}");
        }

        if (! $manifest) {
            if (! file_exists($manifestPath = public_path($manifestDirectory.'/mix-manifest.json'))) {
                throw new Exception('The Mix manifest does not exist.');
            }

            $manifest = json_decode(file_get_contents($manifestPath), true);
        }

        if (! array_key_exists($path, $manifest)) {
            throw new Exception(
                "Unable to locate Mix file: {$path}. Please check your ".
                'webpack.mix.js output paths and try again.'
            );
        }

        return new HtmlString($manifestDirectory.$manifest[$path]);
    }
}

if (! function_exists('old')) {
    /**
     * Retrieve an old input item.
     *
     * 检索旧输入项
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function old($key = null, $default = null)
    {
        //Illuminate\Http\Request trait InteractsWithFlashData::old()检索旧输入项
        return app('request')->old($key, $default);
    }
}

if (! function_exists('policy')) {
    /**
     * Get a policy instance for a given class.
     *
     * 获取给定类的策略实例
     *
     * @param  object|string  $class
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    function policy($class)
    {
        //                      获取给定类的策略实例
        return app(Gate::class)->getPolicyFor($class);
    }
}

if (! function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * 获取公用文件夹的路径
     *
     * @param  string  $path
     * @return string
     */
    function public_path($path = '')
    {
        //Application::publicPath()
        return app()->make('path.public').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('redirect')) {
    /**
     * Get an instance of the redirector.
	 *
	 * 得到重定向器的实例
	 * Illuminate\Routing\RoutingServiceProvider::registerRedirector()中注册
     *
     * @param  string|null  $to
     * @param  int     $status
     * @param  array   $headers
     * @param  bool    $secure
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    function redirect($to = null, $status = 302, $headers = [], $secure = null)
    {
        if (is_null($to)) {
            return app('redirect');
        }
        //Illuminate\Routing\Redirector::to()为给定路径创建新的重定向响应
        return app('redirect')->to($to, $status, $headers, $secure);
    }
}

if (! function_exists('request')) {
    /**
     * Get an instance of the current request or an input item from the request.
     *
     * 从请求中获取当前请求或输入项的实例
     *
     * @param  array|string  $key
     * @param  mixed   $default
     * @return \Illuminate\Http\Request|string|array
     */
    function request($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('request');
        }

        if (is_array($key)) {
            return app('request')->only($key); //Illuminate\Http\Request trait InteractsWithInput::only() 获取包含来自输入数据的值的所提供键的子集
        }

        return data_get(app('request')->all(), $key, $default);//Illuminate\Http\Request trait InteractsWithInput::all() 获取请求的所有输入和文件
    }
}

if (! function_exists('resolve')) {
    /**
     * Resolve a service from the container.
     *
     * 从容器解析服务
     *
     * @param  string  $name
     * @return mixed
     */
    function resolve($name)
    {
        return app($name);
    }
}

if (! function_exists('resource_path')) {
    /**
     * Get the path to the resources folder.
     *
     * 获取资源文件夹的路径
     *
     * @param  string  $path
     * @return string
     */
    function resource_path($path = '')
    {
        //     Application::resourcePath()获取资源目录路径
        return app()->resourcePath().($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('response')) {
    /**
     * Return a new response from the application.
	 *
	 * 从应用程序返回新的响应
     *
     * @param  string  $content
     * @param  int     $status
     * @param  array   $headers
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    function response($content = '', $status = 200, array $headers = [])
    {
		// Illuminate\Routing\ResponseFactory.php
        $factory = app(ResponseFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }
        //              从应用程序返回新的响应
        return $factory->make($content, $status, $headers);
    }
}

if (! function_exists('route')) {
    /**
     * Generate the URL to a named route.
     *
     * 生成命名路由的URL
     *
     * @param  string  $name
     * @param  array   $parameters
     * @param  bool    $absolute
     * @return string
     */
    function route($name, $parameters = [], $absolute = true)
    {
        // Illuminate\Routing\UrlGenerator::route()获取指定路由的URL
        return app('url')->route($name, $parameters, $absolute);
    }
}

if (! function_exists('secure_asset')) {
    /**
     * Generate an asset path for the application.
     *
     * 为应用程序生成asset路径
     *
     * @param  string  $path
     * @return string
     */
    function secure_asset($path)
    {
        return asset($path, true);
    }
}

if (! function_exists('secure_url')) {
    /**
     * Generate a HTTPS url for the application.
     *
     * 为应用程序生成HTTPS url
     *
     * @param  string  $path
     * @param  mixed   $parameters
     * @return string
     */
    function secure_url($path, $parameters = [])
    {
        return url($path, $parameters, true);
    }
}

if (! function_exists('session')) {
    /**
     * Get / set the specified session value.
     *
     * 获取/设置指定的会话值
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * 如果数组作为$key传递，我们将假定您希望设置值数组
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function session($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('session');  //\Illuminate\Session\SessionManager
        }

        if (is_array($key)) {
            return app('session')->put($key); //\Illuminate\Session\Store::put()将密钥/值对或数组中的键值/值对
        }
        //     \Illuminate\Session\Store::get()  从会话中获取项目
        return app('session')->get($key, $default);
    }
}

if (! function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * 获取存储文件夹的路径
     *
     * @param  string  $path
     * @return string
     */
    function storage_path($path = '')
    {
        //          Application::storagePath()
        return app('path.storage').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('trans')) {
    /**
     * Translate the given message.
     *
     * 翻译给定的信息
     *
     * @param  string  $id
     * @param  array   $replace
     * @param  string  $locale
     * @return \Illuminate\Contracts\Translation\Translator|string
     */
    function trans($id = null, $replace = [], $locale = null)
    {
        if (is_null($id)) {
            return app('translator');
        }
        //           \Illuminate\Translation\Translator::trans()获取给定键的翻译
        return app('translator')->trans($id, $replace, $locale);
    }
}

if (! function_exists('trans_choice')) {
    /**
     * Translates the given message based on a count.
     *
     * 根据计数翻译给定的消息
     *
     * @param  string  $id
     * @param  int|array|\Countable  $number
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    function trans_choice($id, $number, array $replace = [], $locale = null)
    {
        //        \Illuminate\Translation\Translator::transChoice()根据整数值得到一个翻译
        return app('translator')->transChoice($id, $number, $replace, $locale);
    }
}

if (! function_exists('__')) {
    /**
     * Translate the given message.
     *
     * 翻译给定的信息
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string  $locale
     * @return \Illuminate\Contracts\Translation\Translator|string
     */
    function __($key = null, $replace = [], $locale = null)
    {
        //         \Illuminate\Translation\Translator::getFromJson()从JSON翻译文件获取给定键的翻译
        return app('translator')->getFromJson($key, $replace, $locale);
    }
}

if (! function_exists('url')) {
    /**
     * Generate a url for the application.
     *
     * 为应用程序生成URL
     *
     * @param  string  $path
     * @param  mixed   $parameters
     * @param  bool    $secure
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    function url($path = null, $parameters = [], $secure = null)
    {
        if (is_null($path)) {
            return app(UrlGenerator::class);
        }
        //           Illuminate\Routing\UrlGenerator::to()生成给定路径的绝对URL
        return app(UrlGenerator::class)->to($path, $parameters, $secure);
    }
}

if (! function_exists('validator')) {
    /**
     * Create a new Validator instance.
     *
     * 创建一个新的验证实例
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return \Illuminate\Contracts\Validation\Validator
     */
    function validator(array $data = [], array $rules = [], array $messages = [], array $customAttributes = [])
    {
        $factory = app(ValidationFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }
        //          创建一个新的验证实例
        return $factory->make($data, $rules, $messages, $customAttributes);
    }
}

if (! function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
	 *
	 * 获取给定视图的得到视图内容
     *
     * @param  string  $view
     * @param  array   $data
     * @param  array   $mergeData
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    function view($view = null, $data = [], $mergeData = [])
    {
		//             \Illuminate\View\Factory
        $factory = app(ViewFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }
		//          \Illuminate\View\Factory::make() 获取给定视图的得到视图内容
        return $factory->make($view, $data, $mergeData);
    }
}
