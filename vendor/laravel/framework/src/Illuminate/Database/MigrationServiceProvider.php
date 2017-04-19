<?php

namespace Illuminate\Database;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
//迁移服务提供者
class MigrationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * 指示是否推迟提供程序的加载
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
        $this->registerRepository(); //注册迁移库服务

        $this->registerMigrator();//注册迁移服务

        $this->registerCreator();//注册迁移创建者
    }

    /**
     * Register the migration repository service.
     *
     * 注册迁移库服务
     *
     * @return void
     */
    protected function registerRepository()
    {
        //                在容器中注册共享绑定
        $this->app->singleton('migration.repository', function ($app) {
            $table = $app['config']['database.migrations'];

            return new DatabaseMigrationRepository($app['db'], $table); // 创建新的数据库迁移库实例
        });
    }

    /**
     * Register the migrator service.
     *
     * 注册迁移服务
     *
     * @return void
     */
    protected function registerMigrator()
    {
        // The migrator is responsible for actually running and rollback the migration
        // files in the application. We'll pass in our database connection resolver
        // so the migrator can resolve any of these connections when it needs to.
        //
        // 他们负责实际运行和回滚在应用迁移文件
        // 我们会通过我们的数据库连接变压器所以他们能解决任何这些连接时需要
        //
        //           在容器中注册共享绑定
        $this->app->singleton('migrator', function ($app) {
            $repository = $app['migration.repository'];

            return new Migrator($repository, $app['db'], $app['files']); //创建一个新的迁移实例
        });
    }

    /**
     * Register the migration creator.
     *
     * 注册迁移创建者
     *
     * @return void
     */
    protected function registerCreator()
    {
        //           在容器中注册共享绑定
        $this->app->singleton('migration.creator', function ($app) {
            return new MigrationCreator($app['files']); // 创建一个新的迁移创建者实例
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
            'migrator', 'migration.repository', 'migration.creator',
        ];
    }
}
