<?php

namespace Illuminate\Mail;

use Swift_Mailer;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * 指示是否延迟了提供者的加载
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerSwiftMailer();//注册Swift Mailer的实例

        $this->registerIlluminateMailer();//注册Illuminate邮件实例

        $this->registerMarkdownRenderer();//注册Markdown渲染器实例
    }

    /**
     * Register the Illuminate mailer instance.
     *
     * 注册Illuminate邮件实例
     *
     * @return void
     */
    protected function registerIlluminateMailer()
    {
        //在容器中注册共享绑定
        $this->app->singleton('mailer', function ($app) {
            //从容器中解析给定类型               获取闭包以从容器中解析给定类型
            $config = $app->make('config')->get('mail');

            // Once we have create the mailer instance, we will set a container instance
            // on the mailer. This allows us to resolve mailer classes via containers
            // for maximum testability on said classes instead of passing Closures.
            //
            // 一旦我们创建了mailer实例，我们将在邮件器上设置一个容器实例
            // 这使我们可以通过容器来解决邮件类的问题，而不是通过闭包来实现最大的可测试性
            //
            $mailer = new Mailer(
                $app['view'], $app['swift.mailer'], $app['events']
            );
            //确定给定的抽象类型是否已绑定
            if ($app->bound('queue')) {
                $mailer->setQueue($app['queue']);//设置队列管理器实例
            }

            // Next we will set all of the global addresses on this mailer, which allows
            // for easy unification of all "from" addresses as well as easy debugging
            // of sent messages since they get be sent into a single email address.
            //
            // 接下来，我们将在这个邮件中设置所有的全局地址，这样就可以方便地统一所有“来自”地址，也可以方便地调试发送的消息，因为它们被发送到一个单独的电子邮件地址
            //
            foreach (['from', 'reply_to', 'to'] as $type) {
                //按类型在邮件上设置一个全局地址
                $this->setGlobalAddress($mailer, $config, $type);
            }

            return $mailer;
        });
    }

    /**
     * Set a global address on the mailer by type.
     *
     * 按类型在邮件上设置一个全局地址
     *
     * @param  \Illuminate\Mail\Mailer  $mailer
     * @param  array  $config
     * @param  string  $type
     * @return void
     */
    protected function setGlobalAddress($mailer, array $config, $type)
    {
        //           使用“点”符号从数组中获取一个项
        $address = Arr::get($config, $type);

        if (is_array($address) && isset($address['address'])) {
            //                将值转换为大驼峰
            $mailer->{'always'.Str::studly($type)}($address['address'], $address['name']);
        }
    }

    /**
     * Register the Swift Mailer instance.
     *
     * 注册Swift Mailer的实例
     *
     * @return void
     */
    public function registerSwiftMailer()
    {
        $this->registerSwiftTransport();//注册Swift传输实例

        // Once we have the transporter registered, we will register the actual Swift
        // mailer instance, passing in the transport instances, which allows us to
        // override this transporter instances during app start-up if necessary.
        //
        // 一旦注册了传输器，我们将注册实际的Swift邮件实例，通过传输实例，这允许我们在必要时在应用启动时覆盖这个传输实例
        //
        //在容器中注册共享绑定
        $this->app->singleton('swift.mailer', function ($app) {
            return new Swift_Mailer($app['swift.transport']->driver());
        });
    }

    /**
     * Register the Swift Transport instance.
     *
     * 注册Swift传输实例
     *
     * @return void
     */
    protected function registerSwiftTransport()
    {
        //在容器中注册共享绑定
        $this->app->singleton('swift.transport', function ($app) {
            return new TransportManager($app);
        });
    }

    /**
     * Register the Markdown renderer instance.
     *
     * 注册Markdown渲染器实例
     *
     * @return void
     */
    protected function registerMarkdownRenderer()
    {
        //确定我们是否在控制台中运行
        if ($this->app->runningInConsole()) {
            //注册发布命令发布的路径
            $this->publishes([
                __DIR__.'/resources/views' => resource_path('views/vendor/mail'),
            ], 'laravel-mail');
        }
        //在容器中注册共享绑定
        $this->app->singleton(Markdown::class, function () {
            //                          从容器中解析给定类型
            return new Markdown($this->app->make('view'), [
                'theme' => config('mail.markdown.theme', 'default'),
                'paths' => config('mail.markdown.paths', []),
            ]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * 获取提供者提供的服务
     *
     * @return array
     */
    public function provides()
    {
        return [
            'mailer', 'swift.mailer', 'swift.transport', Markdown::class,
        ];
    }
}
