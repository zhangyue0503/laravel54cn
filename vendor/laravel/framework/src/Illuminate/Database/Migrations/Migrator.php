<?php

namespace Illuminate\Database\Migrations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class Migrator
{
    /**
     * The migration repository implementation.
     *
     * 迁移存储库实现
     *
     * @var \Illuminate\Database\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * The filesystem instance.
     *
     * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The connection resolver instance.
     *
     * 连接解析器实例
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the default connection.
     *
     * 缺省连接的名称
     *
     * @var string
     */
    protected $connection;

    /**
     * The notes for the current operation.
     *
     * 当前操作的说明
     *
     * @var array
     */
    protected $notes = [];

    /**
     * The paths to all of the migration files.
     *
     * 所有迁移文件的路径
     *
     * @var array
     */
    protected $paths = [];

    /**
     * Create a new migrator instance.
     *
     * 创建一个新的迁移实例
     *
     * @param  \Illuminate\Database\Migrations\MigrationRepositoryInterface  $repository
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(MigrationRepositoryInterface $repository,
                                Resolver $resolver,
                                Filesystem $files)
    {
        $this->files = $files;
        $this->resolver = $resolver;
        $this->repository = $repository;
    }

    /**
     * Run the pending migrations at a given path.
     *
     * 在给定的路径上运行正在等待的迁移
     *
     * @param  array|string  $paths
     * @param  array  $options
     * @return array
     */
    public function run($paths = [], array $options = [])
    {
        $this->notes = [];

        // Once we grab all of the migration files for the path, we will compare them
        // against the migrations that have already been run for this package then
        // run each of the outstanding migrations against a database connection.
        //
        // 一旦我们获取了该路径的所有迁移文件，我们将把它们与已经为该包运行的迁移进行比较，然后运行针对数据库连接的所有未完成的迁移
        //
        //             在给定的路径中获取所有迁移文件
        $files = $this->getMigrationFiles($paths);
        //在给定路径中的所有迁移文件中都需要            获取尚未运行的迁移文件
        $this->requireFiles($migrations = $this->pendingMigrations(
            //                    获取一个给定包的运行迁移
            $files, $this->repository->getRan()
        ));

        // Once we have all these migrations that are outstanding we are ready to run
        // we will go ahead and run them "up". This will execute each migration as
        // an operation against a database. Then we'll return this list of them.
        //
        // 一旦我们有了这些非常出色的迁移，我们就可以运行了，我们将继续运行它们
        // 这将执行每个迁移作为对数据库的操作
        // 然后我们将返回它们的列表
        //
        //       运行一系列的迁移
        $this->runPending($migrations, $options);

        return $migrations;
    }

    /**
     * Get the migration files that have not yet run.
     *
     * 获取尚未运行的迁移文件
     *
     * @param  array  $files
     * @param  array  $ran
     * @return array
     */
    protected function pendingMigrations($files, $ran)
    {
        //创建一个新的集合实例，如果该值不是一个准备好的
        return Collection::make($files)
            //创建不通过给定的真值测试的所有元素的集合
                ->reject(function ($file) use ($ran) {
                    //获取迁移的名称
                    return in_array($this->getMigrationName($file), $ran);
                    //重置基础阵列上的键->获取集合中的所有项目
                })->values()->all();
    }

    /**
     * Run an array of migrations.
     *
     * 运行一系列的迁移
     *
     * @param  array  $migrations
     * @param  array  $options
     * @return void
     */
    public function runPending(array $migrations, array $options = [])
    {
        // First we will just make sure that there are any migrations to run. If there
        // aren't, we will just make a note of it to the developer so they're aware
        // that all of the migrations have been run against this database system.
        //
        // 首先，我们将确保有任何要运行的迁移
        // 如果没有，我们只需要向开发人员说明一下，这样他们就会意识到所有的迁移都是针对这个数据库系统运行的
        //
        if (count($migrations) == 0) {
            $this->note('<info>Nothing to migrate.</info>');

            return;
        }

        // Next, we will get the next batch number for the migrations so we can insert
        // correct batch number in the database migrations repository when we store
        // each migration's execution. We will also extract a few of the options.
        //
        // 接下来，我们将获得迁移的下一个批号，以便在存储每次迁移时，我们可以在数据库迁移存储库中插入正确的批号
        // 我们还将提取一些选项
        //
        //                          获得下一个迁移批号
        $batch = $this->repository->getNextBatchNumber();
        //使用“点”符号从数组中获取一个项
        $pretend = Arr::get($options, 'pretend', false);

        $step = Arr::get($options, 'step', false);

        // Once we have the array of migrations, we will spin through them and run the
        // migrations "up" so the changes are made to the databases. We'll then log
        // that the migration was run so we don't repeat it next time we execute.
        //
        // 一旦我们有了迁移的数组，我们就会对它们进行旋转，并运行迁移“向上”，这样就会对数据库进行更改
        // 然后，我们将记录迁移的运行情况，这样我们下次执行时就不会再重复了
        //
        foreach ($migrations as $file) {
            //“运行”一个迁移实例
            $this->runUp($file, $batch, $pretend);

            if ($step) {
                $batch++;
            }
        }
    }

