<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Console\TableCommand;
use Illuminate\Auth\Console\MakeAuthCommand;
use Illuminate\Foundation\Console\UpCommand;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Auth\Console\ClearResetsCommand;
use Illuminate\Cache\Console\CacheTableCommand;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Queue\Console\FailedTableCommand;
use Illuminate\Foundation\Console\AppNameCommand;
use Illuminate\Foundation\Console\JobMakeCommand;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Foundation\Console\MailMakeCommand;
use Illuminate\Foundation\Console\OptimizeCommand;
use Illuminate\Foundation\Console\TestMakeCommand;
use Illuminate\Foundation\Console\EventMakeCommand;
use Illuminate\Foundation\Console\ModelMakeCommand;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Foundation\Console\ViewClearCommand;
use Illuminate\Session\Console\SessionTableCommand;
use Illuminate\Foundation\Console\PolicyMakeCommand;
use Illuminate\Foundation\Console\RouteCacheCommand;
use Illuminate\Foundation\Console\RouteClearCommand;
use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Illuminate\Foundation\Console\ConfigCacheCommand;
use Illuminate\Foundation\Console\ConfigClearCommand;
use Illuminate\Foundation\Console\ConsoleMakeCommand;
use Illuminate\Foundation\Console\EnvironmentCommand;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use Illuminate\Foundation\Console\RequestMakeCommand;
use Illuminate\Foundation\Console\StorageLinkCommand;
use Illuminate\Routing\Console\ControllerMakeCommand;
use Illuminate\Routing\Console\MiddlewareMakeCommand;
use Illuminate\Foundation\Console\ListenerMakeCommand;
use Illuminate\Foundation\Console\ProviderMakeCommand;
use Illuminate\Foundation\Console\ClearCompiledCommand;
use Illuminate\Foundation\Console\EventGenerateCommand;
use Illuminate\Foundation\Console\VendorPublishCommand;
use Illuminate\Console\Scheduling\ScheduleFinishCommand;
use Illuminate\Database\Console\Seeds\SeederMakeCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Foundation\Console\NotificationMakeCommand;
use Illuminate\Queue\Console\WorkCommand as QueueWorkCommand;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Notifications\Console\NotificationTableCommand;
use Illuminate\Cache\Console\ClearCommand as CacheClearCommand;
use Illuminate\Queue\Console\RetryCommand as QueueRetryCommand;
use Illuminate\Cache\Console\ForgetCommand as CacheForgetCommand;
use Illuminate\Queue\Console\ListenCommand as QueueListenCommand;
use Illuminate\Queue\Console\RestartCommand as QueueRestartCommand;
use Illuminate\Queue\Console\ListFailedCommand as ListFailedQueueCommand;
use Illuminate\Queue\Console\FlushFailedCommand as FlushFailedQueueCommand;
use Illuminate\Queue\Console\ForgetFailedCommand as ForgetFailedQueueCommand;
use Illuminate\Database\Console\Migrations\ResetCommand as MigrateResetCommand;
use Illuminate\Database\Console\Migrations\StatusCommand as MigrateStatusCommand;
use Illuminate\Database\Console\Migrations\InstallCommand as MigrateInstallCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand as MigrateRefreshCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand as MigrateRollbackCommand;
//Artisan服务提供者
class ArtisanServiceProvider extends ServiceProvider
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
     * The commands to be registered.
     *
     * 要注册的命令
     *
     * @var array
     */
    protected $commands = [
        'CacheClear' => 'command.cache.clear',
        'CacheForget' => 'command.cache.forget',
        'ClearCompiled' => 'command.clear-compiled',
        'ClearResets' => 'command.auth.resets.clear',
        'ConfigCache' => 'command.config.cache',
        'ConfigClear' => 'command.config.clear',
        'Down' => 'command.down',
        'Environment' => 'command.environment',
        'KeyGenerate' => 'command.key.generate',
        'Migrate' => 'command.migrate',
        'MigrateInstall' => 'command.migrate.install',
        'MigrateRefresh' => 'command.migrate.refresh',
        'MigrateReset' => 'command.migrate.reset',
        'MigrateRollback' => 'command.migrate.rollback',
        'MigrateStatus' => 'command.migrate.status',
        'Optimize' => 'command.optimize',
        'QueueFailed' => 'command.queue.failed',
        'QueueFlush' => 'command.queue.flush',
        'QueueForget' => 'command.queue.forget',
        'QueueListen' => 'command.queue.listen',
        'QueueRestart' => 'command.queue.restart',
        'QueueRetry' => 'command.queue.retry',
        'QueueWork' => 'command.queue.work',
        'RouteCache' => 'command.route.cache',
        'RouteClear' => 'command.route.clear',
        'RouteList' => 'command.route.list',
        'Seed' => 'command.seed',
        'ScheduleFinish' => ScheduleFinishCommand::class,
        'ScheduleRun' => ScheduleRunCommand::class,
        'StorageLink' => 'command.storage.link',
        'Up' => 'command.up',
        'ViewClear' => 'command.view.clear',
    ];

    /**
     * The commands to be registered.
     *
     * 要注册的命令
     *
     * @var array
     */
    protected $devCommands = [
        'AppName' => 'command.app.name',
        'AuthMake' => 'command.auth.make',
        'CacheTable' => 'command.cache.table',
        'ConsoleMake' => 'command.console.make',
        'ControllerMake' => 'command.controller.make',
        'EventGenerate' => 'command.event.generate',
        'EventMake' => 'command.event.make',
        'JobMake' => 'command.job.make',
        'ListenerMake' => 'command.listener.make',
        'MailMake' => 'command.mail.make',
        'MiddlewareMake' => 'command.middleware.make',
        'MigrateMake' => 'command.migrate.make',
        'ModelMake' => 'command.model.make',
        'NotificationMake' => 'command.notification.make',
        'NotificationTable' => 'command.notification.table',
        'PolicyMake' => 'command.policy.make',
        'ProviderMake' => 'command.provider.make',
        'QueueFailedTable' => 'command.queue.failed-table',
        'QueueTable' => 'command.queue.table',
        'RequestMake' => 'command.request.make',
        'SeederMake' => 'command.seeder.make',
        'SessionTable' => 'command.session.table',
        'Serve' => 'command.serve',
        'TestMake' => 'command.test.make',
        'VendorPublish' => 'command.vendor.publish',
    ];

    /**
     * Register the service provider.
     *
     * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        //注册给定的命令
        $this->registerCommands(array_merge(
            $this->commands, $this->devCommands
        ));
    }

    /**
     * Register the given commands.
     *
     * 注册给定的命令
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach (array_keys($commands) as $command) {
            call_user_func_array([$this, "register{$command}Command"], []);
        }
        //注册包的自定义Artisan命令
        $this->commands(array_values($commands));
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerAppNameCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.app.name', function ($app) {
            return new AppNameCommand($app['composer'], $app['files']);//创建一个新的密钥生成器命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerAuthMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.auth.make', function ($app) {
            return new MakeAuthCommand;//创建身份验证命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerCacheClearCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.cache.clear', function ($app) {
            return new CacheClearCommand($app['cache']);//创建一个新的缓存清除命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerCacheForgetCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.cache.forget', function ($app) {
            return new CacheForgetCommand($app['cache']);//创建一个新的缓存清除命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerCacheTableCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.cache.table', function ($app) {
            return new CacheTableCommand($app['files'], $app['composer']);//创建一个新的缓存表命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerClearCompiledCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.clear-compiled', function () {
            return new ClearCompiledCommand;//清除编译命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerClearResetsCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.auth.resets.clear', function () {
            return new ClearResetsCommand;//清除重置命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerConfigCacheCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.config.cache', function ($app) {
            return new ConfigCacheCommand($app['files']);//创建一个新的配置缓存命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerConfigClearCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.config.clear', function ($app) {
            return new ConfigClearCommand($app['files']);//创建一个新的配置清除命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerConsoleMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.console.make', function ($app) {
            return new ConsoleMakeCommand($app['files']);//控制台创建命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerControllerMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.controller.make', function ($app) {
            return new ControllerMakeCommand($app['files']);//控制器创建命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerEventGenerateCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.event.generate', function () {
            return new EventGenerateCommand;//事件生成命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerEventMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.event.make', function ($app) {
            return new EventMakeCommand($app['files']);//创建事件命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerDownCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.down', function () {
            return new DownCommand;//创建事件命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerEnvironmentCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.environment', function () {
            return new EnvironmentCommand;//环境命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerJobMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.job.make', function ($app) {
            return new JobMakeCommand($app['files']);//创建工作命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerKeyGenerateCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.key.generate', function () {
            return new KeyGenerateCommand;//密钥生成命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerListenerMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.listener.make', function ($app) {
            return new ListenerMakeCommand($app['files']);//创建监听命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerMailMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.mail.make', function ($app) {
            return new MailMakeCommand($app['files']);//创建邮件命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerMiddlewareMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.middleware.make', function ($app) {
            return new MiddlewareMakeCommand($app['files']);//创建中间件命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.migrate', function ($app) {
            return new MigrateCommand($app['migrator']);//创建一个新的迁移命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerMigrateInstallCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.migrate.install', function ($app) {
            return new MigrateInstallCommand($app['migration.repository']);//创建一个新的迁移安装命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerMigrateMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.migrate.make', function ($app) {
            // Once we have the migration creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the migrations, and may be extended by these developers.
            //
            // 一旦我们注册了迁移的创建者，我们将创建这个命令并注入创建者
            // 创建者负责迁移的实际文件创建，并可能由这些开发人员进行扩展
            //
            $creator = $app['migration.creator'];

            $composer = $app['composer'];

            return new MigrateMakeCommand($creator, $composer);//创建一个新的迁移安装命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerMigrateRefreshCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.migrate.refresh', function () {
            return new MigrateRefreshCommand;//刷新命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerMigrateResetCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.migrate.reset', function ($app) {
            return new MigrateResetCommand($app['migrator']);//创建一个新的迁移回滚命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerMigrateRollbackCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.migrate.rollback', function ($app) {
            return new MigrateRollbackCommand($app['migrator']);//创建一个新的迁移回滚命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerMigrateStatusCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.migrate.status', function ($app) {
            return new MigrateStatusCommand($app['migrator']);//创建一个新的迁移回滚命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerModelMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.model.make', function ($app) {
            return new ModelMakeCommand($app['files']);//创建模型命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerNotificationMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.notification.make', function ($app) {
            return new NotificationMakeCommand($app['files']);//创建通知命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerOptimizeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.optimize', function ($app) {
            return new OptimizeCommand($app['composer']);//创建一个新的优化命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerProviderMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.provider.make', function ($app) {
            return new ProviderMakeCommand($app['files']);//创建提供者命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerQueueFailedCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.queue.failed', function () {
            return new ListFailedQueueCommand;//失败的列表命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerQueueForgetCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.queue.forget', function () {
            return new ForgetFailedQueueCommand;//移除失败的命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerQueueFlushCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.queue.flush', function () {
            return new FlushFailedQueueCommand;//刷新失败的命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerQueueListenCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.queue.listen', function ($app) {
            return new QueueListenCommand($app['queue.listener']);//创建一个新的队列监听命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerQueueRestartCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.queue.restart', function () {
            return new QueueRestartCommand;//重启命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerQueueRetryCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.queue.retry', function () {
            return new QueueRetryCommand;//重试命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerQueueWorkCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.queue.work', function ($app) {
            return new QueueWorkCommand($app['queue.worker']);//创建一个新的队列监听命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerQueueFailedTableCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.queue.failed-table', function ($app) {
            return new FailedTableCommand($app['files'], $app['composer']);//创建一个新的失败队列作业表命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerQueueTableCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.queue.table', function ($app) {
            return new TableCommand($app['files'], $app['composer']);//创建一个新的队列作业表命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerRequestMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.request.make', function ($app) {
            return new RequestMakeCommand($app['files']);//创建请求命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerSeederMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.seeder.make', function ($app) {
            return new SeederMakeCommand($app['files'], $app['composer']);//创建一个新的命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerSessionTableCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.session.table', function ($app) {
            return new SessionTableCommand($app['files'], $app['composer']);//创建一个新的会话表命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerStorageLinkCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.storage.link', function () {
            return new StorageLinkCommand;//存储链接命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerRouteCacheCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.route.cache', function ($app) {
            return new RouteCacheCommand($app['files']);//创建一个新的路由命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerRouteClearCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.route.clear', function ($app) {
            return new RouteClearCommand($app['files']);//创建一个新的路由清除命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerRouteListCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.route.list', function ($app) {
            return new RouteListCommand($app['router']);//创建一个新的路由命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerSeedCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.seed', function ($app) {
            return new SeedCommand($app['db']);//创建一个新的数据库种子命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerScheduleFinishCommand()
    {
        //在容器中注册共享绑定(计划完成命令)
        $this->app->singleton(ScheduleFinishCommand::class);
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerScheduleRunCommand()
    {
        //在容器中注册共享绑定(调度运行命令)
        $this->app->singleton(ScheduleRunCommand::class);
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerServeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.serve', function () {
            return new ServeCommand;//服务命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerTestMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.test.make', function ($app) {
            return new TestMakeCommand($app['files']);//创建测试命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerUpCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.up', function () {
            return new UpCommand;//up命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerVendorPublishCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.vendor.publish', function ($app) {
            return new VendorPublishCommand($app['files']);//创建一个新的命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerViewClearCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.view.clear', function ($app) {
            return new ViewClearCommand($app['files']);//创建一个新的配置清除命令实例
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerPolicyMakeCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.policy.make', function ($app) {
            return new PolicyMakeCommand($app['files']);//创建策略命令
        });
    }

    /**
     * Register the command.
     *
     * 注册命令
     *
     * @return void
     */
    protected function registerNotificationTableCommand()
    {
        //在容器中注册共享绑定
        $this->app->singleton('command.notification.table', function ($app) {
            return new NotificationTableCommand($app['files'], $app['composer']);//创建一个新的通知表命令实例
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
        return array_merge(array_values($this->commands), array_values($this->devCommands));
    }
}
