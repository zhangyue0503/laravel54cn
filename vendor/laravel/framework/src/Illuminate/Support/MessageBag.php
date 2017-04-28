<?php

namespace Illuminate\Support;

use Countable;
use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
//信息包
class MessageBag implements Arrayable, Countable, Jsonable, JsonSerializable, MessageBagContract, MessageProvider
{
    /**
     * All of the registered messages.
     *
     * 所有已注册的信息
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Default format for message output.
     *
     * 信息输出的默认格式
     *
     * @var string
     */
    protected $format = ':message';

    /**
     * Create a new message bag instance.
     *
     * 创建一个新的信息包实例
     *
     * @param  array  $messages
     * @return void
     */
    public function __construct(array $messages = [])
    {
        foreach ($messages as $key => $value) {
            $this->messages[$key] = (array) $value;
        }
    }

    /**
     * Get the keys present in the message bag.
     *
     * 获取当前信息包中的keys
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->messages);
    }

    /**
     * Add a message to the bag.
     *
     * 向包中添加信息
     *
     * @param  string  $key
     * @param  string  $message
     * @return $this
     */
    public function add($key, $message)
    {
        //确定是否存在key和消息组合
        if ($this->isUnique($key, $message)) {
            $this->messages[$key][] = $message;
        }

        return $this;
    }

    /**
     * Determine if a key and message combination already exists.
     *
     * 确定是否存在key和消息组合
     *
     * @param  string  $key
     * @param  string  $message
     * @return bool
     */
    protected function isUnique($key, $message)
    {
        $messages = (array) $this->messages;

        return ! isset($messages[$key]) || ! in_array($message, $messages[$key]);
    }

    /**
     * Merge a new array of messages into the bag.
     *
     * 将新的消息数组合并到包中
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array  $messages
     * @return $this
     */
    public function merge($messages)
    {
        //         是否属于消息提供者
        if ($messages instanceof MessageProvider) {
            //                 从实例中获取消息->获取容器中的原始消息
            $messages = $messages->getMessageBag()->getMessages();
        }

        $this->messages = array_merge_recursive($this->messages, $messages);

        return $this;
    }