    /**
     * Run "up" a migration instance.
     *
     * “运行”一个迁移实例
     *
     * @param  string  $file
     * @param  int     $batch
     * @param  bool    $pretend
     * @return void
     */
    protected function runUp($file, $batch, $pretend)
    {
        // First we will resolve a "real" instance of the migration class from this
        // migration file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        //
        // 首先，我们将从这个迁移文件名称中解析迁移类的“真实”实例
        // 一旦我们有实例可以运行实际的命令,如“向上”或“向下”,或者我们可以模拟操作
        //
        //               从文件中解析一个迁移实例
        $migration = $this->resolve(
            //            获取迁移的名称
            $name = $this->getMigrationName($file)
        );

        if ($pretend) {
            //           假装运行迁移
            return $this->pretendToRun($migration, 'up');
        }
        //如果数据库支持该操作，则在事务中运行一个迁移
        $this->runMigration($migration, 'up');

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        //
        // 一旦我们运行了一个迁移类，我们将记录它是在这个存储库中运行的，这样我们下次在应用程序中进行迁移时就不会尝试运行它
        // 迁移存储库保持迁移顺序
        //
        //                记录迁移运行的日志
        $this->repository->log($name, $batch);
        //为迁移者提出一个通知事件
        $this->note("<info>Migrated:</info> {$name}");
    }

    /**
     * Rollback the last migration operation.
     *
     * 回滚最后一次迁移操作
     *
     * @param  array|string $paths
     * @param  array  $options
     * @return array
     */
    public function rollback($paths = [], array $options = [])
    {
        $this->notes = [];

        // We want to pull in the last batch of migrations that ran on the previous
        // migration operation. We'll then reverse those migrations and run each
        // of them "down" to reverse the last migration "operation" which ran.
        //
        // 我们希望引入上一个迁移操作上运行的最后一批迁移
        // 然后我们将反向迁移和运行他们每个人“向下”扭转过去迁移“操作”跑
        //
        //                        获取回滚操作的迁移
        $migrations = $this->getMigrationsForRollback($options);

        if (count($migrations) === 0) {
            //为迁移者提出一个通知事件
            $this->note('<info>Nothing to rollback.</info>');

            return [];
        } else {
            //              回滚给定的迁移
            return $this->rollbackMigrations($migrations, $paths, $options);
        }
    }

    /**
     * Get the migrations for a rollback operation.
     *
     * 获取回滚操作的迁移
     *
     * @param  array  $options
     * @return array
     */
    protected function getMigrationsForRollback(array $options)
    {
        //使用“点”符号从数组中获取一个项
        if (($steps = Arr::get($options, 'step', 0)) > 0) {
            //                       获取迁移列表
            return $this->repository->getMigrations($steps);
        } else {
            //                  最后一次迁移
            return $this->repository->getLast();
        }
    }

    /**
     * Rollback the given migrations.
     *
     * 回滚给定的迁移
     *
     * @param  array  $migrations
     * @param  array  $paths
     * @param  array  $options
     * @return array
     */
    protected function rollbackMigrations(array $migrations, array $paths, array $options)
    {
        $rolledBack = [];
        //在给定路径中的所有迁移文件中都需要          在给定的路径中获取所有迁移文件
        $this->requireFiles($files = $this->getMigrationFiles($paths));

        // Next we will run through all of the migrations and call the "down" method
        // which will reverse each migration in order. This getLast method on the
        // repository already returns these migration's names in reverse order.
        //
        // 接下来，我们将遍历所有迁移，并调用“down”方法，该方法将依次反转每个迁移
        // 在存储库上的get姓氏已经以相反的顺序返回了这些迁移的名称
        //
        foreach ($migrations as $migration) {
            $migration = (object) $migration;

            $rolledBack[] = $files[$migration->migration];
            //“向下”迁移实例运行
            $this->runDown(
                $files[$migration->migration],
            //使用“点”符号从数组中获取一个项
                $migration, Arr::get($options, 'pretend', false)
            );
        }

        return $rolledBack;
    }

