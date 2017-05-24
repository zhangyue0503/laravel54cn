<?php

namespace Illuminate\Mail;

use Aws\Ses\SesClient;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Manager;
use GuzzleHttp\Client as HttpClient;
use Swift_MailTransport as MailTransport;
use Swift_SmtpTransport as SmtpTransport;
use Illuminate\Mail\Transport\LogTransport;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Mail\Transport\MailgunTransport;
use Illuminate\Mail\Transport\MandrillTransport;
use Illuminate\Mail\Transport\SparkPostTransport;
use Swift_SendmailTransport as SendmailTransport;

class TransportManager extends Manager
{
    /**
     * Create an instance of the SMTP Swift Transport driver.
     *
     * 创建一个SMTP快速传输驱动程序的实例
     *
     * @return \Swift_SmtpTransport
     */
    protected function createSmtpDriver()
    {
        //            从容器中解析给定类型       获取指定的配置值
        $config = $this->app->make('config')->get('mail');

        // The Swift SMTP transport instance will allow us to use any SMTP backend
        // for delivering mail such as Sendgrid, Amazon SES, or a custom server
        // a developer has available. We will just pass this configured host.
        //
        // Swift SMTP传输实例将允许我们使用任何SMTP后端来发送邮件，如Sendgrid、Amazon SES或开发人员提供的自定义服务器
        // 我们将通过这个已配置的主机
        //
        //              创建一个新的SmtpTransport传输实例
        $transport = SmtpTransport::newInstance(
            $config['host'], $config['port']
        );

        if (isset($config['encryption'])) {
            //设置加密类型(tls或ssl)
            $transport->setEncryption($config['encryption']);
        }

        // Once we have the transport we will check for the presence of a username
        // and password. If we have it we will set the credentials on the Swift
        // transporter instance so that we'll properly authenticate delivery.
        //
        // 一旦我们有了传输，我们将检查用户名和密码的存在
        // 如果我们拥有它，我们将在Swift传输器实例上设置凭据，这样我们就能正确地对交付进行身份验证
        //
        if (isset($config['username'])) {
            //设置用户名以进行身份验证
            $transport->setUsername($config['username']);
            //设置密码以进行身份验证
            $transport->setPassword($config['password']);
        }

        // Next we will set any stream context options specified for the transport
        // and then return it. The option is not required any may not be inside
        // the configuration array at all so we'll verify that before adding.
        //
        // 接下来，我们将为传输指定任何流上下文选项，然后返回它
        // 该选项不需要任何可能不在配置数组中，因此在添加之前我们将验证这个选项
        //
        if (isset($config['stream'])) {
            //         设置流上下文选项
            $transport->setStreamOptions($config['stream']);
        }

        return $transport;
    }

    /**
     * Create an instance of the Sendmail Swift Transport driver.
     *
     * 创建Sendmail快速传输驱动程序的实例
     *
     * @return \Swift_SendmailTransport
     */
    protected function createSendmailDriver()
    {
        //创建一个新的SendmailTransport传输实例
        return SendmailTransport::newInstance(
            $this->app['config']['mail']['sendmail']
        );
    }

    /**
     * Create an instance of the Amazon SES Swift Transport driver.
     *
     * 创建Amazon Swift传输驱动程序的实例
     *
     * @return \Swift_SendmailTransport
     */
    protected function createSesDriver()
    {
        $config = array_merge($this->app['config']->get('services.ses', []), [
            'version' => 'latest', 'service' => 'email',
        ]);
        //创建一个新的SES传输实例
        return new SesTransport(new SesClient(
            //将SES证书添加到配置数组中
            $this->addSesCredentials($config)
        ));
    }

    /**
     * Add the SES credentials to the configuration array.
     *
     * 将SES证书添加到配置数组中
     *
     * @param  array  $config
     * @return array
     */
    protected function addSesCredentials(array $config)
    {
        if ($config['key'] && $config['secret']) {
            //                          从给定数组中获取项目的子集
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }

        return $config;
    }

    /**
     * Create an instance of the Mail Swift Transport driver.
     *
     * 创建一个邮件Swift传输驱动程序的实例
     *
     * @return \Swift_MailTransport
     */
    protected function createMailDriver()
    {
        //创建一个新的MailTransport实例
        return MailTransport::newInstance();
    }

    /**
     * Create an instance of the Mailgun Swift Transport driver.
     *
     * 创建一个Mailgun Swift传输驱动程序的实例
     *
     * @return \Illuminate\Mail\Transport\MailgunTransport
     */
    protected function createMailgunDriver()
    {
        $config = $this->app['config']->get('services.mailgun', []);
        //创建一个新的Mailgun传输实例
        return new MailgunTransport(
            $this->guzzle($config),//获取一个新的HTTP客户端实例
            $config['secret'], $config['domain']
        );
    }

    /**
     * Create an instance of the Mandrill Swift Transport driver.
     *
     * 创建一个Mandrill Swift传输驱动程序的实例
     *
     * @return \Illuminate\Mail\Transport\MandrillTransport
     */
    protected function createMandrillDriver()
    {
        $config = $this->app['config']->get('services.mandrill', []);
        //创建一个新的Mandrill传输实例
        return new MandrillTransport(
            //获取一个新的HTTP客户端实例
            $this->guzzle($config), $config['secret']
        );
    }

    /**
     * Create an instance of the SparkPost Swift Transport driver.
     *
     * 创建一个SparkPost Swift传输驱动程序的实例
     *
     * @return \Illuminate\Mail\Transport\SparkPostTransport
     */
    protected function createSparkPostDriver()
    {
        $config = $this->app['config']->get('services.sparkpost', []);
        //创建一个新的SparkPost传输实例
        return new SparkPostTransport(
        //获取一个新的HTTP客户端实例                        使用“点”符号从数组中获取一个项
            $this->guzzle($config), $config['secret'], Arr::get($config, 'options', [])
        );
    }

    /**
     * Create an instance of the Log Swift Transport driver.
     *
     * 创建一个日志Swift传输驱动程序的实例
     *
     * @return \Illuminate\Mail\Transport\LogTransport
     */
    protected function createLogDriver()
    {
        //   创建一个新的日志传输实例     从容器中解析给定类型
        return new LogTransport($this->app->make(LoggerInterface::class));
    }

    /**
     * Create an instance of the Array Swift Transport Driver.
     *
     * 创建一个数组Swift传输驱动程序的实例
     *
     * @return \Illuminate\Mail\Transport\ArrayTransport
     */
    protected function createArrayDriver()
    {
        //创建一个新的数组传输实例
        return new ArrayTransport;
    }

    /**
     * Get a fresh Guzzle HTTP client instance.
     *
     * 获取一个新的HTTP客户端实例
     *
     * @param  array  $config
     * @return \GuzzleHttp\Client
     */
    protected function guzzle($config)
    {
        //                        如果不存在，使用“点”表示法将一个元素添加到数组中
        return new HttpClient(Arr::add(
            //使用“点”符号从数组中获取一个项
            Arr::get($config, 'guzzle', []), 'connect_timeout', 60
        ));
    }

    /**
     * Get the default mail driver name.
     *
     * 获取默认的邮件驱动程序名称
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['mail.driver'];
    }

    /**
     * Set the default mail driver name.
     *
     * 设置默认的邮件驱动程序名称
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['mail.driver'] = $name;
    }
}
