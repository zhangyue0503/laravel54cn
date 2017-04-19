<?php

namespace Illuminate\Database;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Queue\EntityResolver;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Eloquent\QueueEntityResolver;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * 引导应用程序事件
     *
     * @return void
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']); // 设置连接解析器实例

        Model::setEventDispatcher($this->app['events']); // 设置事件调度实例
    }

    /**
     * Register the service provider.
     *
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        Model::clearBootedModels(); //清除启动模型的列表，以便重新启动它们

        $this->registerConnectionServices();//注册主数据库绑定

        $this->registerEloquentFactory(); //在容器中注册有Eloquent工厂实例

        $this->registerQueueableEntityResolver();//注册queueable实体解析器的实现
    }

    /**
     * Register the primary database bindings.
     *
     * 注册主数据库绑定
     *
     * @return void
     */
    protected function registerConnectionServices()
    {
        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        //
        // 连接工厂用于创建数据库上的实际连接实例
        // 我们将把工厂注入到管理器，以便它可以使连接，而他们实际上需要的，而不是以前
        //
        $this->app->singleton('db.factory', function ($app) { //在容器中注册共享绑定
            return new ConnectionFactory($app); //创建一个新的连接工厂实例
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        //
        // 数据库管理器用于解决各种连接，因为可以管理多个连接
        // 它还实现了连接解析器接口，可用于需要连接的其他组件
        //
        $this->app->singleton('db', function ($app) { //在容器中注册共享绑定
            return new DatabaseManager($app, $app['db.factory']); //数据库管理器
        });

        $this->app->bind('db.connection', function ($app) { //与容器注册绑定
            return $app['db']->connection();
        });
    }

    /**
     * Register the Eloquent factory instance in the container.
     *
     * 在容器中注册Eloquent工厂实例
     *
     * @return void
     */
    protected function registerEloquentFactory()
    {
        ////在容器中注册共享绑定
        $this->app->singleton(FakerGenerator::class, function ($app) {
            return FakerFactory::create($app['config']->get('app.faker_locale', 'en_US'));
        });
        ////在容器中注册共享绑定
        $this->app->singleton(EloquentFactory::class, function ($app) {
            return EloquentFactory::construct(
                $app->make(FakerGenerator::class), database_path('factories')
            );
        });
    }

    /**
     * Register the queueable entity resolver implementation.
     *
     * 注册queueable实体解析器的实现
     *
     * @return void
     */
    protected function registerQueueableEntityResolver()
    {
        ////在容器中注册共享绑定
        $this->app->singleton(EntityResolver::class, function () {
            return new QueueEntityResolver;
        });
    }
}
