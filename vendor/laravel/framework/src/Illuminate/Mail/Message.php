<?php

namespace Illuminate\Mail;

use Swift_Image;
use Swift_Attachment;

class Message
{
    /**
     * The Swift Message instance.
     *
     * Swift消息实例
     *
     * @var \Swift_Message
     */
    protected $swift;

    /**
     * CIDs of files embedded in the message.
     *
     * 嵌入在消息中的文件的CIDs
     *
     * @var array
     */
    protected $embeddedFiles = [];

    /**
     * Create a new message instance.
     *
     * 创建一个新的消息实例
     *
     * @param  \Swift_Message  $swift
     * @return void
     */
    public function __construct($swift)
    {
        $this->swift = $swift;
    }

    /**
     * Add a "from" address to the message.
     *
     * 将“from”地址添加到消息中
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function from($address, $name = null)
    {
        //设置该消息的地址
        $this->swift->setFrom($address, $name);

        return $this;
    }

    /**
     * Set the "sender" of the message.
     *
     * 设置消息的“发送者”
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function sender($address, $name = null)
    {
        // 设置此消息的发送者
        $this->swift->setSender($address, $name);

        return $this;
    }

    /**
     * Set the "return path" of the message.
     *
     * 设置消息的“返回路径”
     *
     * @param  string  $address
     * @return $this
     */
    public function returnPath($address)
    {
        //        设置该消息的返回路径(反弹地址)
        $this->swift->setReturnPath($address);

        return $this;
    }

    /**
     * Add a recipient to the message.
     *
     * 在消息中添加收件人
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @param  bool  $override
     * @return $this
     */
    public function to($address, $name = null, $override = false)
    {
        if ($override) {
            //            设置该消息的地址
            $this->swift->setTo($address, $name);

            return $this;
        }
        //在消息中添加收件人
        return $this->addAddresses($address, $name, 'To');
    }

    /**
     * Add a carbon copy to the message.
     *
     * 在消息中添加一个抄送拷贝
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @param  bool  $override
     * @return $this
     */
    public function cc($address, $name = null, $override = false)
    {
        if ($override) {
            //            设置该消息的cc地址
            $this->swift->setCc($address, $name);

            return $this;
        }
        //在消息中添加收件人
        return $this->addAddresses($address, $name, 'Cc');
    }

    /**
     * Add a blind carbon copy to the message.
     *
     * 在消息中添加一个抄送的副本
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @param  bool  $override
     * @return $this
     */
    public function bcc($address, $name = null, $override = false)
    {
        if ($override) {
            //            设置该消息的bcc地址
            $this->swift->setBcc($address, $name);

            return $this;
        }
        //在消息中添加收件人
        return $this->addAddresses($address, $name, 'Bcc');
    }

    /**
     * Add a reply to address to the message.
     *
     * 在消息中添加一个应答
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function replyTo($address, $name = null)
    {
        //在消息中添加收件人
        return $this->addAddresses($address, $name, 'ReplyTo');
    }

    /**
     * Add a recipient to the message.
     *
     * 在消息中添加收件人
     *
     * @param  string|array  $address
     * @param  string  $name
     * @param  string  $type
     * @return $this
     */
    protected function addAddresses($address, $name, $type)
    {
        if (is_array($address)) {
            $this->swift->{"set{$type}"}($address, $name);
        } else {
            $this->swift->{"add{$type}"}($address, $name);
        }

        return $this;
    }

