<?php

namespace Illuminate\Notifications\Channels;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackAttachmentField;

class SlackWebhookChannel
{
    /**
     * The HTTP client instance.
     *
     * HTTP客户端实例
     *
     * @var \GuzzleHttp\Client
     */
    protected $http;

    /**
     * Create a new Slack channel instance.
     *
     * 创建一个新的Slack频道实例
     *
     * @param  \GuzzleHttp\Client  $http
     * @return void
     */
    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Send the given notification.
     *
     * 发送给定的通知
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send($notifiable, Notification $notification)
    {
        //                      获取给定驱动程序的通知路由信息
        if (! $url = $notifiable->routeNotificationFor('slack')) {
            return;
        }
        //                               为Slack的webhook构建一个JSON有效负载
        $this->http->post($url, $this->buildJsonPayload(
            $notification->toSlack($notifiable)
        ));
    }

    /**
     * Build up a JSON payload for the Slack webhook.
     *
     * 为Slack的webhook构建一个JSON有效负载
     *
     * @param  \Illuminate\Notifications\Messages\SlackMessage  $message
     * @return array
     */
    protected function buildJsonPayload(SlackMessage $message)
    {
        $optionalFields = array_filter([
            //          使用“点”符号从数组或对象中获取项
            'username' => data_get($message, 'username'),
            'icon_emoji' => data_get($message, 'icon'),
            'icon_url' => data_get($message, 'image'),
            'channel' => data_get($message, 'channel'),
        ]);

        return array_merge([
            'json' => array_merge([
                'text' => $message->content,
                'attachments' => $this->attachments($message),//格式消息的附件
            ], $optionalFields),
        ], $message->http);
    }

    /**
     * Format the message's attachments.
     *
     * 格式消息的附件
     *
     * @param  \Illuminate\Notifications\Messages\SlackMessage  $message
     * @return array
     */
    protected function attachments(SlackMessage $message)
    {
        //                                    在每个项目上运行map
        return collect($message->attachments)->map(function ($attachment) use ($message) {
            return array_filter([
                //                                  获取消息的颜色
                'color' => $attachment->color ?: $message->color(),
                'title' => $attachment->title,
                'text' => $attachment->content,
                'fallback' => $attachment->fallback,
                'title_link' => $attachment->url,
                'fields' => $this->fields($attachment),//附件格式的字段
                'mrkdwn_in' => $attachment->markdown,
                'footer' => $attachment->footer,
                'footer_icon' => $attachment->footerIcon,
                'ts' => $attachment->timestamp,
            ]);
        })->all();//获取集合中的所有项目
    }

    /**
     * Format the attachment's fields.
     *
     * 附件格式的字段
     *
     * @param  \Illuminate\Notifications\Messages\SlackAttachment  $attachment
     * @return array
     */
    protected function fields(SlackAttachment $attachment)
    {
        //                                   在每个项目上运行map
        return collect($attachment->fields)->map(function ($value, $key) {
            if ($value instanceof SlackAttachmentField) {
                return $value->toArray();//获取附件字段的数组表示
            }

            return ['title' => $key, 'value' => $value, 'short' => true];
        })->values()->all();//重置基础阵列上的键->获取集合中的所有项目
    }
}
