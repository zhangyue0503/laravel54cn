<?php

namespace Illuminate\Mail\Transport;

use Swift_Transport;
use Swift_Mime_Message;
use Swift_Events_SendEvent;
use Swift_Events_EventListener;

abstract class Transport implements Swift_Transport
{
    /**
     * The plug-ins registered with the transport.
     *
     * 在传输中注册的插件
     *
     * @var array
     */
    public $plugins = [];

    /**
     * {@inheritdoc}
     * 测试这个传输机制是否已经启动
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     * 启动这个传输机制
     */
    public function start()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     * 停止这个传输机制
     */
    public function stop()
    {
        return true;
    }

    /**
     * Register a plug-in with the transport.
     *
     * 使用传输注册一个插件
     *
     * @param  \Swift_Events_EventListener  $plugin
     * @return void
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        array_push($this->plugins, $plugin);
    }

    /**
     * Iterate through registered plugins and execute plugins' methods.
     *
     * 遍历已注册的插件并执行插件的方法
     *
     * @param  \Swift_Mime_Message  $message
     * @return void
     */
    protected function beforeSendPerformed(Swift_Mime_Message $message)
    {
        $event = new Swift_Events_SendEvent($this, $message);

        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, 'beforeSendPerformed')) {
                $plugin->beforeSendPerformed($event);
            }
        }
    }

    /**
     * Iterate through registered plugins and execute plugins' methods.
     *
     * 遍历已注册的插件并执行插件的方法
     *
     * @param  \Swift_Mime_Message  $message
     * @return void
     */
    protected function sendPerformed(Swift_Mime_Message $message)
    {
        $event = new Swift_Events_SendEvent($this, $message);

        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, 'sendPerformed')) {
                $plugin->sendPerformed($event);
            }
        }
    }

    /**
     * Get the number of recipients.
     *
     * 获得受助者的数量
     *
     * @param  \Swift_Mime_Message  $message
     * @return int
     */
    protected function numberOfRecipients(Swift_Mime_Message $message)
    {
        return count(array_merge(
            //          获取该消息的地址           获取该消息的CC地址               获取该消息的BCC地址
            (array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
        ));
    }
}