    /**
     * Rolls all of the currently applied migrations back.
     *
     * 将当前应用的所有迁移回滚
     *
     * @param  array|string $paths
     * @param  bool  $pretend
     * @return array
     */
    public function reset($paths = [], $pretend = false)
    {
        $this->notes = [];

        // Next, we will reverse the migration list so we can run them back in the
        // correct order for resetting this database. This will allow us to get
        // the database back into its "empty" state ready for the migrations.
        //
        // 接下来，我们将逆转迁移列表，这样我们就可以以正确的顺序运行它们，以重新设置这个数据库
        // 这将使我们能够将数据库恢复到为迁移准备的“空”状态
        //
        //                                     获取一个给定包的运行迁移
        $migrations = array_reverse($this->repository->getRan());

        if (count($migrations) === 0) {
            //为迁移者提出一个通知事件
            $this->note('<info>Nothing to rollback.</info>');

            return [];
        } else {
            //            重置给定的迁移
            return $this->resetMigrations($migrations, $paths, $pretend);
        }
    }

    /**
     * Reset the given migrations.
     *
     * 重置给定的迁移
     *
     * @param  array  $migrations
     * @param  array  $paths
     * @param  bool  $pretend
     * @return array
     */
    protected function resetMigrations(array $migrations, array $paths, $pretend = false)
    {
        // Since the getRan method that retrieves the migration name just gives us the
        // migration name, we will format the names into objects with the name as a
        // property on the objects so that we can pass it to the rollback method.
        //
        // 自从getRan方法检索迁移名称只给了我们迁移名称,我们将格式名称为对象名称的对象的一个属性,这样我们可以通过回滚方法
        //
        //                                    在每个项目上运行map
        $migrations = collect($migrations)->map(function ($m) {
            return (object) ['migration' => $m];
        })->all();//获取集合中的所有项目
        //            回滚给定的迁移
        return $this->rollbackMigrations(
            $migrations, $paths, compact('pretend')
        );
    }

    /**
     * Run "down" a migration instance.
     *
     * “向下”迁移实例运行
     *
     * @param  string  $file
     * @param  object  $migration
     * @param  bool    $pretend
     * @return void
     */
    protected function runDown($file, $migration, $pretend)
    {
        // First we will get the file name of the migration so we can resolve out an
        // instance of the migration. Once we get an instance we can either run a
        // pretend execution of the migration or we can run the real migration.
        //
        // 首先，我们将获取迁移的文件名，这样我们就可以解析迁移的实例。一旦我们得到一个实例，我们就可以运行一个模拟的迁移，或者我们可以运行真正的迁移
        //
        //             从文件中解析一个迁移实例
        $instance = $this->resolve(
            //            获取迁移的名称
            $name = $this->getMigrationName($file)
        );

        if ($pretend) {
            //           假装运行迁移
            return $this->pretendToRun($instance, 'down');
        }
        //如果数据库支持该操作，则在事务中运行一个迁移
        $this->runMigration($instance, 'down');

        // Once we have successfully run the migration "down" we will remove it from
        // the migration repository so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        //
        // 一旦我们已经成功运行迁移”“我们将移除它从迁移库将被视为未运行的应用程序将能够火任何后操作
        //
        //                从日志中删除一个迁移
        $this->repository->delete($migration);
        //为迁移者提出一个通知事件
        $this->note("<info>Rolled back:</info> {$name}");
    }

    /**
     * Run a migration inside a transaction if the database supports it.
     *
     * 如果数据库支持该操作，则在事务中运行一个迁移
     *
     * @param  object  $migration
     * @param  string  $method
     * @return void
     */
    protected function runMigration($migration, $method)
    {
        //解析数据库连接实例
        $connection = $this->resolveConnection(
            $migration->getConnection()
        );

        $callback = function () use ($migration, $method) {
            $migration->{$method}();
        };

        //从迁移连接中获取模式语法              检查该语法是否支持在事务中包装的模式更改
        $this->getSchemaGrammar($connection)->supportsSchemaTransactions()
                    ? $connection->transaction($callback)//在事务中执行闭包
                    : $callback();
    }

    /**
     * Pretend to run the migrations.
     *
     * 假装运行迁移
     *
     * @param  object  $migration
     * @param  string  $method
     * @return void
     */
    protected function pretendToRun($migration, $method)
    {
        //获取为迁移所运行的所有查询
        foreach ($this->getQueries($migration, $method) as $query) {
            $name = get_class($migration);
            //为迁移者提出一个通知事件
            $this->note("<info>{$name}:</info> {$query['query']}");
        }
    }