    /**
     * Set the subject of the message.
     *
     * 设置消息的主题
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject($subject)
    {
        //         设置这个消息的主题
        $this->swift->setSubject($subject);

        return $this;
    }

    /**
     * Set the message priority level.
     *
     * 设置消息优先级级别
     *
     * @param  int  $level
     * @return $this
     */
    public function priority($level)
    {
        //        设置此消息的优先级
        $this->swift->setPriority($level);

        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * 将文件附加到消息中
     *
     * @param  string  $file
     * @param  array  $options
     * @return $this
     */
    public function attach($file, array $options = [])
    {
        //              创建一个Swift附件实例
        $attachment = $this->createAttachmentFromPath($file);
        //          准备并附加附件
        return $this->prepAttachment($attachment, $options);
    }

    /**
     * Create a Swift Attachment instance.
     *
     * 创建一个Swift附件实例
     *
     * @param  string  $file
     * @return \Swift_Attachment
     */
    protected function createAttachmentFromPath($file)
    {
        //                    从文件系统路径中创建一个新的附件
        return Swift_Attachment::fromPath($file);
    }

    /**
     * Attach in-memory data as an attachment.
     *
     * 将内存中的数据附加为附件
     *
     * @param  string  $data
     * @param  string  $name
     * @param  array  $options
     * @return $this
     */
    public function attachData($data, $name, array $options = [])
    {
        //              创建一个Swift附件实例
        $attachment = $this->createAttachmentFromData($data, $name);
        //              准备并附加附件
        return $this->prepAttachment($attachment, $options);
    }

    /**
     * Create a Swift Attachment instance from data.
     *
     * 从数据中创建一个快速附件实例
     *
     * @param  string  $data
     * @param  string  $name
     * @return \Swift_Attachment
     */
    protected function createAttachmentFromData($data, $name)
    {
        //                        创建一个新的附件实例
        return Swift_Attachment::newInstance($data, $name);
    }

    /**
     * Embed a file in the message and get the CID.
     *
     * 在消息中嵌入一个文件并获得CID
     *
     * @param  string  $file
     * @return string
     */
    public function embed($file)
    {
        if (isset($this->embeddedFiles[$file])) {
            return $this->embeddedFiles[$file];
        }
        //                                            连接一个@link swiftmimemimeentity并返回它的CID源
        return $this->embeddedFiles[$file] = $this->swift->embed(
            //从文件系统路径中创建一个新映像
            Swift_Image::fromPath($file)
        );
    }

    /**
     * Embed in-memory data in the message and get the CID.
     *
     * 将内存中的数据嵌入到消息中，并获得CID
     *
     * @param  string  $data
     * @param  string  $name
     * @param  string|null  $contentType
     * @return string
     */
    public function embedData($data, $name, $contentType = null)
    {
        //                  创建一个新的图片
        $image = Swift_Image::newInstance($data, $name, $contentType);
        //连接一个@link swiftmimemimeentity并返回它的CID源
        return $this->swift->embed($image);
    }

    /**
     * Prepare and attach the given attachment.
     *
     * 准备并附加附件
     *
     * @param  \Swift_Attachment  $attachment
     * @param  array  $options
     * @return $this
     */
    protected function prepAttachment($attachment, $options = [])
    {
        // First we will check for a MIME type on the message, which instructs the
        // mail client on what type of attachment the file is so that it may be
        // downloaded correctly by the user. The MIME option is not required.
        //
        // 首先，我们将检查消息上的MIME类型，它指示邮件客户机关于该文件是什么类型的附件，以便用户可以正确地下载它
        // MIME选项是不需要的
        //
        if (isset($options['mime'])) {
            //        设置该实体的内容类型
            $attachment->setContentType($options['mime']);
        }

        // If an alternative name was given as an option, we will set that on this
        // attachment so that it will be downloaded with the desired names from
        // the developer, otherwise the default file names will get assigned.
        //
        // 如果有一个替代的名字作为一个选项，我们会在这个附件上设置它，这样它就可以从开发人员的名字中下载，否则默认的文件名就会被分配
        //
        if (isset($options['as'])) {
            //设置该附件的文件名
            $attachment->setFilename($options['as']);
        }
        //连接一个@link swiftmimemimeentity，例如附件或mime部件
        $this->swift->attach($attachment);

        return $this;
    }

    /**
     * Get the underlying Swift Message instance.
     *
     * 获取底层的Swift消息实例
     *
     * @return \Swift_Message
     */
    public function getSwiftMessage()
    {
        return $this->swift;
    }

    /**
     * Dynamically pass missing methods to the Swift instance.
     *
     * 动态地将丢失的方法传递给Swift实例
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $callable = [$this->swift, $method];

        return call_user_func_array($callable, $parameters);
    }
}
