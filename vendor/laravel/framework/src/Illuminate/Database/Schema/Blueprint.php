<?php

namespace Illuminate\Database\Schema;

use Closure;
use Illuminate\Support\Fluent;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;

class Blueprint
{
    /**
     * The table the blueprint describes.
     *
     * 蓝图描述的表格
     *
     * @var string
     */
    protected $table;

    /**
     * The columns that should be added to the table.
     *
     * 应该添加到表中的列
     *
     * @var array
     */
    protected $columns = [];

    /**
     * The commands that should be run for the table.
     *
     * 应该为表运行的命令
     *
     * @var array
     */
    protected $commands = [];

    /**
     * The storage engine that should be used for the table.
     *
     * 应该用于表的存储引擎
     *
     * @var string
     */
    public $engine;

    /**
     * The default character set that should be used for the table.
     * 表中应该使用的默认字符集
     */
    public $charset;

    /**
     * The collation that should be used for the table.
     * 应该用于表的排序
     */
    public $collation;

    /**
     * Whether to make the table temporary.
     *
     * 是否要使表暂时成为临时的
     *
     * @var bool
     */
    public $temporary = false;

    /**
     * Create a new schema blueprint.
     *
     * 创建一个新的模式蓝图
     *
     * @param  string  $table
     * @param  \Closure|null  $callback
     * @return void
     */
    public function __construct($table, Closure $callback = null)
    {
        $this->table = $table;

        if (! is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * Execute the blueprint against the database.
     *
     * 对数据库执行蓝图
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar $grammar
     * @return void
     */
    public function build(Connection $connection, Grammar $grammar)
    {
        //获取blueprint的原始SQL语句
        foreach ($this->toSql($connection, $grammar) as $statement) {
            //执行SQL语句并返回布尔结果
            $connection->statement($statement);
        }
    }

    /**
     * Get the raw SQL statements for the blueprint.
     *
     * 获取blueprint的原始SQL语句
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \Illuminate\Database\Schema\Grammars\Grammar  $grammar
     * @return array
     */
    public function toSql(Connection $connection, Grammar $grammar)
    {
        //添加由blueprint的状态所暗示的命令
        $this->addImpliedCommands();

        $statements = [];

        // Each type of command has a corresponding compiler function on the schema
        // grammar which is used to build the necessary SQL statements to build
        // the blueprint element, so we'll just call that compilers function.
        //
        // 每种类型的命令在模式语法上都有对应的编译器函数，用于构建必要的SQL语句来构建blueprint元素，因此我们只调用编译器函数
        //
        foreach ($this->commands as $command) {
            $method = 'compile'.ucfirst($command->name);

            if (method_exists($grammar, $method)) {
                if (! is_null($sql = $grammar->$method($this, $command, $connection))) {
                    $statements = array_merge($statements, (array) $sql);
                }
            }
        }

        return $statements;
    }

    /**
     * Add the commands that are implied by the blueprint's state.
     *
     * 添加由blueprint的状态所暗示的命令
     *
     * @return void
     */
    protected function addImpliedCommands()
    {
        //获取应该添加的蓝图中的列                     确定蓝图是否有一个create命令
        if (count($this->getAddedColumns()) > 0 && ! $this->creating()) {
            //                               创建一个新的流畅命令
            array_unshift($this->commands, $this->createCommand('add'));
        }
        //获取应该更改的蓝图中的列
        if (count($this->getChangedColumns()) > 0 && ! $this->creating()) {
            array_unshift($this->commands, $this->createCommand('change'));
        }
        //添加在列上指定的索引命令
        $this->addFluentIndexes();
    }

    /**
     * Add the index commands fluently specified on columns.
     *
     * 添加在列上指定的索引命令
     *
     * @return void
     */
    protected function addFluentIndexes()
    {
        foreach ($this->columns as $column) {
            foreach (['primary', 'unique', 'index'] as $index) {
                // If the index has been specified on the given column, but is simply equal
                // to "true" (boolean), no name has been specified for this index so the
                // index method can be called without a name and it will generate one.
                //
                // 如果该指数已经给定的列上指定,但仅仅等于“true”(布尔),没有指定名称这个索引的索引方法可以调用没有名字,它会生成一个
                //
                if ($column->{$index} === true) {
                    $this->{$index}($column->name);

                    continue 2;
                }

                // If the index has been specified on the given column, and it has a string
                // value, we'll go ahead and call the index method and pass the name for
                // the index since the developer specified the explicit name for this.
                //
                // 如果该指数已经给定的列上指定,它有一个字符串值,我们将继续调用索引方法,通过索引的名称由于开发人员指定明确的名称
                //
                elseif (isset($column->{$index})) {
                    $this->{$index}($column->name, $column->{$index});

                    continue 2;
                }
            }
        }
    }

    /**
     * Determine if the blueprint has a create command.
     *
     * 确定蓝图是否有一个create命令
     *
     * @return bool
     */
    protected function creating()
    {
        //                             确定集合中是否存在项
        return collect($this->commands)->contains(function ($command) {
            return $command->name == 'create';
        });
    }

    /**
     * Indicate that the table needs to be created.
     *
     * 表示需要创建表
     *
     * @return \Illuminate\Support\Fluent
     */
    public function create()
    {
        //            在blueprint中添加一个新命令
        return $this->addCommand('create');
    }

    /**
     * Indicate that the table needs to be temporary.
     *
     * 表明表需要是临时的
     *
     * @return void
     */
    public function temporary()
    {
        $this->temporary = true;
    }

    /**
     * Indicate that the table should be dropped.
     *
     * 表示应该删除表
     *
     * @return \Illuminate\Support\Fluent
     */
    public function drop()
    {
        //在blueprint中添加一个新命令
        return $this->addCommand('drop');
    }

    /**
     * Indicate that the table should be dropped if it exists.
     *
     * 表示如果存在表，就应该删除该表
     *
     * @return \Illuminate\Support\Fluent
     */
    public function dropIfExists()
    {
        //在blueprint中添加一个新命令
        return $this->addCommand('dropIfExists');
    }

    /**
     * Indicate that the given columns should be dropped.
     *
     * 表示应该删除给定的列
     *
     * @param  array|mixed  $columns
     * @return \Illuminate\Support\Fluent
     */
    public function dropColumn($columns)
    {
        $columns = is_array($columns) ? $columns : (array) func_get_args();
        //在blueprint中添加一个新命令
        return $this->addCommand('dropColumn', compact('columns'));
    }

    /**
     * Indicate that the given columns should be renamed.
     *
     * 表示给定的列应该被重命名
     *
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Support\Fluent
     */
    public function renameColumn($from, $to)
    {
        //在blueprint中添加一个新命令
        return $this->addCommand('renameColumn', compact('from', 'to'));
    }

    /**
     * Indicate that the given primary key should be dropped.
     *
     * 表示应该删除给定的主键
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropPrimary($index = null)
    {
        //            在蓝图上创建一个新的drop index命令
        return $this->dropIndexCommand('dropPrimary', 'primary', $index);
    }

    /**
     * Indicate that the given unique key should be dropped.
     *
     * 表明给定的惟一键应该被删除
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropUnique($index)
    {
        //            在蓝图上创建一个新的drop index命令
        return $this->dropIndexCommand('dropUnique', 'unique', $index);
    }

    /**
     * Indicate that the given index should be dropped.
     *
     * 指示给定的索引应该被删除
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropIndex($index)
    {
        //            在蓝图上创建一个新的drop index命令
        return $this->dropIndexCommand('dropIndex', 'index', $index);
    }

    /**
     * Indicate that the given foreign key should be dropped.
     *
     * 表明给定的外键应该被删除
     *
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    public function dropForeign($index)
    {
        //            在蓝图上创建一个新的drop index命令
        return $this->dropIndexCommand('dropForeign', 'foreign', $index);
    }

    /**
     * Indicate that the timestamp columns should be dropped.
     *
     * 表示应该删除时间戳列
     *
     * @return void
     */
    public function dropTimestamps()
    {
        //表示应该删除给定的列
        $this->dropColumn('created_at', 'updated_at');
    }

    /**
     * Indicate that the timestamp columns should be dropped.
     *
     * 表示应该删除时间戳列
     *
     * @return void
     */
    public function dropTimestampsTz()
    {
        //表示应该删除时间戳列
        $this->dropTimestamps();
    }

    /**
     * Indicate that the soft delete column should be dropped.
     *
     * 表明应该删除软删除列
     *
     * @return void
     */
    public function dropSoftDeletes()
    {
        //表示应该删除给定的列
        $this->dropColumn('deleted_at');
    }

    /**
     * Indicate that the soft delete column should be dropped.
     *
     * 表明应该删除软删除列
     *
     * @return void
     */
    public function dropSoftDeletesTz()
    {
        //表明应该删除软删除列
        $this->dropSoftDeletes();
    }

    /**
     * Indicate that the remember token column should be dropped.
     *
     * 表示应该删除记住令牌列
     *
     * @return void
     */
    public function dropRememberToken()
    {
        //表示应该删除给定的列
        $this->dropColumn('remember_token');
    }

    /**
     * Rename the table to a given name.
     *
     * 将表重命名为给定的名称
     *
     * @param  string  $to
     * @return \Illuminate\Support\Fluent
     */
    public function rename($to)
    {
        //在blueprint中添加一个新命令
        return $this->addCommand('rename', compact('to'));
    }

    /**
     * Specify the primary key(s) for the table.
     *
     * 为表指定主键(s)
     *
     * @param  string|array  $columns
     * @param  string  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    public function primary($columns, $name = null, $algorithm = null)
    {
        //          在blueprint中添加一个新的索引命令
        return $this->indexCommand('primary', $columns, $name, $algorithm);
    }

    /**
     * Specify a unique index for the table.
     *
     * 为表指定惟一的索引
     *
     * @param  string|array  $columns
     * @param  string  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    public function unique($columns, $name = null, $algorithm = null)
    {
        //          在blueprint中添加一个新的索引命令
        return $this->indexCommand('unique', $columns, $name, $algorithm);
    }

    /**
     * Specify an index for the table.
     *
     * 为表指定索引
     *
     * @param  string|array  $columns
     * @param  string  $name
     * @param  string|null  $algorithm
     * @return \Illuminate\Support\Fluent
     */
    public function index($columns, $name = null, $algorithm = null)
    {
        //          在blueprint中添加一个新的索引命令
        return $this->indexCommand('index', $columns, $name, $algorithm);
    }

    /**
     * Specify a foreign key for the table.
     *
     * 为表指定一个外键
     *
     * @param  string|array  $columns
     * @param  string  $name
     * @return \Illuminate\Support\Fluent
     */
    public function foreign($columns, $name = null)
    {
        //          在blueprint中添加一个新的索引命令
        return $this->indexCommand('foreign', $columns, $name);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     *
     * 创建一个新增加的整数(4字节)列在表格上
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function increments($column)
    {
        //创建一个新的无符号整数(4字节)列在表格上
        return $this->unsignedInteger($column, true);
    }

    /**
     * Create a new auto-incrementing small integer (2-byte) column on the table.
     *
     * 创建一个新增加的小整数(2字节)列在表格上
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function smallIncrements($column)
    {
        //创建一个新的无符号整数(2字节)小列放在表格上
        return $this->unsignedSmallInteger($column, true);
    }

    /**
     * Create a new auto-incrementing medium integer (3-byte) column on the table.
     *
     * 创建一个新增加的中等整数(3字节)列在表格上
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function mediumIncrements($column)
    {
        //创建一个新的无符号中等整数(3字节)列在表格上
        return $this->unsignedMediumInteger($column, true);
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     *
     * 创建一个新增加的大整数(8字节)列在表格上
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function bigIncrements($column)
    {
        //创建一个新的无符号大整数(8字节)列在表格上
        return $this->unsignedBigInteger($column, true);
    }

    /**
     * Create a new char column on the table.
     *
     * 在表上创建一个新的char列
     *
     * @param  string  $column
     * @param  int  $length
     * @return \Illuminate\Support\Fluent
     */
    public function char($column, $length = null)
    {
        $length = $length ?: Builder::$defaultStringLength;
        //在blueprint中添加一个新列
        return $this->addColumn('char', $column, compact('length'));
    }

    /**
     * Create a new string column on the table.
     *
     * 在表上创建一个新的字符串列
     *
     * @param  string  $column
     * @param  int  $length
     * @return \Illuminate\Support\Fluent
     */
    public function string($column, $length = null)
    {
        $length = $length ?: Builder::$defaultStringLength;
        //在blueprint中添加一个新列
        return $this->addColumn('string', $column, compact('length'));
    }

    /**
     * Create a new text column on the table.
     *
     * 在表上创建一个新的文本列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function text($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('text', $column);
    }

    /**
     * Create a new medium text column on the table.
     *
     * 在表格中创建一个新的中等文本列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function mediumText($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('mediumText', $column);
    }

    /**
     * Create a new long text column on the table.
     *
     * 在表上创建一个新的长文本列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function longText($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('longText', $column);
    }

    /**
     * Create a new integer (4-byte) column on the table.
     *
     * 创建一个新的整数(4字节)列在表格上
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Support\Fluent
     */
    public function integer($column, $autoIncrement = false, $unsigned = false)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('integer', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new tiny integer (1-byte) column on the table.
     *
     * 创建一个新的小整数(4字节)列在表格上
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Support\Fluent
     */
    public function tinyInteger($column, $autoIncrement = false, $unsigned = false)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('tinyInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new small integer (2-byte) column on the table.
     *
     * 创建一个新的小整数(2字节)列在表格上
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Support\Fluent
     */
    public function smallInteger($column, $autoIncrement = false, $unsigned = false)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('smallInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new medium integer (3-byte) column on the table.
     *
     * 创建一个新的中等整数(3字节)列在表格上
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Support\Fluent
     */
    public function mediumInteger($column, $autoIncrement = false, $unsigned = false)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('mediumInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new big integer (8-byte) column on the table.
     *
     * 创建一个新的大整数(8字节)列在表格上
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @param  bool  $unsigned
     * @return \Illuminate\Support\Fluent
     */
    public function bigInteger($column, $autoIncrement = false, $unsigned = false)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new unsigned integer (4-byte) column on the table.
     *
     * 创建一个新的无符号整数(4字节)列在表格上
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Support\Fluent
     */
    public function unsignedInteger($column, $autoIncrement = false)
    {
        //创建一个新的整数(4字节)列在表格上
        return $this->integer($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned tiny integer (1-byte) column on the table.
     *
     * 创建一个新的无符号小整数(1字节)列在表格上
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Support\Fluent
     */
    public function unsignedTinyInteger($column, $autoIncrement = false)
    {
        //           创建一个新的小整数(4字节)列在表格上
        return $this->tinyInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned small integer (2-byte) column on the table.
     *
     * 创建一个新的无符号整数(2字节)小列放在表格上
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Support\Fluent
     */
    public function unsignedSmallInteger($column, $autoIncrement = false)
    {
        //创建一个新的小整数(2字节)列在表格上
        return $this->smallInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned medium integer (3-byte) column on the table.
     *
     * 创建一个新的无符号中等整数(3字节)列在表格上
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Support\Fluent
     */
    public function unsignedMediumInteger($column, $autoIncrement = false)
    {
        //创建一个新的中等整数(3字节)列在表格上
        return $this->mediumInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * 创建一个新的无符号大整数(8字节)列在表格上
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     * @return \Illuminate\Support\Fluent
     */
    public function unsignedBigInteger($column, $autoIncrement = false)
    {
        //创建一个新的大整数(8字节)列在表格上
        return $this->bigInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new float column on the table.
     *
     * 在表上创建一个新的浮动列
     *
     * @param  string  $column
     * @param  int     $total
     * @param  int     $places
     * @return \Illuminate\Support\Fluent
     */
    public function float($column, $total = 8, $places = 2)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('float', $column, compact('total', 'places'));
    }

    /**
     * Create a new double column on the table.
     *
     * 在表上创建一个新的双列
     *
     * @param  string   $column
     * @param  int|null    $total
     * @param  int|null $places
     * @return \Illuminate\Support\Fluent
     */
    public function double($column, $total = null, $places = null)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('double', $column, compact('total', 'places'));
    }

    /**
     * Create a new decimal column on the table.
     *
     * 在表上创建一个新的小数列
     *
     * @param  string  $column
     * @param  int     $total
     * @param  int     $places
     * @return \Illuminate\Support\Fluent
     */
    public function decimal($column, $total = 8, $places = 2)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('decimal', $column, compact('total', 'places'));
    }

    /**
     * Create a new boolean column on the table.
     *
     * 在表上创建一个新的布尔列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function boolean($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('boolean', $column);
    }

    /**
     * Create a new enum column on the table.
     *
     * 在表上创建一个新的enum列
     *
     * @param  string  $column
     * @param  array   $allowed
     * @return \Illuminate\Support\Fluent
     */
    public function enum($column, array $allowed)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('enum', $column, compact('allowed'));
    }

    /**
     * Create a new json column on the table.
     *
     * 在表上创建一个新的json列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function json($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('json', $column);
    }

    /**
     * Create a new jsonb column on the table.
     *
     * 在表上创建一个新的jsonb列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function jsonb($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('jsonb', $column);
    }

    /**
     * Create a new date column on the table.
     *
     * 在表上创建一个新的日期列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function date($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('date', $column);
    }

    /**
     * Create a new date-time column on the table.
     *
     * 创建一个新的日期-时间列在表格上
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function dateTime($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('dateTime', $column);
    }

    /**
     * Create a new date-time column (with time zone) on the table.
     *
     * 创建一个新的日期-时间列(时区)放在表格上
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function dateTimeTz($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('dateTimeTz', $column);
    }

    /**
     * Create a new time column on the table.
     *
     * 在表上创建一个新的时间列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function time($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('time', $column);
    }

    /**
     * Create a new time column (with time zone) on the table.
     *
     * 在表上创建一个新的时间列(带有时区)
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function timeTz($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('timeTz', $column);
    }

    /**
     * Create a new timestamp column on the table.
     *
     * 在表上创建一个新的时间戳列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function timestamp($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('timestamp', $column);
    }

    /**
     * Create a new timestamp (with time zone) column on the table.
     *
     * 在表上创建一个新的时间戳(带有时区)列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function timestampTz($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('timestampTz', $column);
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * 在表中添加可空的创建和更新时间戳
     *
     * @return void
     */
    public function timestamps()
    {
        //在表上创建一个新的时间戳列
        $this->timestamp('created_at')->nullable();

        $this->timestamp('updated_at')->nullable();
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * 在表中添加可空的创建和更新时间戳
     *
     * Alias for self::timestamps().
     *
     * @return void
     */
    public function nullableTimestamps()
    {
        $this->timestamps();//在表中添加可空的创建和更新时间戳
    }

    /**
     * Add creation and update timestampTz columns to the table.
     *
     * 添加创建和更新timestampTz列到表中
     *
     * @return void
     */
    public function timestampsTz()
    {
        // 在表上创建一个新的时间戳(带有时区)列
        $this->timestampTz('created_at')->nullable();

        $this->timestampTz('updated_at')->nullable();
    }

    /**
     * Add a "deleted at" timestamp for the table.
     *
     * 为表添加一个“删除at”时间戳
     *
     * @return \Illuminate\Support\Fluent
     */
    public function softDeletes()
    {
        //在表上创建一个新的时间戳列
        return $this->timestamp('deleted_at')->nullable();
    }

    /**
     * Add a "deleted at" timestampTz for the table.
     *
     * 添加一个“删除”timestampTz表
     *
     * @return \Illuminate\Support\Fluent
     */
    public function softDeletesTz()
    {
        //在表上创建一个新的时间戳(带有时区)列
        return $this->timestampTz('deleted_at')->nullable();
    }

    /**
     * Create a new binary column on the table.
     *
     * 在表上创建一个新的二进制列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function binary($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('binary', $column);
    }

    /**
     * Create a new uuid column on the table.
     *
     * 在表上创建一个新的uuid列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function uuid($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('uuid', $column);
    }

    /**
     * Create a new IP address column on the table.
     *
     * 在表上创建一个新的IP地址列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function ipAddress($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('ipAddress', $column);
    }

    /**
     * Create a new MAC address column on the table.
     *
     * 在表上创建一个新的MAC地址列
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function macAddress($column)
    {
        //在blueprint中添加一个新列
        return $this->addColumn('macAddress', $column);
    }

    /**
     * Add the proper columns for a polymorphic table.
     *
     * 为多态表添加适当的列
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function morphs($name, $indexName = null)
    {
        //创建一个新的无符号整数(4字节)列在表格上
        $this->unsignedInteger("{$name}_id");
        //在表上创建一个新的字符串列
        $this->string("{$name}_type");
        //为表指定索引
        $this->index(["{$name}_id", "{$name}_type"], $indexName);
    }

    /**
     * Add nullable columns for a polymorphic table.
     *
     * @param  string  $name
     * @param  string|null  $indexName
     * @return void
     */
    public function nullableMorphs($name, $indexName = null)
    {
        //创建一个新的无符号整数(4字节)列在表格上
        $this->unsignedInteger("{$name}_id")->nullable();
        //在表上创建一个新的字符串列
        $this->string("{$name}_type")->nullable();
        //为表指定索引
        $this->index(["{$name}_id", "{$name}_type"], $indexName);
    }

    /**
     * Adds the `remember_token` column to the table.
     *
     * 将“remember_token”列添加到表中
     *
     * @return \Illuminate\Support\Fluent
     */
    public function rememberToken()
    {
        //在表上创建一个新的字符串列
        return $this->string('remember_token', 100)->nullable();
    }

    /**
     * Add a new index command to the blueprint.
     *
     * 在blueprint中添加一个新的索引命令
     *
     * @param  string        $type
     * @param  string|array  $columns
     * @param  string        $index
     * @param  string|null   $algorithm
     * @return \Illuminate\Support\Fluent
     */
    protected function indexCommand($type, $columns, $index, $algorithm = null)
    {
        $columns = (array) $columns;

        // If no name was specified for this index, we will create one using a basic
        // convention of the table name, followed by the columns, followed by an
        // index type, such as primary or index, which makes the index unique.
        //
        // 如果没有指定这个索引名称,我们将创建一个使用一个表名的基本惯例,紧随其后的是列,紧随其后的是一个索引类型,如主或索引,使指数独特
        //
        //                      为表创建一个默认的索引名称
        $index = $index ?: $this->createIndexName($type, $columns);
        //在blueprint中添加一个新命令
        return $this->addCommand(
            $type, compact('index', 'columns', 'algorithm')
        );
    }

    /**
     * Create a new drop index command on the blueprint.
     *
     * 在蓝图上创建一个新的drop index命令
     *
     * @param  string  $command
     * @param  string  $type
     * @param  string|array  $index
     * @return \Illuminate\Support\Fluent
     */
    protected function dropIndexCommand($command, $type, $index)
    {
        $columns = [];

        // If the given "index" is actually an array of columns, the developer means
        // to drop an index merely by specifying the columns involved without the
        // conventional name, so we will build the index name from the columns.
        //
        // 如果给定的“指数”实际上是一个数组的列,开发人员意味着删除索引仅仅通过指定的列不涉及传统的名字,所以我们将构建的索引名称列
        //
        if (is_array($index)) {
            //             为表创建一个默认的索引名称
            $index = $this->createIndexName($type, $columns = $index);
        }

        return $this->indexCommand($command, $columns, $index);
    }

    /**
     * Create a default index name for the table.
     *
     * 为表创建一个默认的索引名称
     *
     * @param  string  $type
     * @param  array   $columns
     * @return string
     */
    protected function createIndexName($type, array $columns)
    {
        $index = strtolower($this->table.'_'.implode('_', $columns).'_'.$type);

        return str_replace(['-', '.'], '_', $index);
    }

    /**
     * Add a new column to the blueprint.
     *
     * 在blueprint中添加一个新列
     *
     * @param  string  $type
     * @param  string  $name
     * @param  array   $parameters
     * @return \Illuminate\Support\Fluent
     */
    public function addColumn($type, $name, array $parameters = [])
    {
        $this->columns[] = $column = new Fluent(
            array_merge(compact('type', 'name'), $parameters)
        );

        return $column;
    }

    /**
     * Remove a column from the schema blueprint.
     *
     * 从模式蓝图中删除一个列
     *
     * @param  string  $name
     * @return $this
     */
    public function removeColumn($name)
    {
        $this->columns = array_values(array_filter($this->columns, function ($c) use ($name) {
            return $c['attributes']['name'] != $name;
        }));

        return $this;
    }

    /**
     * Add a new command to the blueprint.
     *
     * 在blueprint中添加一个新命令
     *
     * @param  string  $name
     * @param  array  $parameters
     * @return \Illuminate\Support\Fluent
     */
    protected function addCommand($name, array $parameters = [])
    {
        //                                    创建一个新的流畅命令
        $this->commands[] = $command = $this->createCommand($name, $parameters);

        return $command;
    }

    /**
     * Create a new Fluent command.
     *
     * 创建一个新的流畅命令
     *
     * @param  string  $name
     * @param  array   $parameters
     * @return \Illuminate\Support\Fluent
     */
    protected function createCommand($name, array $parameters = [])
    {
        return new Fluent(array_merge(compact('name'), $parameters));
    }

    /**
     * Get the table the blueprint describes.
     *
     * 获取蓝图描述的表格
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the columns on the blueprint.
     *
     * 获取蓝图上的列
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get the commands on the blueprename
     * 获取蓝图上的命令
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Get the columns on the blueprint that should be added.
     *
     * 获取应该添加的蓝图中的列
     *
     * @return array
     */
    public function getAddedColumns()
    {
        return array_filter($this->columns, function ($column) {
            return ! $column->change;
        });
    }

    /**
     * Get the columns on the blueprint that should be changed.
     *
     * 获取应该更改的蓝图中的列
     *
     * @return array
     */
    public function getChangedColumns()
    {
        return array_filter($this->columns, function ($column) {
            return (bool) $column->change;
        });
    }
}