    /**
     * Get all of the queries that would be run for a migration.
     *
     * 获取为迁移所运行的所有查询
     *
     * @param  object  $migration
     * @param  string  $method
     * @return array
     */
    protected function getQueries($migration, $method)
    {
        // Now that we have the connections we can resolve it and pretend to run the
        // queries against the database returning the array of raw SQL statements
        // that would get fired against the database system for this migration.
        //
        // 现在，我们有了连接，我们可以解决它，并假装对数据库运行查询来返回原始SQL语句的数组，这些语句将被针对该迁移的数据库系统触发
        //
        //解析数据库连接实例
        $db = $this->resolveConnection(
            $connection = $migration->getConnection()
        );
        //在“干运行”模式下执行给定回调
        return $db->pretend(function () use ($migration, $method) {
            $migration->$method();
        });
    }

    /**
     * Resolve a migration instance from a file.
     *
     * 从文件中解析一个迁移实例
     *
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        //将值转换为大驼峰
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));

        return new $class;
    }

    /**
     * Get all of the migration files in a given path.
     *
     * 在给定的路径中获取所有迁移文件
     *
     * @param  string|array  $paths
     * @return array
     */
    public function getMigrationFiles($paths)
    {
        //创建一个新的集合实例，如果该值不是一个准备好的  映射集合并将结果按单个级别拉平
        return Collection::make($paths)->flatMap(function ($path) {
            //找到匹配给定模式的路径名
            return $this->files->glob($path.'/*_*.php');
            //在每个项目上运行过滤器->使用给定的回调排序集合
        })->filter()->sortBy(function ($file) {
            //获取迁移的名称
            return $this->getMigrationName($file);
            //重置基础阵列上的键->用字段或使用回调键组合一个关联数组
        })->values()->keyBy(function ($file) {
            //获取迁移的名称
            return $this->getMigrationName($file);
            //获取集合中的所有项目
        })->all();
    }

    /**
     * Require in all the migration files in a given path.
     *
     * 在给定路径中的所有迁移文件中都需要
     *
     * @param  array   $files
     * @return void
     */
    public function requireFiles(array $files)
    {
        foreach ($files as $file) {
            //要求给定的文件一次
            $this->files->requireOnce($file);
        }
    }

    /**
     * Get the name of the migration.
     *
     * 获取迁移的名称
     *
     * @param  string  $path
     * @return string
     */
    public function getMigrationName($path)
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Register a custom migration path.
     *
     * 注册一个自定义迁移路径
     *
     * @param  string  $path
     * @return void
     */
    public function path($path)
    {
        $this->paths = array_unique(array_merge($this->paths, [$path]));
    }

    /**
     * Get all of the custom migration paths.
     *
     * 获取所有自定义迁移路径
     *
     * @return array
     */
    public function paths()
    {
        return $this->paths;
    }

    /**
     * Set the default connection name.
     *
     * 设置缺省连接名称
     *
     * @param  string  $name
     * @return void
     */
    public function setConnection($name)
    {
        if (! is_null($name)) {
            //          设置默认的连接名称
            $this->resolver->setDefaultConnection($name);
        }
        //             设置信息源以收集数据
        $this->repository->setSource($name);

        $this->connection = $name;
    }

    /**
     * Resolve the database connection instance.
     *
     * 解析数据库连接实例
     *
     * @param  string  $connection
     * @return \Illuminate\Database\Connection
     */
    public function resolveConnection($connection)
    {
        //                 获取一个数据连接实例
        return $this->resolver->connection($connection ?: $this->connection);
    }

    /**
     * Get the schema grammar out of a migration connection.
     *
     * 从迁移连接中获取模式语法
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected function getSchemaGrammar($connection)
    {
        //获取连接使用的查询语法
        if (is_null($grammar = $connection->getSchemaGrammar())) {
            $connection->useDefaultSchemaGrammar();//将架构语法设置为默认实现

            $grammar = $connection->getSchemaGrammar();
        }

        return $grammar;
    }

    /**
     * Get the migration repository instance.
     *
     * 获取迁移存储库实例
     *
     * @return \Illuminate\Database\Migrations\MigrationRepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Determine if the migration repository exists.
     *
     * 确定迁移存储库是否存在
     *
     * @return bool
     */
    public function repositoryExists()
    {
        //确定迁移存储库是否存在
        return $this->repository->repositoryExists();
    }

    /**
     * Get the file system instance.
     *
     * 获取文件系统实例
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

    /**
     * Raise a note event for the migrator.
     *
     * 为迁移者提出一个通知事件
     *
     * @param  string  $message
     * @return void
     */
    protected function note($message)
    {
        $this->notes[] = $message;
    }

    /**
     * Get the notes for the last operation.
     *
     * 获取最后一个操作的注释
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }
}
