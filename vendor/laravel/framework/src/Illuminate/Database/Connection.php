<?php

namespace Illuminate\Database;

use PDO;
use Closure;
use Exception;
use PDOStatement;
use LogicException;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Database\Query\Expression;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Doctrine\DBAL\Connection as DoctrineConnection;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Database\Query\Grammars\Grammar as QueryGrammar;

class Connection implements ConnectionInterface
{
    use DetectsDeadlocks, //检测死锁
        DetectsLostConnections, //检测连接丢失
        Concerns\ManagesTransactions; //管理事务

    /**
     * The active PDO connection.
     *
     * 活动的PDO连接
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * The active PDO connection used for reads.
     *
     * 读取用的活动PDO连接
     *
     * @var PDO
     */
    protected $readPdo;

    /**
     * The name of the connected database.
     *
     * 连接数据库的名称
     *
     * @var string
     */
    protected $database;

    /**
     * The table prefix for the connection.
     *
     * 连接的表前缀
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * The database connection configuration options.
     *
     * 数据库连接配置选项
     *
     * @var array
     */
    protected $config = [];

    /**
     * The reconnector instance for the connection.
     *
     * 用于连接的reconnector实例
     *
     * @var callable
     */
    protected $reconnector;

    /**
     * The query grammar implementation.
     *
     * 查询语法实现
     *
     * @var \Illuminate\Database\Query\Grammars\Grammar
     */
    protected $queryGrammar;

    /**
     * The schema grammar implementation.
     *
     * 模式语法实现
     *
     * @var \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected $schemaGrammar;

    /**
     * The query post processor implementation.
     *
     * 查询后处理器实现
     *
     * @var \Illuminate\Database\Query\Processors\Processor
     */
    protected $postProcessor;

    /**
     * The event dispatcher instance.
     *
     * 事件调度实例
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The default fetch mode of the connection.
     *
     * 连接的默认取模式
     *
     * @var int
     */
    protected $fetchMode = PDO::FETCH_OBJ;

    /**
     * The number of active transactions.
     *
     * 活动事务数
     *
     * @var int
     */
    protected $transactions = 0;

    /**
     * All of the queries run against the connection.
     *
     * 所有查询都运行在连接上
     *
     * @var array
     */
    protected $queryLog = [];

    /**
     * Indicates whether queries are being logged.
     *
     * 指示是否有正在查询的查询
     *
     * @var bool
     */
    protected $loggingQueries = false;

    /**
     * Indicates if the connection is in a "dry run".
     *
     * 指示连接是否处于“干运行状态”
     *
     * @var bool
     */
    protected $pretending = false;

    /**
     * The instance of Doctrine connection.
     *
     * Doctrine连接的实例
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $doctrineConnection;

    /**
     * The connection resolvers.
     *
     * 解析器连接
     *
     * @var array
     */
    protected static $resolvers = [];

    /**
     * Create a new database connection instance.
	 *
	 * 创建一个新的数据库连接实例
     *
     * 创建一个新的数据库连接实例
     *
     * @param  \PDO|\Closure     $pdo
     * @param  string   $database
     * @param  string   $tablePrefix
     * @param  array    $config
     * @return void
     */
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        $this->pdo = $pdo;

        // First we will setup the default properties. We keep track of the DB
        // name we are connected to since it is needed when some reflective
        // type commands are run such as checking whether a table exists.
        //
        // 首先，我们将设置默认属性
        // 我们跟踪我们连接到的DB名称，因为当一些反射类型命令运行时，如需要检查表是否存在
        //
        $this->database = $database;

        $this->tablePrefix = $tablePrefix;

        $this->config = $config;

        // We need to initialize a query grammar and the query post processors
        // which are both very important parts of the database abstractions
        // so we initialize these to their default values while starting.
        //
        // 我们需要初始化一个查询语法和查询后处理器，它们都是数据库抽象的重要部分，所以我们在初始化时将它们初始化为默认值。
        //
        $this->useDefaultQueryGrammar(); //设置默认实现的请求语法

