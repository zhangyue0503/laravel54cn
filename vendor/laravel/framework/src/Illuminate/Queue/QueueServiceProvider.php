<?php

namespace Illuminate\Queue;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\Connectors\NullConnector;
use Illuminate\Queue\Connectors\SyncConnector;
use Illuminate\Queue\Connectors\RedisConnector;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\Connectors\DatabaseConnector;
use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Queue\Connectors\BeanstalkdConnector;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;

class QueueServiceProvider extends ServiceProvider
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
        $this->registerManager();//注册队列管理器

        $this->registerConnection();//注册缺省队列连接绑定

        $this->registerWorker();//注册队列工人

        $this->registerListener();//注册队列监听器

        $this->registerFailedJobServices();//注册失败的作业服务
    }

    /**
     * Register the queue manager.
	 *
	 * 注册队列管理器
     *
     * @return void
     */
    protected function registerManager()
    {
		//      在容器中注册共享绑定
        $this->app->singleton('queue', function ($app) {
            // Once we have an instance of the queue manager, we will register the various
			// resolvers for the queue connectors. These connectors are responsible for
			// creating the classes that accept queue configs and instantiate queues.
			//
			// 一旦我们的队列管理器的一个实例，我们将登记为队列连接各种解析器
			// 这些连接器负责创造，接受队列配置和实例化的类队列
			//
			// 用给定的值调用给定的闭包，然后返回值(创建一个新的队列管理器实例)
            return tap(new QueueManager($app), function ($manager) {
                $this->registerConnectors($manager);//在队列管理器上注册连接器
            });
        });
    }

    /**
     * Register the default queue connection binding.
     *
     * 注册缺省队列连接绑定
     *
     * @return void
     */
    protected function registerConnection()
    {
        //在容器中注册共享绑定
        $this->app->singleton('queue.connection', function ($app) {
            return $app['queue']->connection();//解析队列连接实例
        });
    }

    /**
     * Register the connectors on the queue manager.
	 *
	 * 在队列管理器上注册连接器
	 * * 注册消息队列中控制器的连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    public function registerConnectors($manager)
    {
        foreach (['Null', 'Sync', 'Database', 'Redis', 'Beanstalkd', 'Sqs'] as $connector) {
            $this->{"register{$connector}Connector"}($manager);
        }
    }

    /**
     * Register the Null queue connector.
     * 注册空队列连接器
     *
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerNullConnector($manager)
    {
        //添加队列连接解析器
        $manager->addConnector('null', function () {
            return new NullConnector;
        });
    }

    /**
     * Register the Sync queue connector.
	 *
	 * 注册同步队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerSyncConnector($manager)
    {
		//    添加队列连接解析器
        $manager->addConnector('sync', function () {
            return new SyncConnector;
        });
    }

    /**
     * Register the database queue connector.
	 *
	 * 注册数据库队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerDatabaseConnector($manager)
    {
		//      添加队列连接解析器
        $manager->addConnector('database', function () {
            return new DatabaseConnector($this->app['db']);
        });
    }

    /**
     * Register the Redis queue connector.
     *
     * 注册Redis队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerRedisConnector($manager)
    {
        //添加队列连接解析器
        $manager->addConnector('redis', function () {
            return new RedisConnector($this->app['redis']);
        });
    }

    /**
     * Register the Beanstalkd queue connector.
     *
     * 注册Beanstalkd队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerBeanstalkdConnector($manager)
    {
        //添加队列连接解析器
        $manager->addConnector('beanstalkd', function () {
            return new BeanstalkdConnector;
        });
    }

    /**
     * Register the Amazon SQS queue connector.
     *
     * 注册Amazon SQS队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerSqsConnector($manager)
    {
        //添加队列连接解析器
        $manager->addConnector('sqs', function () {
            return new SqsConnector;
        });
    }

    /**
     * Register the queue worker.
     *
     * 注册队列工人
     *
     * @return void
     */
    protected function registerWorker()
    {
        //在容器中注册共享绑定
        $this->app->singleton('queue.worker', function () {
            return new Worker(
                $this->app['queue'], $this->app['events'], $this->app[ExceptionHandler::class]
            );
        });
    }

    /**
     * Register the queue listener.
     *
     * 注册队列监听器
     *
     * @return void
     */
    protected function registerListener()
    {
        //在容器中注册共享绑定
        $this->app->singleton('queue.listener', function () {
            //                         得到Laravel安装的基本路径
            return new Listener($this->app->basePath());
        });
    }

    /**
     * Register the failed job services.
     *
     * 注册失败的作业服务
     *
     * @return void
     */
    protected function registerFailedJobServices()
    {
        //在容器中注册共享绑定
        $this->app->singleton('queue.failer', function () {
            $config = $this->app['config']['queue.failed'];

            return isset($config['table'])
            //                  创建一个新的数据库失败的作业提供者
                        ? $this->databaseFailedJobProvider($config)
                        : new NullFailedJobProvider;
        });
    }

    /**
     * Create a new database failed job provider.
     *
     * 创建一个新的数据库失败的作业提供者
     *
     * @param  array  $config
     * @return \Illuminate\Queue\Failed\DatabaseFailedJobProvider
     */
    protected function databaseFailedJobProvider($config)
    {
        return new DatabaseFailedJobProvider(
            $this->app['db'], $config['database'], $config['table']
        );
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
            'queue', 'queue.worker', 'queue.listener',
            'queue.failer', 'queue.connection',
        ];
    }
}
