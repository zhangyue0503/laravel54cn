<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mail Driver 邮件驱动
    |--------------------------------------------------------------------------
    |
    | Laravel supports both SMTP and PHP's "mail" function as drivers for the
    | sending of e-mail. You may specify which one you're using throughout
    | your application here. By default, Laravel is setup for SMTP mail.
    |
    | Laravel支持SMTP和PHP的“mail”功能作为发送电子邮件的驱动程序。您可以指定在您的应用程序中使用的是哪一个。默认情况下，Laravel是SMTP邮件的设置
    |
    | Supported: "smtp", "sendmail", "mailgun", "mandrill", "ses",
    |            "sparkpost", "log", "array"
    |
    */

    'driver' => env('MAIL_DRIVER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | SMTP Host Address SMTP主机地址
    |--------------------------------------------------------------------------
    |
    | Here you may provide the host address of the SMTP server used by your
    | applications. A default option is provided that is compatible with
    | the Mailgun mail service which will provide reliable deliveries.
    |
    | 在这里，您可以提供应用程序使用的SMTP服务器的主机地址。默认选项是与Mailgun邮件服务兼容，后者将提供可靠的交付
    |
    */

    'host' => env('MAIL_HOST', 'smtp.mailgun.org'),

    /*
    |--------------------------------------------------------------------------
    | SMTP Host Port SMTP主机端口
    |--------------------------------------------------------------------------
    |
    | This is the SMTP port used by your application to deliver e-mails to
    | users of the application. Like the host we have set this value to
    | stay compatible with the Mailgun e-mail application by default.
    |
    | 这是应用程序使用的SMTP端口，用于向应用程序的用户发送电子邮件。像主机一样，我们设置了这个值，以保持默认的Mailgun电子邮件应用程序的兼容性
    |
    */

    'port' => env('MAIL_PORT', 587),

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address 全局“From”地址
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    | 您可能希望您的应用程序发送的所有电子邮件都从同一个地址发送。在这里，您可以指定应用程序发送的所有电子邮件在全球使用的名称和地址
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    /*
    |--------------------------------------------------------------------------
    | E-Mail Encryption Protocol 电子邮件Encryption议定书
    |--------------------------------------------------------------------------
    |
    | Here you may specify the encryption protocol that should be used when
    | the application send e-mail messages. A sensible default using the
    | transport layer security protocol should provide great security.
    |
    | 在这里，您可以指定应用程序发送电子邮件时应该使用的加密协议。使用传输层安全协议的明智的默认设置应该提供极大的安全性
    |
    */

    'encryption' => env('MAIL_ENCRYPTION', 'tls'),

    /*
    |--------------------------------------------------------------------------
    | SMTP Server Username SMTP服务器的用户名
    |--------------------------------------------------------------------------
    |
    | If your SMTP server requires a username for authentication, you should
    | set it here. This will get used to authenticate with your server on
    | connection. You may also set the "password" value below this one.
    |
    | 如果您的SMTP服务器需要用户名进行身份验证，那么您应该在这里设置它。这将用于在连接上与服务器进行身份验证。您还可以将“密码”值设置在下面
    |
    */

    'username' => env('MAIL_USERNAME'),

    'password' => env('MAIL_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Sendmail System Path Sendmail系统路径
    |--------------------------------------------------------------------------
    |
    | When using the "sendmail" driver to send e-mails, we will need to know
    | the path to where Sendmail lives on this server. A default path has
    | been provided here, which will work well on most of your systems.
    |
    | 当使用“sendmail”驱动程序发送电子邮件时，我们需要知道发送sendmail在该服务器上的路径。这里提供了一条默认路径，这将在您的大多数系统上运行良好
    |
    */

    'sendmail' => '/usr/sbin/sendmail -bs',

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings Markdown邮件设置
    |--------------------------------------------------------------------------
    |
    | If you are using Markdown based email rendering, you may configure your
    | theme and component paths here, allowing you to customize the design
    | of the emails. Or, you may simply stick with the Laravel defaults!
    |
    | 如果你使用的是Markdown的电子邮件渲染，你可以在这里配置你的主题和组件路径，允许你定制邮件的设计。或者，你可以简单地坚持使用Laravel默认值
    |
    */

    'markdown' => [
        'theme' => 'default',

        'paths' => [
            //获取资源文件夹的路径
            resource_path('views/vendor/mail'),
        ],
    ],

];