    /**
     * Determine if messages exist for all of the given keys.
     *
     * 确定所有给定的键是否存在消息
     *
     * @param  array|string  $key
     * @return bool
     */
    public function has($key)
    {
        if (is_null($key)) {
            return $this->any();//确定消息包是否有任何消息
        }

        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $key) {
            //从包中获取一个给定键的第一个消息
            if ($this->first($key) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if messages exist for any of the given keys.
     *
     * 确定是否存在任何给定键的消息
     *
     * @param  array  $keys
     * @return bool
     */
    public function hasAny($keys = [])
    {
        foreach ($keys as $key) {
            //确定所有给定的键是否存在消息
            if ($this->has($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the first message from the bag for a given key.
     *
     * 从包中获取一个给定键的第一个消息
     *
     * @param  string  $key
     * @param  string  $format
     * @return string
     */
    public function first($key = null, $format = null)
    {
        //                      获取包中每个关键字的所有消息      从给定的键的包中获取所有的消息
        $messages = is_null($key) ? $this->all($format) : $this->get($key, $format);
        //                通过给定的真值测试返回数组中的第一个元素
        $firstMessage = Arr::first($messages, null, '');
        //                               通过给定的真值测试返回数组中的第一个元素
        return is_array($firstMessage) ? Arr::first($firstMessage) : $firstMessage;
    }

    /**
     * Get all of the messages from the bag for a given key.
     *
     * 从给定的键的包中获取所有的消息
     *
     * @param  string  $key
     * @param  string  $format
     * @return array
     */
    public function get($key, $format = null)
    {
        // If the message exists in the container, we will transform it and return
        // the message. Otherwise, we'll check if the key is implicit & collect
        // all the messages that match a given key and output it as an array.
        //
        // 如果消息存在于容器中，我们将对其进行转换并返回消息
        // 否则，我们将检查密钥是否为隐式，并收集与给定键匹配的所有消息并将其作为数组输出
        //
        if (array_key_exists($key, $this->messages)) {
            //格式化消息中的数组
            return $this->transform(
                //                       根据给定格式获取相应的格式
                $this->messages[$key], $this->checkFormat($format), $key
            );
        }
        //确定一个给定的字符串包含另一个字符串
        if (Str::contains($key, '*')) {
            //          获取通配符的消息
            return $this->getMessagesForWildcardKey($key, $format);
        }

        return [];
    }

    /**
     * Get the messages for a wildcard key.
     *
     * 获取通配符的消息
     *
     * @param  string  $key
     * @param  string|null  $format
     * @return array
     */
    protected function getMessagesForWildcardKey($key, $format)
    {
        return collect($this->messages)
                //在每个项目上运行过滤器
                ->filter(function ($messages, $messageKey) use ($key) {
                    return Str::is($key, $messageKey);//确定给定的字符串是否与给定的模式匹配
                })
                //在每个项目上运行map
                ->map(function ($messages, $messageKey) use ($format) {
                    //格式化消息中的数组
                    return $this->transform(
                        //               根据给定格式获取相应的格式
                        $messages, $this->checkFormat($format), $messageKey
                    );
                })->all();//获取集合中的所有项目
    }

    /**
     * Get all of the messages for every key in the bag.
     *
     * 获取包中每个关键字的所有消息
     *
     * @param  string  $format
     * @return array
     */
    public function all($format = null)
    {
        //             根据给定格式获取相应的格式
        $format = $this->checkFormat($format);

        $all = [];

        foreach ($this->messages as $key => $messages) {
            //                           格式化消息中的数组
            $all = array_merge($all, $this->transform($messages, $format, $key));
        }

        return $all;
    }

    /**
     * Get all of the unique messages for every key in the bag.
     *
     * 获取包中每个关键字的唯一信息
     *
     * @param  string  $format
     * @return array
     */
    public function unique($format = null)
    {
        //                        获取包中每个关键字的所有消息
        return array_unique($this->all($format));
    }

    /**
     * Format an array of messages.
     *
     * 格式化消息中的数组
     *
     * @param  array   $messages
     * @param  string  $format
     * @param  string  $messageKey
     * @return array
     */
    protected function transform($messages, $format, $messageKey)
    {
        return collect((array) $messages)
            //在每个项目上运行map
            ->map(function ($message) use ($format, $messageKey) {
                // We will simply spin through the given messages and transform each one
                // replacing the :message place holder with the real message allowing
                // the messages to be easily formatted to each developer's desires.
                //
                // 我们将简单地对给定的消息进行旋转，并将每一个消息转换为:消息占位符与真正的消息，使消息可以轻松地格式化为每个开发人员的愿望。
                //
                return str_replace([':message', ':key'], [$message, $messageKey], $format);
            })->all();//获取集合中的所有项目
    }

    /**
     * Get the appropriate format based on the given format.
     *
     * 根据给定格式获取相应的格式
     *
     * @param  string  $format
     * @return string
     */
    protected function checkFormat($format)
    {
        return $format ?: $this->format;
    }

    /**
     * Get the raw messages in the container.
     *
     * 获取容器中的原始消息
     *
     * @return array
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * Get the raw messages in the container.
     *
     * 获取容器中的原始消息
     *
     * @return array
     */
    public function getMessages()
    {
        //获取容器中的原始消息
        return $this->messages();
    }

    /**
     * Get the messages for the instance.
     *
     * 获取实例的消息
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getMessageBag()
    {
        return $this;
    }

    /**
     * Get the default message format.
     *
     * 获取默认消息格式
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set the default message format.
     *
     * 设置默认消息格式
     *
     * @param  string  $format
     * @return \Illuminate\Support\MessageBag
     */
    public function setFormat($format = ':message')
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Determine if the message bag has any messages.
     *
     * 确定消息包是否有任何消息
     *
     * @return bool
     */
    public function isEmpty()
    {
        //确定消息包是否有任何消息
        return ! $this->any();
    }

    /**
     * Determine if the message bag has any messages.
     *
     * 确定消息包是否有任何消息
     *
     * @return bool
     */
    public function any()
    {
        return $this->count() > 0; //获取容器中的消息数
    }

    /**
     * Get the number of messages in the container.
     *
     * 获取容器中的消息数
     *
     * @return int
     */
    public function count()
    {
        return count($this->messages, COUNT_RECURSIVE) - count($this->messages);
    }

    /**
     * Get the instance as an array.
     *
     * 将实例作为数组获取
     *
     * @return array
     */
    public function toArray()
    {
        //         获取容器中的原始消息
        return $this->getMessages();
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * 将对象转换为JSON可序列化的对象
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();//将实例作为数组获取
    }

    /**
     * Convert the object to its JSON representation.
     *
     * 将对象转换为JSON表示
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        //                 将对象转换为JSON可序列化的对象
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the message bag to its string representation.
     *
     * 将消息包转换为字符串表示
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();//将对象转换为JSON表示
    }
}