        $this->useDefaultPostProcessor(); //获取默认查询语法实例
    }

    /**
     * Set the query grammar to the default implementation.
	 *
	 * 设置默认实现的请求语法
     *
     * @return void
     */
    public function useDefaultQueryGrammar()
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar(); //获取默认查询语法实例
    }

    /**
     * Get the default query grammar instance.
	 *
	 * 获取默认查询语法实例
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar;
    }

    /**
     * Set the schema grammar to the default implementation.
     *
     * 将架构语法设置为默认实现
     *
     * @return void
     */
    public function useDefaultSchemaGrammar()
    {
        //                      获取默认模式语法实例
        $this->schemaGrammar = $this->getDefaultSchemaGrammar();
    }

    /**
     * Get the default schema grammar instance.
     *
     * 获取默认模式语法实例
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        //
    }

    /**
     * Set the query post processor to the default implementation.
	 *
	 * 根据默认实现设置请求后置处理器
     *
     * @return void
     */
    public function useDefaultPostProcessor()
    {
        //                        获取默认的后置处理器实例
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    /**
     * Get the default post processor instance.
	 *
	 * 获取默认的后置处理器实例
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor;
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * 获取连接的架构生成器实例
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar(); //将架构语法设置为默认实现
        }

        return new SchemaBuilder($this); //创建新的数据库架构管理器
    }

    /**
     * Begin a fluent query against a database table.
	 *
	 * 对数据库表开始一个流式查询
     *
     * @param  string  $table
     * @return \Illuminate\Database\Query\Builder
     */
    public function table($table)
    {
		//    获取一个新的查询构造器实例->设置查询对象的表
        return $this->query()->from($table);
    }

    /**
     * Get a new query builder instance.
	 *
	 * 获取一个新的查询构造器实例
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return new QueryBuilder( //创建一个新的查询构造器实例
            //       获取连接所使用的查询语法     获取连接所使用的查询后处理器
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Run a select statement and return a single result.
     *
     * 运行SELECT语句并返回单个结果
     *
     * @param  string  $query
     * @param  array   $bindings
     * @param  bool  $useReadPdo
     * @return mixed
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        //对数据库运行SELECT语句
        $records = $this->select($query, $bindings, $useReadPdo);

        return array_shift($records);
    }

    /**
     * Run a select statement against the database.
     *
     * 对数据库运行SELECT语句
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return array
     */
    public function selectFromWriteConnection($query, $bindings = [])
    {
        //对数据库运行SELECT语句
        return $this->select($query, $bindings, false);
    }

    /**
     * Run a select statement against the database.
	 *
	 * 对数据库运行SELECT语句
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
		// 运行SQL语句并记录其执行上下文
        return $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) { //确定连接是否在“干运行”
                return [];
            }

            // For select statements, we'll simply execute the query and return an array
            // of the database result set. Each element in the array will be a single
            // row from the database table, and will either be an array or objects.
            //
            // 对于SELECT语句，我们只需执行查询并返回数据库结果集的数组
            // 数组中的每个元素将是一个单独的行，从数据库表，将是一个数组或对象
            //
            //                  配置PDO声明(获得PDO连接使用的select查询)->PDO::prepare()
            $statement = $this->prepared($this->getPdoForSelect($useReadPdo)
                              ->prepare($query));
            //将值绑定到给定语句中的参数($statement,为执行准备查询绑定)
            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();//PDO::execute()

            return $statement->fetchAll();//PDO::fetchAll()
        });
    }

    /**
     * Run a select statement against the database and returns a generator.
     *
     * 对数据库运行SELECT语句并返回生成器
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return \Generator
     */
    public function cursor($query, $bindings = [], $useReadPdo = true)
    {
        // 运行SQL语句并记录其执行上下文
        $statement = $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) { //确定连接是否在“干运行”
                return [];
            }

            // First we will create a statement for the query. Then, we will set the fetch
            // mode and prepare the bindings for the query. Once that's done we will be
            // ready to execute the query against the database and return the cursor.
            //
            // 我们将创建第一个声明的查询
            // 然后，我们将设置获取模式并为查询准备绑定
            // 一旦这样做，我们将准备执行对数据库的查询和返回光标
            //
            //          配置PDO声明(获得PDO连接使用的select查询)->PDO::prepare()
            $statement = $this->prepared($this->getPdoForSelect($useReadPdo)
                              ->prepare($query));
            //将值绑定到给定语句中的参数($statement,为执行准备查询绑定)
            $this->bindValues(
                $statement, $this->prepareBindings($bindings)
            );

            // Next, we'll execute the query against the database and return the statement
            // so we can return the cursor. The cursor will use a PHP generator to give
            // back one row at a time without using a bunch of memory to render them.
            //
            // 接下来，我们将执行对数据库的查询并返回语句，以便返回游标
            // 游标将使用一个PHP生成器一次返回一行，而不需要使用一堆内存来渲染它们。
            //
            $statement->execute();//PDO::execute()

            return $statement;
        });

        while ($record = $statement->fetch()) {
            yield $record;
        }
    }

    /**
     * Configure the PDO prepared statement.
     *
     * 配置PDO声明
     *
     * @param  \PDOStatement  $statement
     * @return \PDOStatement
     */
    protected function prepared(PDOStatement $statement)
    {
        $statement->setFetchMode($this->fetchMode);//PDO::setFetchMode()
        //如果可能的话，对给定的事件进行发送
        $this->event(new Events\StatementPrepared(
            $this, $statement
        ));

        return $statement;
    }

    /**
     * Get the PDO connection to use for a select query.
     *
     * 获得PDO连接使用的select查询
     *
     * @param  bool  $useReadPdo
     * @return \PDO
     */
    protected function getPdoForSelect($useReadPdo = true)
    {
        //                   用于读取的当前PDO连接      获取当前的PDO连接
        return $useReadPdo ? $this->getReadPdo() : $this->getPdo();
    }

    /**
     * Run an insert statement against the database.
     *
     * 对数据库运行insert语句
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function insert($query, $bindings = [])
    {
        return $this->statement($query, $bindings); //执行SQL语句并返回布尔结果
    }

    /**
     * Run an update statement against the database.
     *
     * 对数据库运行更新语句
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function update($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings); //运行SQL语句并得到受影响的行数
    }

    /**
     * Run a delete statement against the database.
     *
     * 对数据库运行删除语句
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function delete($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings); //运行SQL语句并得到受影响的行数
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * 执行SQL语句并返回布尔结果
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        // 运行SQL语句并记录其执行上下文
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) { //确定连接是否在“干运行”
                return true;
            }
            //              获取当前的PDO连接->PDO::prepare()
            $statement = $this->getPdo()->prepare($query);
            //将值绑定到给定语句中的参数($statement,为执行准备查询绑定)
            $this->bindValues($statement, $this->prepareBindings($bindings));

            return $statement->execute(); // PDO::execute()
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * 运行SQL语句并得到受影响的行数
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        // 运行SQL语句并记录其执行上下文
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {//确定连接是否在“干运行”
                return 0;
            }

            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement and then we'll use PDO to fetch the affected.
            //
            // 对于更新或删除语句，我们希望得到受语句影响的行数并将其返回给开发人员
            // 我们首先需要执行的语句，然后我们将使用PDO提取影响行数
            //
            //              获取当前的PDO连接->PDO::prepare()
            $statement = $this->getPdo()->prepare($query);
            //将值绑定到给定语句中的参数($statement,为执行准备查询绑定)
            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute(); // PDO::execute()

            return $statement->rowCount(); // PDO::rowCount()
        });
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * 运行一个原文，准备对PDO连接查询
     *
     * @param  string  $query
     * @return bool
     */
    public function unprepared($query)
    {
        // 运行SQL语句并记录其执行上下文
        return $this->run($query, [], function ($query) {
            if ($this->pretending()) {//确定连接是否在“干运行”
                return true;
            }
            //             获取当前的PDO连接->PDO::exec()
            return (bool) $this->getPdo()->exec($query);
        });
    }

    /**
     * Execute the given callback in "dry run" mode.
     *
     * 在“干运行”模式下执行给定回调
     *
     * @param  \Closure  $callback
     * @return array
     */
    public function pretend(Closure $callback)
    {
        return $this->withFreshQueryLog(function () use ($callback) { //在“干运行”模式下执行给定回调
            $this->pretending = true;

            // Basically to make the database connection "pretend", we will just return
            // the default values for all the query methods, then we will return an
            // array of queries that were "executed" within the Closure callback.
            //
            // 基本上是为了使数据库连接“假装”，我们只会返回默认值的所有查询方法，然后我们将返回一个数组的查询是“执行”在封闭回调
            //
            $callback($this);

            $this->pretending = false;

            return $this->queryLog;
        });
    }

    /**
     * Execute the given callback in "dry run" mode.
     *
     * 在“干运行”模式下执行给定回调
     *
     * @param  \Closure  $callback
     * @return array
     */
    protected function withFreshQueryLog($callback)
    {
        $loggingQueries = $this->loggingQueries;

        // First we will back up the value of the logging queries property and then
        // we'll be ready to run callbacks. This query log will also get cleared
        // so we will have a new log of all the queries that are executed now.
        //
        // 首先我们将备份的日志查询属性的值，然后我们就可以运行回调
        // 此查询日志也将得到清除，所以我们将有一个新的日志，所有的查询，现在执行
        //
        $this->enableQueryLog(); //启用连接上的查询日志

        $this->queryLog = [];

        // Now we'll execute this callback and capture the result. Once it has been
        // executed we will restore the value of query logging and give back the
        // value of hte callback so the original callers can have the results.
        //
        // 现在我们将执行这个回调并捕获结果
        // 一旦被执行，我们将恢复查询记录的价值和回报该回调的值，原来的用户会有结果
        //
        $result = $callback();

        $this->loggingQueries = $loggingQueries;

        return $result;
    }

    /**
     * Bind values to their parameters in the given statement.
     *
     * 将值绑定到给定语句中的参数
     *
     * @param  \PDOStatement $statement
     * @param  array  $bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue( //PDO::bindValue()
                is_string($key) ? $key : $key + 1, $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }

    /**
     * Prepare the query bindings for execution.
     *
     * 为执行准备查询绑定
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar(); //获取连接所使用的查询语法

        foreach ($bindings as $key => $value) {
            // We need to transform all instances of DateTimeInterface into the actual
            // date string. Each query grammar maintains its own date string format
            // so we'll just ask the grammar for the format to get from the date.
            //
            // 我们需要转变DateTimeInterface所有实例在实际日期字符串
            // 每个查询语法都维护自己的日期字符串格式，所以我们只需要从日期中获取格式的语法
            //
            if ($value instanceof DateTimeInterface) {
                //                                 获取数据库存储日期的格式
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif ($value === false) {
                $bindings[$key] = 0;
            }
        }

        return $bindings;
    }

    /**
     * Run a SQL statement and log its execution context.
	 *
	 * 运行SQL语句并记录其执行上下文
     *
     * @param  string    $query
     * @param  array     $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function run($query, $bindings, Closure $callback)
    {
        $this->reconnectIfMissingConnection();

        $start = microtime(true);

        // Here we will run this query. If an exception occurs we'll determine if it was
        // caused by a connection that has been lost. If that is the cause, we'll try
        // to re-establish connection and re-run the query with a fresh connection.
        //
        // 在这里，我们将运行此查询
        // 如果发生异常，我们将确定它是否由丢失的连接引起
        // 如果这是原因，我们将尝试重新建立连接，并重新运行查询与一个新的连接
        //
        try {
            $result = $this->runQueryCallback($query, $bindings, $callback); //运行SQL语句
        } catch (QueryException $e) {
            $result = $this->handleQueryException( //查询异常处理
                $e, $query, $bindings, $callback
            );
        }

        // Once we have run the query we will calculate the time that it took to run and
        // then log the query, bindings, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.
        //
        // 一旦我们运行了查询，我们将计算运行所需的时间，然后记录查询、绑定和执行时间，以便在开发者需要它们的事件中报告它们
        // 我们将以毫秒为单位记录时间
        //
        $this->logQuery(
            $query, $bindings, $this->getElapsedTime($start) //从给定起始点获取所经过的时间
        );

        return $result;
    }

    /**
     * Run a SQL statement.
	 *
	 * 运行SQL语句
     *
     * @param  string    $query
     * @param  array     $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        // To execute the statement, we'll simply call the callback, which will actually
        // run the SQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query SQL, bindings and time in our memory.
        //
        // 要执行的语句，我们可以简单的调用回调，这实际上会碰到PDO连接SQL
        // 然后，我们可以计算执行和记录查询sql、绑定和时间在我们的内存中所花费的时间
        //
        try {
            $result = $callback($query, $bindings);
        }

        // If an exception occurs when attempting to run a query, we'll format the error
        // message to include the bindings with SQL, which will make this exception a
        // lot more helpful to the developer instead of just the database's errors.
        //
        // 如果在试图运行查询时发生异常，我们将格式化错误信息以包含sql绑定，这将使此异常对开发人员更有帮助，而不仅仅是数据库的错误
        //
        catch (Exception $e) {
            throw new QueryException( //创建一个新的查询异常实例
                //           为执行准备查询绑定
                $query, $this->prepareBindings($bindings), $e
            );
        }

        return $result;
    }

    /**
     * Log a query in the connection's query log.
     *
     * 在连接的查询日志中记录查询
     *
     * @param  string  $query
     * @param  array   $bindings
     * @param  float|null  $time
     * @return void
     */
    public function logQuery($query, $bindings, $time = null)
    {
        //如果可能的话，对给定的事件进行发送
        $this->event(new QueryExecuted($query, $bindings, $time, $this));

        if ($this->loggingQueries) {
            $this->queryLog[] = compact('query', 'bindings', 'time');
        }
    }

    /**
     * Get the elapsed time since a given starting point.
	 *
	 * 从给定起始点获取所经过的时间
     *
     * @param  int    $start
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Handle a query exception.
     *
     * 查询异常处理
     *
     * @param  \Exception  $e
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return mixed
     */
    protected function handleQueryException($e, $query, $bindings, Closure $callback)
    {
        if ($this->transactions >= 1) {
            throw $e;
        }
        //             处理查询执行过程中发生的查询异常
        return $this->tryAgainIfCausedByLostConnection(
            $e, $query, $bindings, $callback
        );
    }

    /**
     * Handle a query exception that occurred during query execution.
     *
     * 处理查询执行过程中发生的查询异常
     *
     * @param  \Illuminate\Database\QueryException  $e
     * @param  string    $query
     * @param  array     $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, Closure $callback)
    {
        //   确定给定的异常是否由丢失的连接引起
        if ($this->causedByLostConnection($e->getPrevious())) {
            $this->reconnect();

            return $this->runQueryCallback($query, $bindings, $callback);
        }

        throw $e;
    }

    /**
     * Reconnect to the database.
     *
     * 重新连接数据库
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function reconnect()
    {
        if (is_callable($this->reconnector)) {
            return call_user_func($this->reconnector, $this);
        }

        throw new LogicException('Lost connection and no reconnector available.');
    }

    /**
     * Reconnect to the database if a PDO connection is missing.
     *
     * 重新连接到数据库，如果一个PDO连接丢失
     *
     * @return void
     */
    protected function reconnectIfMissingConnection()
    {
        if (is_null($this->pdo)) {
            $this->reconnect(); //重新连接数据库
        }
    }

    /**
     * Disconnect from the underlying PDO connection.
     *
     * 从底层的PDO连接断开
     *
     * @return void
     */
    public function disconnect()
    {
        //PDO连接设置        设置用于读取的PDO连接
        $this->setPdo(null)->setReadPdo(null);
    }

    /**
     * Register a database query listener with the connection.
     *
     * 使用连接注册数据库查询监听器
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function listen(Closure $callback)
    {
        if (isset($this->events)) {
            //            用分配器注册事件监听器
            $this->events->listen(Events\QueryExecuted::class, $callback);
        }
    }

    /**
     * Fire an event for this connection.
     *
     * 为此连接触发事件
     *
     * @param  string  $event
     * @return void
     */
    protected function fireConnectionEvent($event)
    {
        if (! isset($this->events)) {
            return;
        }

        switch ($event) {
            case 'beganTransaction':
                return $this->events->dispatch(new Events\TransactionBeginning($this)); //将事件触发，直到返回第一个非空响应
            case 'committed':
                return $this->events->dispatch(new Events\TransactionCommitted($this)); //将事件触发，直到返回第一个非空响应
            case 'rollingBack':
                return $this->events->dispatch(new Events\TransactionRolledBack($this)); //将事件触发，直到返回第一个非空响应
        }
    }

    /**
     * Fire the given event if possible.
     *
     * 如果可能的话，对给定的事件进行发送
     *
     * @param  mixed  $event
     * @return void
     */
    protected function event($event)
    {
        if (isset($this->events)) {
            $this->events->dispatch($event); //将事件触发，直到返回第一个非空响应
        }
    }

    /**
     * Get a new raw query expression.
     *
     * 获取新的原始查询表达式
     *
     * @param  mixed  $value
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value)
    {
        return new Expression($value); // 创建新的原始查询表达式
    }

    /**
     * Is Doctrine available?
     *
     * Doctrine是可用的？
     *
     * @return bool
     */
    public function isDoctrineAvailable()
    {
        return class_exists('Doctrine\DBAL\Connection');
    }

    /**
     * Get a Doctrine Schema Column instance.
     *
     * 获取Doctrine模式列实例
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Doctrine\DBAL\Schema\Column
     */
    public function getDoctrineColumn($table, $column)
    {
        $schema = $this->getDoctrineSchemaManager(); //获取连接的Doctrine DBAL架构管理器

        return $schema->listTableDetails($table)->getColumn($column);
    }

    /**
     * Get the Doctrine DBAL schema manager for the connection.
     *
     * 获取连接的Doctrine DBAL架构管理器
     *
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getDoctrineSchemaManager()
    {
        return $this->getDoctrineDriver()->getSchemaManager($this->getDoctrineConnection());
    }

    /**
     * Get the Doctrine DBAL database connection instance.
     *
     * 获取Doctrine DBAL数据库连接实例
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getDoctrineConnection()
    {
        if (is_null($this->doctrineConnection)) {
            //                获取当前的PDO连接               从配置选项中获得选项
            $data = ['pdo' => $this->getPdo(), 'dbname' => $this->getConfig('database')];

            $this->doctrineConnection = new DoctrineConnection(
                $data, $this->getDoctrineDriver()
            );
        }

        return $this->doctrineConnection;
    }

    /**
     * Get the current PDO connection.
     *
     * 获取当前的PDO连接
     *
     * @return \PDO
     */
    public function getPdo()
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    /**
     * Get the current PDO connection used for reading.
     *
     * 用于读取的当前PDO连接
     *
     * @return \PDO
     */
    public function getReadPdo()
    {
        if ($this->transactions >= 1) {
            return $this->getPdo();//获取当前的PDO连接
        }

        if ($this->readPdo instanceof Closure) {
            return $this->readPdo = call_user_func($this->readPdo);
        }
        //                            获取当前的PDO连接
        return $this->readPdo ?: $this->getPdo();
    }

    /**
     * Set the PDO connection.
     *
     * 设置PDO连接
     *
     * @param  \PDO|null  $pdo
     * @return $this
     */
    public function setPdo($pdo)
    {
        $this->transactions = 0;

        $this->pdo = $pdo;

        return $this;
    }

    /**
     * Set the PDO connection used for reading.
     *
     * 设置用于读取的PDO连接
     *
     * @param  \PDO|null  $pdo
     * @return $this
     */
    public function setReadPdo($pdo)
    {
        $this->readPdo = $pdo;

        return $this;
    }

    /**
     * Set the reconnect instance on the connection.
     *
     * 设置连接上的重新连接实例
     *
     * @param  callable  $reconnector
     * @return $this
     */
    public function setReconnector(callable $reconnector)
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    /**
     * Get the database connection name.
     *
     * 获取数据库连接名
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getConfig('name');//从配置选项中获得选项
    }

    /**
     * Get an option from the configuration options.
     *
     * 从配置选项中获得选项
     *
     * @param  string  $option
     * @return mixed
     */
    public function getConfig($option)
    {
        return Arr::get($this->config, $option);
    }

    /**
     * Get the PDO driver name.
     *
     * 获得PDO驱动程序名称
     *
     * @return string
     */
    public function getDriverName()
    {
        return $this->getConfig('driver');//从配置选项中获得选项
    }

    /**
     * Get the query grammar used by the connection.
     *
     * 获取连接所使用的查询语法
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    public function getQueryGrammar()
    {
        return $this->queryGrammar;
    }

    /**
     * Set the query grammar used by the connection.
     *
     * 设置连接使用的查询语法
     *
     * @param  \Illuminate\Database\Query\Grammars\Grammar  $grammar
     * @return void
     */
    public function setQueryGrammar(Query\Grammars\Grammar $grammar)
    {
        $this->queryGrammar = $grammar;
    }

    /**
     * Get the schema grammar used by the connection.
     *
     * 获取连接使用的查询语法
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    public function getSchemaGrammar()
    {
        return $this->schemaGrammar;
    }

    /**
     * Set the schema grammar used by the connection.
     *
     * 设置连接所使用的架构语法
     *
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return void
     */
    public function setSchemaGrammar(Schema\Grammars\Grammar $grammar)
    {
        $this->schemaGrammar = $grammar;
    }

    /**
     * Get the query post processor used by the connection.
     *
     * 获取连接所使用的查询后处理器
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    /**
     * Set the query post processor used by the connection.
     *
     * 设置连接所使用的查询后处理器
     *
     * @param  \Illuminate\Database\Query\Processors\Processor  $processor
     * @return void
     */
    public function setPostProcessor(Processor $processor)
    {
        $this->postProcessor = $processor;
    }

    /**
     * Get the event dispatcher used by the connection.
     *
     * 获取连接使用的事件调度程序
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance on the connection.
     *
     * 设置连接使用的事件调度程序
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Determine if the connection in a "dry run".
     *
     * 确定连接是否在“干运行”
     *
     * @return bool
     */
    public function pretending()
    {
        return $this->pretending === true;
    }

    /**
     * Get the connection query log.
     *
     * 获取连接查询日志
     *
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * Clear the query log.
     *
     * 清除查询日志
     *
     * @return void
     */
    public function flushQueryLog()
    {
        $this->queryLog = [];
    }

    /**
     * Enable the query log on the connection.
     *
     * 启用连接上的查询日志
     *
     * @return void
     */
    public function enableQueryLog()
    {
        $this->loggingQueries = true;
    }

    /**
     * Disable the query log on the connection.
     *
     * 禁用连接上的查询日志
     *
     * @return void
     */
    public function disableQueryLog()
    {
        $this->loggingQueries = false;
    }

    /**
     * Determine whether we're logging queries.
     *
     * 确定我们是否正在记录查询
     *
     * @return bool
     */
    public function logging()
    {
        return $this->loggingQueries;
    }

    /**
     * Get the name of the connected database.
     *
     * 获取连接数据库的名称
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->database;
    }

    /**
     * Set the name of the connected database.
     *
     * 设置连接数据库的名称
     *
     * @param  string  $database
     * @return string
     */
    public function setDatabaseName($database)
    {
        $this->database = $database;
    }

    /**
     * Get the table prefix for the connection.
     *
     * 获取连接的表前缀
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Set the table prefix in use by the connection.
     *
     * 设置连接所使用的表前缀
     *
     * @param  string  $prefix
     * @return void
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
        //   获取连接所使用的查询语法 设置语法的表前缀
        $this->getQueryGrammar()->setTablePrefix($prefix);
    }

    /**
     * Set the table prefix and return the grammar.
     *
     * 设置表前缀并返回语法
     *
     * @param  \Illuminate\Database\Grammar  $grammar
     * @return \Illuminate\Database\Grammar
     */
    public function withTablePrefix(Grammar $grammar)
    {
        $grammar->setTablePrefix($this->tablePrefix);//设置连接所使用的表前缀

        return $grammar;
    }

    /**
     * Register a connection resolver.
     *
     * 注册连接解析器
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return void
     */
    public static function resolverFor($driver, Closure $callback)
    {
        static::$resolvers[$driver] = $callback;
    }

    /**
     * Get the connection resolver for the given driver.
     *
     * 获取给定驱动程序的连接解析器
     *
     * @param  string  $driver
     * @return mixed
     */
    public static function getResolver($driver)
    {
        return isset(static::$resolvers[$driver]) ?
                     static::$resolvers[$driver] : null;
    }
}
