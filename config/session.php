<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver 默认Session驱动
    |--------------------------------------------------------------------------
    |
    | This option controls the default session "driver" that will be used on
    | requests. By default, we will use the lightweight native driver but
    | you may specify any of the other wonderful drivers provided here.
    |
    | 此选项控制将用于请求的默认会话“驱动程序”。默认情况下，我们将使用轻量级的本地驱动程序，但您可以指定这里提供的其他优秀驱动程序
    |
    | Supported: "file", "cookie", "database", "apc",
    |            "memcached", "redis", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime 会话的生命周期
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires. If you want them
    | to immediately expire on the browser closing, set that option.
    |
    | 在这里，您可以指定希望会话在到期之前保持空闲的分钟数。如果您希望它们立即在浏览器关闭时过期，设置该选项
    |
    */

    'lifetime' => 120,

    'expire_on_close' => false,

    /*
    |--------------------------------------------------------------------------
    | Session Encryption 会话加密
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify that all of your session data
    | should be encrypted before it is stored. All encryption will be run
    | automatically by Laravel and you can use the Session like normal.
    |
    | 这个选项允许您轻松地指定所有会话数据在存储之前都应该被加密。所有加密都将由Laravel自动运行，并且您可以使用正常的会话
    |
    */

    'encrypt' => false,

    /*
    |--------------------------------------------------------------------------
    | Session File Location 会话文件位置
    |--------------------------------------------------------------------------
    |
    | When using the native session driver, we need a location where session
    | files may be stored. A default has been set for you but a different
    | location may be specified. This is only needed for file sessions.
    |
    | 当使用本机会话驱动程序时，我们需要一个可以存储会话文件的位置。已经为您设置了默认值，但是可以指定不同的位置。这只需要进行文件会话
    |
    */
    //          获取存储文件夹的路径
    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection 会话数据库连接
    |--------------------------------------------------------------------------
    |
    | When using the "database" or "redis" session drivers, you may specify a
    | connection that should be used to manage these sessions. This should
    | correspond to a connection in your database configuration options.
    |
    | 当使用“数据库”或“redis”会话驱动程序时，您可以指定应该用于管理这些会话的连接。这应该与数据库配置选项中的连接相对应
    |
    */

    'connection' => null,

    /*
    |--------------------------------------------------------------------------
    | Session Database Table 会话的数据库表
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the table we
    | should use to manage the sessions. Of course, a sensible default is
    | provided for you; however, you are free to change this as needed.
    |
    | 在使用“数据库”会话驱动程序时，可以指定要使用的表来管理会话。当然，为您提供了一个明智的默认值;但是，您可以根据需要随意更改
    |
    */

    'table' => 'sessions',

    /*
    |--------------------------------------------------------------------------
    | Session Cache Store 会话缓存存储
    |--------------------------------------------------------------------------
    |
    | When using the "apc" or "memcached" session drivers, you may specify a
    | cache store that should be used for these sessions. This value must
    | correspond with one of the application's configured cache stores.
    |
    | 当使用“apc”或“memcached”会话驱动程序时，您可以指定应该用于这些会话的缓存存储。此值必须与应用程序配置的缓存存储区相对应
    |
    */

    'store' => null,

    /*
    |--------------------------------------------------------------------------
    | Session Sweeping Lottery 会话全面清除机率
    |--------------------------------------------------------------------------
    |
    | Some session drivers must manually sweep their storage location to get
    | rid of old sessions from storage. Here are the chances that it will
    | happen on a given request. By default, the odds are 2 out of 100.
    |
    | 一些会话驱动程序必须手动清除它们的存储位置，以便从存储中删除旧会话。这里有可能发生在给定的请求上。默认情况下，概率是2 / 100
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name 会话cookie名
    |--------------------------------------------------------------------------
    |
    | Here you may change the name of the cookie used to identify a session
    | instance by ID. The name specified here will get used every time a
    | new session cookie is created by the framework for every driver.
    |
    | 在这里，您可以更改使用ID来标识会话实例的cookie的名称。这里指定的名称将在每次驱动程序框架创建的新会话cookie中得到使用
    |
    */

    'cookie' => 'laravel_session',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path 会话cookie路径
    |--------------------------------------------------------------------------
    |
    | The session cookie path determines the path for which the cookie will
    | be regarded as available. Typically, this will be the root path of
    | your application but you are free to change this when necessary.
    |
    | 会话cookie路径决定了cookie将被视为可用的路径。通常情况下，这将是应用程序的根路径，但在必要时可以自由更改此路径
    |
    */

    'path' => '/',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain 会话cookie域名
    |--------------------------------------------------------------------------
    |
    | Here you may change the domain of the cookie used to identify a session
    | in your application. This will determine which domains the cookie is
    | available to in your application. A sensible default has been set.
    |
    | 在这里，您可以更改用于标识应用程序中的会话的cookie的域。这将决定在您的应用程序中可以使用cookie的哪些域。已经制定了明智的违约
    |
    */

    'domain' => env('SESSION_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    |
    | By setting this option to true, session cookies will only be sent back
    | to the server if the browser has a HTTPS connection. This will keep
    | the cookie from being sent to you if it can not be done securely.
    |
    | 通过将这个选项设置为true，如果浏览器有HTTPS连接，会话cookie将只被发送回服务器。如果不能安全地完成，这将保持cookie不被发送给您
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will prevent JavaScript from accessing the
    | value of the cookie and the cookie will only be accessible through
    | the HTTP protocol. You are free to modify this option if needed.
    |
    | 将此值设置为true将阻止JavaScript访问cookie的值，而cookie只能通过HTTP协议访问。如果需要，您可以自由修改此选项
    |
    */

    'http_only' => true,

];
