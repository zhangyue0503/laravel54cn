<?php

namespace Illuminate\Session;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SessionHandlerInterface;
use Illuminate\Contracts\Session\Session;

class Store implements Session
{
    /**
     * The session ID.
     *
     * 会话ID
     *
     * @var string
     */
    protected $id;

    /**
     * The session name.
     *
     * 会话名称
     *
     * @var string
     */
    protected $name;

    /**
     * The session attributes.
     *
     * 会话属性
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The session handler implementation.
     *
     * 会话处理实现
     *
     * @var \SessionHandlerInterface
     */
    protected $handler;

    /**
     * Session store started status.
     *
     * 会话存储开始状态
     *
     * @var bool
     */
    protected $started = false;

    /**
     * Create a new session instance.
	 *
	 * 创建一个新的会话实例
     *
     * @param  string $name
     * @param  \SessionHandlerInterface $handler
     * @param  string|null $id
     * @return void
     */
    public function __construct($name, SessionHandlerInterface $handler, $id = null)
    {
        $this->setId($id); // 设置会话ID
        $this->name = $name;
        $this->handler = $handler;
    }

    /**
     * Start the session, reading the data from a handler.
	 *
	 * 启动会话，从处理程序读取数据
     *
     * @return bool
     */
    public function start()
    {
        //从处理程序加载会话数据
        $this->loadSession();
        //检查一个键是否存在并且不是空
        if (! $this->has('_token')) {
            //重新生成CSRF令牌值
            $this->regenerateToken();
        }

        return $this->started = true;
    }

    /**
     * Load the session data from the handler.
	 *
	 * 从处理程序加载会话数据
     *
     * @return void
     */
    protected function loadSession()
    {
		//                                                  从处理程序读取会话数据
        $this->attributes = array_merge($this->attributes, $this->readFromHandler());
    }

    /**
     * Read the session data from the handler.
	 *
	 * 从处理程序读取会话数据
     *
     * @return array
     */
    protected function readFromHandler()
    {
		//                  读取会话数据(获取当前的会话ID)
        if ($data = $this->handler->read($this->getId())) {
            //                        从会话中准备未序列化的原始字符串数据
            $data = @unserialize($this->prepareForUnserialize($data));

            if ($data !== false && ! is_null($data) && is_array($data)) {
                return $data;
            }
        }

        return [];
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
        return $data;
    }

    /**
     * Save the session data to storage.
     *
     * 将会话数据保存到存储中
     *
     * @return bool
     */
    public function save()
    {
        //为会话提供flash数据
        $this->ageFlashData();
        //                      获取当前的会话ID       准备用于存储的序列化会话数据
        $this->handler->write($this->getId(), $this->prepareForStorage(
            serialize($this->attributes)
        ));

        $this->started = false;
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
        return $data;
    }

    /**
     * Age the flash data for the session.
     *
     * 为会话提供flash数据
     *
     * @return void
     */
    public function ageFlashData()
    {
        //从会话中删除一个或多个项目 从会话中获取项目
        $this->forget($this->get('_flash.old', []));
        //将键/值对或数组中的键/值对放入session
        $this->put('_flash.old', $this->get('_flash.new', []));

        $this->put('_flash.new', []);
    }

    /**
     * Get all of the session data.
     *
     * 获取所有的会话数据
     *
     * @return array
     */
    public function all()
    {
        return $this->attributes;
    }

    /**
     * Checks if a key exists.
     *
     * 检查一个键是否存在
     *
     * @param  string|array  $key
     * @return bool
     */
    public function exists($key)
    {
        //                                                        确定集合中是否存在项
        return ! collect(is_array($key) ? $key : func_get_args())->contains(function ($key) {
            //      确定给定的key是否存在于提供的数组中
            return ! Arr::exists($this->attributes, $key);
        });
    }

    /**
     * Checks if an a key is present and not null.
     *
     * 检查一个键是否存在并且不是空
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key)
    {
        //                                                     确定集合中是否存在项
        return ! collect(is_array($key) ? $key : func_get_args())->contains(function ($key) {
            //              从会话中获取项目
            return is_null($this->get($key));
        });
    }

    /**
     * Get an item from the session.
     *
     * 从会话中获取项目
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        //使用“点”符号从数组中获取一个项
        return Arr::get($this->attributes, $key, $default);
    }

    /**
     * Get the value of a given key and then forget it.
     *
     * 获取一个给定键的值，然后忘记它
     *
     * @param  string  $key
     * @param  string  $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        //从数组中获取值，并将其移除
        return Arr::pull($this->attributes, $key, $default);
    }

    /**
     * Determine if the session contains old input.
     *
     * 确定会话是否包含旧输入
     *
     * @param  string  $key
     * @return bool
     */
    public function hasOldInput($key = null)
    {
        //从闪存的输入数组中获取所请求的项
        $old = $this->getOldInput($key);

        return is_null($key) ? count($old) > 0 : ! is_null($old);
    }

