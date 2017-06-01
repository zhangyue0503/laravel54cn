<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults  验证默认值
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    | 此选项控制应用程序的默认身份验证“警卫”和密码重置选项
    | 您可能会根据需要更改这些默认值，但对于大多数应用程序来说，它们是一个完美的开始
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards  身份验证警卫
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the Eloquent user provider.
    |
    | 接下来，您可以为应用程序定义每个身份验证保护。当然，这里已经为您定义了一个非常好的默认配置，它使用会话存储和雄辩的用户提供程序
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | 所有身份验证驱动程序都有一个用户提供程序。这定义了如何从数据库或其他存储机制中检索用户，以保存用户的数据
    |
    | Supported: "session", "token"
    | 支持
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'token',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers 用户服务提供者
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | 所有身份验证驱动程序都有一个用户提供程序。这定义了如何从数据库或其他存储机制中检索用户，以保存用户的数据
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any extra authentication guards you have defined.
    |
    | 如果您有多个用户表或模型，您可以配置多个源来表示每个模型/表。然后，这些源可能被分配给您定义的任何额外的身份验证守卫
    |
    | Supported: "database", "eloquent"
    | 支持
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\User::class,
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords 重置密码
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | 如果您在应用程序中有多个用户表或模型，您可以指定多个密码重置配置，并且您希望根据特定的用户类型设置单独的密码重置设置
    |
    | The expire time is the number of minutes that the reset token should be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | 过期时间是重置令牌应该被认为有效的分钟数。这种安全特性使令牌持续时间很短，因此没有多少时间可以猜测。您可以根据需要更改这个
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
        ],
    ],

];
