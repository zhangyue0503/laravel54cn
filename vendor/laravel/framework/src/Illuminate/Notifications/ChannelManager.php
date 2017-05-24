<?php

namespace Illuminate\Notifications;

use Illuminate\Mail\Markdown;
use InvalidArgumentException;
use Illuminate\Support\Manager;
use Nexmo\Client as NexmoClient;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Nexmo\Client\Credentials\Basic as NexmoCredentials;
use Illuminate\Contracts\Notifications\Factory as FactoryContract;
use Illuminate\Contracts\Notifications\Dispatcher as DispatcherContract;
//频道管理器
class ChannelManager extends Manager implements DispatcherContract, FactoryContract
{
    /**
     * The default channel used to deliver messages.
     *
     * 用于传递消息的默认通道
     *
     * @var string
     */
    protected $defaultChannel = 'mail';

    /**
     * Send the given notification to the given notifiable entities.
     *
     * 将给定的通知发送给指定的通知实体
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification)
    {
        //           创建一个新的通知发送方实例
        return (new NotificationSender(
            //            从容器中解析给定类型
            $this, $this->app->make(Bus::class), $this->app->make(Dispatcher::class))
        )->send($notifiables, $notification);//将给定的通知发送给指定的通知实体
    }

    /**
     * Send the given notification immediately.
     *
     * 立即发送通知
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @param  array|null  $channels
     * @return void
     */
    public function sendNow($notifiables, $notification, array $channels = null)
    {
        //           创建一个新的通知发送方实例
        return (new NotificationSender(
        //            从容器中解析给定类型
            $this, $this->app->make(Bus::class), $this->app->make(Dispatcher::class))
        )->sendNow($notifiables, $notification, $channels);//立即发送通知
    }

    /**
     * Get a channel instance.
     *
     * 获取频道实例
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function channel($name = null)
    {
        //          获取驱动实例
        return $this->driver($name);
    }

    /**
     * Create an instance of the database driver.
     *
     * 创建数据库驱动程序的实例
     *
     * @return \Illuminate\Notifications\Channels\DatabaseChannel
     */
    protected function createDatabaseDriver()
    {
        //             从容器中解析给定类型
        return $this->app->make(Channels\DatabaseChannel::class);
    }

    /**
     * Create an instance of the broadcast driver.
     *
     * 创建广播驱动程序的实例
     *
     * @return \Illuminate\Notifications\Channels\BroadcastChannel
     */
    protected function createBroadcastDriver()
    {
        //             从容器中解析给定类型
        return $this->app->make(Channels\BroadcastChannel::class);
    }

    /**
     * Create an instance of the mail driver.
     *
     * 创建邮件驱动程序的实例
     *
     * @return \Illuminate\Notifications\Channels\MailChannel
     */
    protected function createMailDriver()
    {
        //             从容器中解析给定类型                      设置Markdown解析器回调
        return $this->app->make(Channels\MailChannel::class)->setMarkdownResolver(function () {
            return $this->app->make(Markdown::class);
        });
    }

    /**
     * Create an instance of the Nexmo driver.
     *
     * 创建Nexmo驱动程序的实例
     *
     * @return \Illuminate\Notifications\Channels\NexmoSmsChannel
     */
    protected function createNexmoDriver()
    {
        return new Channels\NexmoSmsChannel(
            new NexmoClient(new NexmoCredentials(
                $this->app['config']['services.nexmo.key'],
                $this->app['config']['services.nexmo.secret']
            )),
            $this->app['config']['services.nexmo.sms_from']
        );
    }

    /**
     * Create an instance of the Slack driver.
     *
     * 创建Slack驱动程序的实例
     *
     * @return \Illuminate\Notifications\Channels\SlackWebhookChannel
     */
    protected function createSlackDriver()
    {
        return new Channels\SlackWebhookChannel(new HttpClient);
    }

    /**
     * Create a new driver instance.
     *
     * 创建一个新的驱动程序实例
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        try {
            //             创建一个新的驱动实例
            return parent::createDriver($driver);
        } catch (InvalidArgumentException $e) {
            if (class_exists($driver)) {
                //            从容器中解析给定类型
                return $this->app->make($driver);
            }

            throw $e;
        }
    }

    /**
     * Get the default channel driver name.
     *
     * 获取默认的频道驱动程序名称
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->defaultChannel;
    }

    /**
     * Get the default channel driver name.
     *
     * 获取默认的频道驱动程序名称
     *
     * @return string
     */
    public function deliversVia()
    {
        //           获取默认的频道驱动程序名称
        return $this->getDefaultDriver();
    }

    /**
     * Set the default channel driver name.
     *
     * 设置默认的频道驱动程序名称
     *
     * @param  string  $channel
     * @return void
     */
    public function deliverVia($channel)
    {
        $this->defaultChannel = $channel;
    }
}