    /**
     * Get the requested item from the flashed input array.
     *
     * 从闪存的输入数组中获取所请求的项
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function getOldInput($key = null, $default = null)
    {
        //使用“点”符号从数组中获取一个项  从会话中获取项目
        return Arr::get($this->get('_old_input', []), $key, $default);
    }

    /**
     * Replace the given session attributes entirely.
     *
     * 完全替换给定的会话属性
     *
     * @param  array  $attributes
     * @return void
     */
    public function replace(array $attributes)
    {
        //将键/值对或数组中的键/值对放入session
        $this->put($attributes);
    }

    /**
     * Put a key / value pair or array of key / value pairs in the session.
     *
     * 将键/值对或数组中的键/值对放入session
     *
     * @param  string|array  $key
     * @param  mixed       $value
     * @return void
     */
    public function put($key, $value = null)
    {
        if (! is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $arrayKey => $arrayValue) {
            //如果没有给定key的方法，整个数组将被替换
            Arr::set($this->attributes, $arrayKey, $arrayValue);
        }
    }

    /**
     * Get an item from the session, or store the default value.
     *
     * 从会话中获取一个项目，或者存储默认值
     *
     * @param  string  $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function remember($key, Closure $callback)
    {
        //                      从会话中获取项目
        if (! is_null($value = $this->get($key))) {
            return $value;
        }
        //用给定的值调用给定的闭包，然后返回值
        return tap($callback(), function ($value) use ($key) {
            //将键/值对或数组中的键/值对放入session
            $this->put($key, $value);
        });
    }

    /**
     * Push a value onto a session array.
     *
     * 将一个值推到一个会话数组中
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function push($key, $value)
    {
        //从会话中获取项目
        $array = $this->get($key, []);

        $array[] = $value;
        //将键/值对或数组中的键/值对放入session
        $this->put($key, $array);
    }

    /**
     * Increment the value of an item in the session.
     *
     * 在会话中增加一个项目的值
     *
     * @param  string  $key
     * @param  int  $amount
     * @return mixed
     */
    public function increment($key, $amount = 1)
    {
        //将键/值对或数组中的键/值对放入session  从会话中获取项目
        $this->put($key, $value = $this->get($key, 0) + $amount);

        return $value;
    }

    /**
     * Decrement the value of an item in the session.
     *
     * 在会话中减少一个项目的值
     *
     * @param  string  $key
     * @param  int  $amount
     * @return int
     */
    public function decrement($key, $amount = 1)
    {
        //在会话中增加一个项目的值
        return $this->increment($key, $amount * -1);
    }

    /**
     * Flash a key / value pair to the session.
     *
     * 在会话中闪存一个键/值对
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function flash($key, $value)
    {
        $this->put($key, $value);//将键/值对或数组中的键/值对放入session

        $this->push('_flash.new', $key);//将一个值推到一个会话数组中

        $this->removeFromOldFlashData([$key]);//从旧的flash数据中删除给定的键
    }

    /**
     * Flash a key / value pair to the session for immediate use.
     *
     * 将键/值对用于会话，以便立即使用
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function now($key, $value)
    {
        $this->put($key, $value);//将键/值对或数组中的键/值对放入session

        $this->push('_flash.old', $key);;//将一个值推到一个会话数组中
    }

    /**
     * Reflash all of the session flash data.
     *
     * 反射所有的会话闪存数据
     *
     * @return void
     */
    public function reflash()
    {
        //将新的flash键合并到新的flash数组中  从会话中获取项目
        $this->mergeNewFlashes($this->get('_flash.old', []));
        //将键/值对或数组中的键/值对放入session
        $this->put('_flash.old', []);
    }

    /**
     * Reflash a subset of the current flash data.
     *
     * 反射当前的flash数据的一个子集
     *
     * @param  array|mixed  $keys
     * @return void
     */
    public function keep($keys = null)
    {
        //将新的flash键合并到新的flash数组中
        $this->mergeNewFlashes($keys = is_array($keys) ? $keys : func_get_args());
        //从旧的flash数据中删除给定的键
        $this->removeFromOldFlashData($keys);
    }

    /**
     * Merge new flash keys into the new flash array.
     *
     * 将新的flash键合并到新的flash数组中
     *
     * @param  array  $keys
     * @return void
     */
    protected function mergeNewFlashes(array $keys)
    {
        //                                从会话中获取项目
        $values = array_unique(array_merge($this->get('_flash.new', []), $keys));
        //将键/值对或数组中的键/值对放入session
        $this->put('_flash.new', $values);
    }

    /**
     * Remove the given keys from the old flash data.
     *
     * 从旧的flash数据中删除给定的键
     *
     * @param  array  $keys
     * @return void
     */
    protected function removeFromOldFlashData(array $keys)
    {
        //将键/值对或数组中的键/值对放入session         从会话中获取项目
        $this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
    }

    /**
     * Flash an input array to the session.
     *
     * 在会话中输入一个输入数组
     *
     * @param  array  $value
     * @return void
     */
    public function flashInput(array $value)
    {
        //在会话中闪存一个键/值对
        $this->flash('_old_input', $value);
    }

