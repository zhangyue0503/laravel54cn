<?php

namespace Illuminate\Foundation\Testing;

use Mockery;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Console\Application as Artisan;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use Concerns\InteractsWithContainer,
        Concerns\MakesHttpRequests,
        Concerns\InteractsWithAuthentication,
        Concerns\InteractsWithConsole,
        Concerns\InteractsWithDatabase,
        Concerns\InteractsWithSession,
        Concerns\MocksApplicationServices;

    /**
     * The Illuminate application instance.
     *
     * Illuminate应用程序实例
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The callbacks that should be run after the application is created.
     *
     * 回调后应该运行应用程序创建
     *
     * @var array
     */
    protected $afterApplicationCreatedCallbacks = [];

    /**
     * The callbacks that should be run before the application is destroyed.
     *
     * 在应用程序被销毁之前应该运行的回调
     *
     * @var array
     */
    protected $beforeApplicationDestroyedCallbacks = [];

    /**
     * Indicates if we have made it through the base setUp function.
     *
     * 如果我们通过基本设置函数来表示
     *
     * @var bool
     */
    protected $setUpHasRun = false;

    /**
     * Creates the application.
     *
     * 创建应用程序
     *
     * Needs to be implemented by subclasses.
     *
     * 需要由子类来实现
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    abstract public function createApplication();

    /**
     * Setup the test environment.
     *
     * 设置测试环境
     *
     * @return void
     */
    protected function setUp()
    {
        if (! $this->app) {
            //刷新应用程序实例
            $this->refreshApplication();
        }

        $this->setUpTraits();//启动测试助手特性

        foreach ($this->afterApplicationCreatedCallbacks as $callback) {
            call_user_func($callback);
        }
        //清除所有已解决的实例
        Facade::clearResolvedInstances();
        //设置事件调度器实例
        Model::setEventDispatcher($this->app['events']);

        $this->setUpHasRun = true;
    }

    /**
     * Refresh the application instance.
     *
     * 刷新应用程序实例
     *
     * @return void
     */
    protected function refreshApplication()
    {
        //                 创建应用程序
        $this->app = $this->createApplication();
    }

    /**
     * Boot the testing helper traits.
     *
     * 启动测试助手特性
     *
     * @return void
     */
    protected function setUpTraits()
    {
        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[DatabaseMigrations::class])) {
            $this->runDatabaseMigrations();//定义钩子在每次测试之前和之后迁移数据库
        }

        if (isset($uses[DatabaseTransactions::class])) {
            $this->beginDatabaseTransaction();//在指定的连接上处理数据库事务
        }

        if (isset($uses[WithoutMiddleware::class])) {
            $this->disableMiddlewareForAllTests();//为这个测试类防止所有的中间件被执行
        }

        if (isset($uses[WithoutEvents::class])) {
            $this->disableEventsForAllTests();//防止所有事件句柄被执行
        }
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * 在下一次测试之前清除测试环境
     *
     * @return void
     */
    protected function tearDown()
    {
        if ($this->app) {
            foreach ($this->beforeApplicationDestroyedCallbacks as $callback) {
                call_user_func($callback);
            }

            $this->app->flush();//刷新所有绑定的容器并解决实例

            $this->app = null;
        }

        $this->setUpHasRun = false;

        if (property_exists($this, 'serverVariables')) {
            $this->serverVariables = [];
        }

        if (class_exists('Mockery')) {
            Mockery::close();
        }

        $this->afterApplicationCreatedCallbacks = [];
        $this->beforeApplicationDestroyedCallbacks = [];
        //        清除控制台应用程序启动加载器
        Artisan::forgetBootstrappers();
    }

    /**
     * Register a callback to be run after the application is created.
     *
     * 注册一个回调，以便在应用程序创建后运行
     *
     * @param  callable  $callback
     * @return void
     */
    public function afterApplicationCreated(callable $callback)
    {
        $this->afterApplicationCreatedCallbacks[] = $callback;

        if ($this->setUpHasRun) {
            call_user_func($callback);
        }
    }

    /**
     * Register a callback to be run before the application is destroyed.
     *
     * 注册一个回调，在应用程序被销毁之前运行
     *
     * @param  callable  $callback
     * @return void
     */
    protected function beforeApplicationDestroyed(callable $callback)
    {
        $this->beforeApplicationDestroyedCallbacks[] = $callback;
    }
}