    /**
     * Remove an item from the session, returning its value.
     *
     * 从会话中删除一个条目，返回它的值
     *
     * @param  string  $key
     * @return mixed
     */
    public function remove($key)
    {
        //从数组中获取值，并将其移除
        return Arr::pull($this->attributes, $key);
    }

    /**
     * Remove one or many items from the session.
     *
     * 从会话中删除一个或多个项目
     *
     * @param  string|array  $keys
     * @return void
     */
    public function forget($keys)
    {
        //使用“点”符号从给定数组中移除一个或多个数组项
        Arr::forget($this->attributes, $keys);
    }

    /**
     * Remove all of the items from the session.
     *
     * 从会话中移除所有项目
     *
     * @return void
     */
    public function flush()
    {
        $this->attributes = [];
    }

    /**
     * Flush the session data and regenerate the ID.
     *
     * 刷新会话数据并重新生成ID
     *
     * @return bool
     */
    public function invalidate()
    {
        //从会话中移除所有项目
        $this->flush();
        //为会话生成一个新的会话ID
        return $this->migrate(true);
    }

    /**
     * Generate a new session identifier.
     *
     * 生成一个新的会话标识符
     *
     * @param  bool  $destroy
     * @return bool
     */
    public function regenerate($destroy = false)
    {
        //为会话生成一个新的会话ID
        return $this->migrate($destroy);
    }

    /**
     * Generate a new session ID for the session.
     *
     * 为会话生成一个新的会话ID
     *
     * @param  bool  $destroy
     * @return bool
     */
    public function migrate($destroy = false)
    {
        if ($destroy) {
            //                        获取当前的会话ID
            $this->handler->destroy($this->getId());
        }

        $this->setExists(false);//在处理程序中设置会话的存在，如果适用的话
        //设置会话ID        获取一个新的随机会话ID
        $this->setId($this->generateSessionId());

        return true;
    }

    /**
     * Determine if the session has been started.
     *
     * 确定会话是否已启动
     *
     * @return bool
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Get the name of the session.
     *
     * 获得会话的名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the session.
     *
     * 设置会话的名称
     *
     * @param  string  $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the current session ID.
	 *
	 * 获取当前的会话ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the session ID.
	 *
	 * 设置会话ID
     *
     * @param  string  $id
     * @return void
     */
    public function setId($id)
    {
        //         确定这是一个有效的会话ID               获取一个新的随机会话ID
        $this->id = $this->isValidId($id) ? $id : $this->generateSessionId();
    }

    /**
     * Determine if this is a valid session ID.
     *
     * 确定这是一个有效的会话ID
     *
     * @param  string  $id
     * @return bool
     */
    public function isValidId($id)
    {
        return is_string($id) && ctype_alnum($id) && strlen($id) === 40;
    }

    /**
     * Get a new, random session ID.
     *
     * 获取一个新的随机会话ID
     *
     * @return string
     */
    protected function generateSessionId()
    {
        //生成一个更真实的“随机”alpha数字字符串
        return Str::random(40);
    }

    /**
     * Set the existence of the session on the handler if applicable.
     *
     * 在处理程序中设置会话的存在，如果适用的话
     *
     * @param  bool  $value
     * @return void
     */
    public function setExists($value)
    {
        if ($this->handler instanceof ExistenceAwareInterface) {
            //设置会话的存在状态
            $this->handler->setExists($value);
        }
    }

    /**
     * Get the CSRF token value.
     *
     * 得到的CSRF令牌值
     *
     * @return string
     */
    public function token()
    {
        //从会话中获取项目
        return $this->get('_token');
    }

    /**
     * Regenerate the CSRF token value.
     *
     * 重新生成CSRF令牌值
     *
     * @return void
     */
    public function regenerateToken()
    {
        //将键/值对或数组中的键/值对放入session    生成一个更真实的“随机”alpha数字字符串
        $this->put('_token', Str::random(40));
    }

    /**
     * Get the previous URL from the session.
     *
     * 从会话中获取以前的URL
     *
     * @return string|null
     */
    public function previousUrl()
    {
        //从会话中获取项目
        return $this->get('_previous.url');
    }

    /**
     * Set the "previous" URL in the session.
     *
     * 在会话中设置“之前”的URL
     *
     * @param  string  $url
     * @return void
     */
    public function setPreviousUrl($url)
    {
        //将键/值对或数组中的键/值对放入session
        $this->put('_previous.url', $url);
    }

    /**
     * Get the underlying session handler implementation.
     *
     * 获取底层会话处理程序实现
     *
     * @return \SessionHandlerInterface
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Determine if the session handler needs a request.
     *
     * 确定会话处理程序是否需要一个请求
     *
     * @return bool
     */
    public function handlerNeedsRequest()
    {
        return $this->handler instanceof CookieSessionHandler;
    }

    /**
     * Set the request on the handler instance.
	 *
	 * 在处理程序实例上设置请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function setRequestOnHandler($request)
    {
        // 确定会话处理程序是否需要一个请求
        if ($this->handlerNeedsRequest()) {
            //             设置请求实例
            $this->handler->setRequest($request);
        }
    }
}
