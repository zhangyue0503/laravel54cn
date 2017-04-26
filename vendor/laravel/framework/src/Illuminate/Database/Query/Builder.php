<?php

namespace Illuminate\Database\Query;

use Closure;
use RuntimeException;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Processors\Processor;

class Builder
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The database connection instance.
     *
     * 数据库连接实例
     *
     * @var \Illuminate\Database\Connection
     */
    public $connection;

    /**
     * The database query grammar instance.
     *
     * 数据库查询语法实例
     *
     * @var \Illuminate\Database\Query\Grammars\Grammar
     */
    public $grammar;

    /**
     * The database query post processor instance.
     *
     * 数据库查询后处理器实例
     *
     * @var \Illuminate\Database\Query\Processors\Processor
     */
    public $processor;

    /**
     * The current query value bindings.
     *
     * 当前查询值绑定
     *
     * @var array
     */
    public $bindings = [
        'select' => [],
        'join'   => [],
        'where'  => [],
        'having' => [],
        'order'  => [],
        'union'  => [],
    ];

    /**
     * An aggregate function and column to be run.
     *
     * 要运行的聚合函数和列
     *
     * @var array
     */
    public $aggregate;

    /**
     * The columns that should be returned.
     *
     * 应返回的列
     *
     * @var array
     */
    public $columns;

    /**
     * Indicates if the query returns distinct results.
     *
     * 指示查询是否返回不同的结果
     *
     * @var bool
     */
    public $distinct = false;

    /**
     * The table which the query is targeting.
     *
     * 查询对象的表
     *
     * @var string
     */
    public $from;

    /**
     * The table joins for the query.
     *
     * 表加入查询
     *
     * @var array
     */
    public $joins;

    /**
     * The where constraints for the query.
     *
     * 查询的where限制
     *
     * @var array
     */
    public $wheres;

    /**
     * The groupings for the query.
     *
     * 查询的编组
     *
     * @var array
     */
    public $groups;

    /**
     * The having constraints for the query.
     *
     * 查询的having限制
     *
     * @var array
     */
    public $havings;

    /**
     * The orderings for the query.
     *
     * 查询的排序
     *
     * @var array
     */
    public $orders;

    /**
     * The maximum number of records to return.
     *
     * 返回的最大记录数
     *
     * @var int
     */
    public $limit;

    /**
     * The number of records to skip.
     *
     * 要跳过的记录数
     *
     * @var int
     */
    public $offset;

    /**
     * The query union statements.
     *
     * 查询联合报表
     *
     * @var array
     */
    public $unions;

    /**
     * The maximum number of union records to return.
     *
     * 返回的联合记录的最大数目
     *
     * @var int
     */
    public $unionLimit;

    /**
     * The number of union records to skip.
     *
     * 要跳过的联合记录的数目
     *
     * @var int
     */
    public $unionOffset;

    /**
     * The orderings for the union query.
     *
     * 联合查询的排序
     *
     * @var array
     */
    public $unionOrders;

    /**
     * Indicates whether row locking is being used.
     *
     * 指示是否正在使用行锁
     *
     * @var string|bool
     */
    public $lock;

    /**
     * All of the available clause operators.
     *
     * 所有可用的子句运算符
     *
     * @var array
     */
    public $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'like binary', 'not like', 'between', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * Whether use write pdo for select.
     *
     * 是否使用写PDO的选择
     *
     * @var bool
     */
    public $useWritePdo = false;

    /**
     * Create a new query builder instance.
	 *
	 * 创建一个新的查询构造器实例
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  \Illuminate\Database\Query\Grammars\Grammar  $grammar
     * @param  \Illuminate\Database\Query\Processors\Processor  $processor
     * @return void
     */
    public function __construct(ConnectionInterface $connection,//连接接口
                                Grammar $grammar = null,//语法
                                Processor $processor = null)//处理器
    {
        $this->connection = $connection;
        $this->grammar = $grammar ?: $connection->getQueryGrammar(); //获取连接所使用的查询语法
        $this->processor = $processor ?: $connection->getPostProcessor();//获取连接所使用的查询后处理器
    }

    /**
     * Set the columns to be selected.
     *
     * 设置要选择的列
     *
     * @param  array|mixed  $columns
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    /**
     * Add a new "raw" select expression to the query.
     *
     * 向查询添加新的“原始”选择表达式
     *
     * @param  string  $expression
     * @param  array   $bindings
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function selectRaw($expression, array $bindings = [])
    {
        //向查询添加新的选择列(创建新的原始查询表达式)
        $this->addSelect(new Expression($expression));

        if ($bindings) {
            //向查询添加绑定
            $this->addBinding($bindings, 'select');
        }

        return $this;
    }

    /**
     * Add a subselect expression to the query.
     *
     * 添加一个subselect表达式查询
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  string  $as
     * @return \Illuminate\Database\Query\Builder|static
     *
     * @throws \InvalidArgumentException
     */
    public function selectSub($query, $as)
    {
        // If the given query is a Closure, we will execute it while passing in a new
        // query instance to the Closure. This will give the developer a chance to
        // format and work with the query before we cast it to a raw SQL string.
        //
        // 如果给定的查询是闭包，我们将在将新查询实例传递给闭包时执行它
        // 这将给开发人员提供格式化和使用查询的机会，然后再将其转换为原始sql字符串
        //
        if ($query instanceof Closure) {
            $callback = $query;
            //                 获取查询生成器的新实例
            $callback($query = $this->newQuery());
        }

        // Here, we will parse this query into an SQL string and an array of bindings
        // so we can add it to the query builder using the selectRaw method so the
        // query is included in the real SQL generated by this builder instance.
        //
        // 在这里，我们将解析该查询在SQL字符串和数组绑定的所以我们可以把它添加到使用selectRaw方法查询包含在这个生成器实例生成实体SQL查询生成器
        //
        //                           将子选择查询解析为sql和绑定
        list($query, $bindings) = $this->parseSubSelect($query);
        //       向查询添加新的“原始”选择表达式
        return $this->selectRaw(
            //                                 在关键字标识符中包装值
            '('.$query.') as '.$this->grammar->wrap($as), $bindings
        );
    }

    /**
     * Parse the sub-select query into SQL and bindings.
     *
     * 将子选择查询解析为sql和绑定
     *
     * @param  mixed  $query
     * @return array
     */
    protected function parseSubSelect($query)
    {
        if ($query instanceof self) {
            //获取查询的sql表示形式           在扁平数组中获取当前查询值绑定
            return [$query->toSql(), $query->getBindings()];
        } elseif (is_string($query)) {
            return [$query, []];
        } else {
            throw new InvalidArgumentException;
        }
    }

    /**
     * Add a new select column to the query.
     *
     * 向查询添加新的选择列
     *
     * @param  array|mixed  $column
     * @return $this
     */
    public function addSelect($column)
    {
        $column = is_array($column) ? $column : func_get_args();

        $this->columns = array_merge((array) $this->columns, $column);

        return $this;
    }

    /**
     * Force the query to only return distinct results.
     *
     * 强制查询只返回不同的结果
     *
     * @return $this
     */
    public function distinct()
    {
        $this->distinct = true;

        return $this;
    }

    /**
     * Set the table which the query is targeting.
	 *
	 * 设置查询对象的表
     *
     * @param  string  $table
     * @return $this
     */
    public function from($table)
    {
        $this->from = $table;

        return $this;
    }

    /**
     * Add a join clause to the query.
     *
     * 向查询添加联接子句
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @param  string  $type
     * @param  bool    $where
     * @return $this
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        //             创建一个新的加入从句实例
        $join = new JoinClause($this, $type, $table);

        // If the first "column" of the join is really a Closure instance the developer
        // is trying to build a join with a complex "on" clause containing more than
        // one condition, so we'll add the join and call a Closure with the query.
        //
        // 如果联接的第一个“列”真的是闭包实例，则开发人员正试图用包含一个以上条件的复杂的“on”子句来构建一个联接，所以我们将添加连接并用查询调用闭包
        //
        if ($first instanceof Closure) {
            call_user_func($first, $join);

            $this->joins[] = $join;
            //向查询添加绑定(在扁平数组中获取当前查询值绑定,)
            $this->addBinding($join->getBindings(), 'join');
        }

        // If the column is simply a string, we can assume the join simply has a basic
        // "on" clause with a single condition. So we will just build the join with
        // this simple join clauses attached to it. There is not a join callback.
        //
        // 如果列仅仅是一个字符串，我们可以假设连接简单地有一个基本的“on”从句与一个条件
        // 因此，我们将只是建立连接与这个简单的连接条款附加到它
        // 没有连接回调
        //
        else {
            $method = $where ? 'where' : 'on';

            $this->joins[] = $join->$method($first, $operator, $second);
            //向查询添加绑定(在扁平数组中获取当前查询值绑定,)
            $this->addBinding($join->getBindings(), 'join');
        }

        return $this;
    }

    /**
     * Add a "join where" clause to the query.
     *
     * 向查询添加“join where”子句
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @param  string  $type
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function joinWhere($table, $first, $operator, $second, $type = 'inner')
    {
        //向查询添加联接子句
        return $this->join($table, $first, $operator, $second, $type, true);
    }

    /**
     * Add a left join to the query.
     *
     * 向查询添加left join子句
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        //向查询添加联接子句
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a "join where" clause to the query.
     *
     * 向查询添加“join where”子句
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function leftJoinWhere($table, $first, $operator, $second)
    {
        //向查询添加联接子句
        return $this->joinWhere($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a right join to the query.
     *
     * 向查询添加right join子句
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        //向查询添加联接子句
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Add a "right join where" clause to the query.
     *
     * 向查询添加“right join where”子句
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function rightJoinWhere($table, $first, $operator, $second)
    {
        //向查询添加“join where”子句
        return $this->joinWhere($table, $first, $operator, $second, 'right');
    }

    /**
     * Add a "cross join" clause to the query.
     *
     * 向查询添加“cross join”子句
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function crossJoin($table, $first = null, $operator = null, $second = null)
    {
        if ($first) {
            //向查询添加联接子句
            return $this->join($table, $first, $operator, $second, 'cross');
        }
        //                    创建一个新的加入从句实例
        $this->joins[] = new JoinClause($this, 'cross', $table);

        return $this;
    }

    /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * 如果给定的“值”为真，则应用回调的查询更改
     *
     * @param  bool  $value
     * @param  \Closure  $callback
     * @param  \Closure  $default
     * @return \Illuminate\Database\Query\Builder
     */
    public function when($value, $callback, $default = null)
    {
        $builder = $this;

        if ($value) {
            $builder = call_user_func($callback, $builder);
        } elseif ($default) {
            $builder = call_user_func($default, $builder);
        }

        return $builder;
    }

    /**
     * Merge an array of where clauses and bindings.
     *
     * 合并WHERE子句和绑定的数组
     *
     * @param  array  $wheres
     * @param  array  $bindings
     * @return void
     */
    public function mergeWheres($wheres, $bindings)
    {
        $this->wheres = array_merge((array) $this->wheres, (array) $wheres);

        $this->bindings['where'] = array_values(
            array_merge($this->bindings['where'], (array) $bindings)
        );
    }

    /**
     * Add a basic where clause to the query.
     *
     * 将基本WHERE子句添加到查询中
     *
     * @param  string|array|\Closure  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
        //
        // 如果列是一个数组，我们将假定它是一个键值对数组，并可以将它们分别添加为WHERE子句
        // 当调用该方法时，我们将保持布尔值，并将其传递到嵌套的
        //
        if (is_array($column)) {
            //将WHERE子句的数组添加到查询中
            return $this->addArrayOfWheres($column, $boolean);
        }

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        //
        // 这里我们将对操作符做一些假设
        // 如果只有2个值传递给该方法，我们将假定操作符是等号并继续进行
        // 否则，我们将要求操作员通过
        //
        //                            为WHERE子句准备值和运算符
        list($value, $operator) = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() == 2
        );

        // If the columns is actually a Closure instance, we will assume the developer
        // wants to begin a nested where statement which is wrapped in parenthesis.
        // We'll add that Closure to the query then return back out immediately.
        //
        // 如果列实际上是闭包实例，我们将假设开发人员希望开始嵌套的语句，其中语句包在括号中
        // 我们将向查询中添加闭包，然后立即返回
        //
        if ($column instanceof Closure) {
            //         向查询添加嵌套语句
            return $this->whereNested($column, $boolean);
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        //
        // 如果在有效的操作符列表中找不到给定的操作符，我们将假设开发人员只是对“=”操作符进行短切，我们将操作符设置为“=”，并适当地设置值
        //
        //确定给定的操作符是否被支持
        if ($this->invalidOperator($operator)) {
            list($value, $operator) = [$operator, '='];
        }

        // If the value is a Closure, it means the developer is performing an entire
        // sub-select within the query and we will need to compile the sub-select
        // within the where clause to get the appropriate query record results.
        //
        // 如果值是闭包，则意味着开发人员在查询中执行整个子选择，我们将需要在WHERE子句中编译子选择以获得相应的查询记录结果
        //
        if ($value instanceof Closure) {
            //添加一个完整的子查询
            return $this->whereSub($column, $operator, $value, $boolean);
        }

        // If the value is "null", we will just assume the developer wants to add a
        // where null clause to the query. So, we will allow a short-cut here to
        // that method for convenience so the developer doesn't have to check.
        //
        // 如果该值为“NULL”，我们将假定开发人员想向查询添加一个null子句
        // 因此，我们将允许一个捷径，以方便的方法，使开发人员不必检查
        //
        if (is_null($value)) {
            //向查询添加“where null”子句
            return $this->whereNull($column, $boolean, $operator != '=');
        }

        // If the column is making a JSON reference we'll check to see if the value
        // is a boolean. If it is, we'll add the raw boolean string as an actual
        // value to the query to ensure this is properly handled by the query.
        //
        // 如果列正在生成JSON引用，我们将检查该值是否为布尔值
        // 如果是，我们将将原始布尔字符串作为一个实际值添加到查询中，以确保查询正确地处理该字符串
        //
        //  确定一个给定的字符串包含另一个字符串
        if (Str::contains($column, '->') && is_bool($value)) {
            $value = new Expression($value ? 'true' : 'false');//创建新的原始查询表达式
        }

        // Now that we are working with just a simple query we can put the elements
        // in our array and add the query binding to our array of bindings that
        // will be bound to each SQL statements when it is finally executed.
        //
        // 现在，我们正在使用一个简单的查询，我们可以把元素放在数组中，并将查询绑定添加到绑定数组，这些绑定数组将在最终执行时绑定到每个sql语句
        //
        $type = 'Basic';

        $this->wheres[] = compact(
            'type', 'column', 'operator', 'value', 'boolean'
        );

        if (! $value instanceof Expression) {
            //向查询添加绑定
            $this->addBinding($value, 'where');
        }

        return $this;
    }

    /**
     * Add an array of where clauses to the query.
     *
     * 将WHERE子句的数组添加到查询中
     *
     * @param  array  $column
     * @param  string  $boolean
     * @param  string  $method
     * @return $this
     */
    protected function addArrayOfWheres($column, $boolean, $method = 'where')
    {
        //            向查询添加嵌套语句
        return $this->whereNested(function ($query) use ($column, $method) {
            foreach ($column as $key => $value) {
                if (is_numeric($key) && is_array($value)) {
                    $query->{$method}(...array_values($value));
                } else {
                    $query->$method($key, '=', $value);
                }
            }
        }, $boolean);
    }

    /**
     * Prepare the value and operator for a where clause.
     *
     * 为WHERE子句准备值和运算符
     *
     * @param  string  $value
     * @param  string  $operator
     * @param  bool  $useDefault
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function prepareValueAndOperator($value, $operator, $useDefault = false)
    {
        if ($useDefault) {
            return [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {//确定给定的运算符和值组合是否合法
            throw new InvalidArgumentException('Illegal operator and value combination.');
        }

        return [$value, $operator];
    }

    /**
     * Determine if the given operator and value combination is legal.
     *
     * 确定给定的运算符和值组合是否合法
     *
     * Prevents using Null values with invalid operators.
     *
     * 防止使用无效的运算符空值
     *
     * @param  string  $operator
     * @param  mixed  $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        return is_null($value) && in_array($operator, $this->operators) &&
             ! in_array($operator, ['=', '<>', '!=']);
    }

    /**
     * Determine if the given operator is supported.
     *
     * 确定给定的操作符是否被支持
     *
     * @param  string  $operator
     * @return bool
     */
    protected function invalidOperator($operator)
    {
        return ! in_array(strtolower($operator), $this->operators, true) &&
            //                                             从语句中移除领先的布尔值
               ! in_array(strtolower($operator), $this->grammar->getOperators(), true);
    }

    /**
     * Add an "or where" clause to the query.
     *
     * 向查询添加“or where”子句
     *
     * @param  \Closure|string  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        //将基本WHERE子句添加到查询中
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Add a "where" clause comparing two columns to the query.
     *
     * 向查询中添加“where”子句比较两列
     *
     * @param  string|array  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string|null  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
        //
        // 如果列是一个数组，我们将假定它是一个键值对数组，并可以将它们分别添加为WHERE子句
        // 当调用该方法时，我们将保持布尔值，并将其传递到嵌套的
        //
        if (is_array($first)) {
            //         将WHERE子句的数组添加到查询中
            return $this->addArrayOfWheres($first, $boolean, 'whereColumn');
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        //
        // 如果在有效的操作符列表中找不到给定的操作符，我们将假设开发人员只是对“=”操作符进行短切，我们将将操作符设置为“=”，并适当地设置值
        //
        if ($this->invalidOperator($operator)) {//确定给定的操作符是否被支持
            list($second, $operator) = [$operator, '='];
        }

        // Finally, we will add this where clause into this array of clauses that we
        // are building for the query. All of them will be compiled via a grammar
        // once the query is about to be executed and run against the database.
        //
        // 最后，我们将WHERE子句添加到我们为查询构建的子句数组中
        // 所有这些将被编译通过语法一旦查询将要执行和运行对数据库
        //
        $type = 'Column';

        $this->wheres[] = compact(
            'type', 'first', 'operator', 'second', 'boolean'
        );

        return $this;
    }

    /**
     * Add an "or where" clause comparing two columns to the query.
     *
     * 向查询中添加“or where”子句比较两列
     *
     * @param  string|array  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereColumn($first, $operator = null, $second = null)
    {
        //向查询中添加“where”子句比较两列
        return $this->whereColumn($first, $operator, $second, 'or');
    }

    /**
     * Add a raw where clause to the query.
     *
     * 向查询添加原始where子句
     *
     * @param  string  $sql
     * @param  mixed   $bindings
     * @param  string  $boolean
     * @return $this
     */
    public function whereRaw($sql, $bindings = [], $boolean = 'and')
    {
        $this->wheres[] = ['type' => 'raw', 'sql' => $sql, 'boolean' => $boolean];
        //向查询添加绑定
        $this->addBinding((array) $bindings, 'where');

        return $this;
    }

    /**
     * Add a raw or where clause to the query.
     *
     * 向查询添加原始or where子句
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereRaw($sql, array $bindings = [])
    {
        //向查询添加原始where子句
        return $this->whereRaw($sql, $bindings, 'or');
    }

    /**
     * Add a "where in" clause to the query.
     *
     * 在查询中添加“where in”子句
     *
     * @param  string  $column
     * @param  mixed   $values
     * @param  string  $boolean
     * @param  bool    $not
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotIn' : 'In';

        // If the value is a query builder instance we will assume the developer wants to
        // look for any values that exists within this given query. So we will add the
        // query accordingly so that this query is properly executed when it is run.
        //
        // 如果该值是一个查询生成器实例，我们将假设开发人员希望查找给定查询中存在的任何值
        // 因此，我们将相应地添加查询，以便在运行时正确执行此查询
        //
        if ($values instanceof static) {
            return $this->whereInExistingQuery(//向查询添加外部子select选项
                $column, $values, $boolean, $not
            );
        }

        // If the value of the where in clause is actually a Closure, we will assume that
        // the developer is using a full sub-select for this "in" statement, and will
        // execute those Closures, then we can re-construct the entire sub-selects.
        //
        // 如果子句中的值实际上是闭包，那么我们将假设开发人员正在为这个“in”语句使用一个完整的子选择，并且将执行这些闭包，然后我们可以重新构建整个子选择
        //
        if ($values instanceof Closure) {
            //        添加一个where in与子select的查询
            return $this->whereInSub($column, $values, $boolean, $not);
        }

        // Next, if the value is Arrayable we need to cast it to its raw array form so we
        // have the underlying array value instead of an Arrayable object which is not
        // able to be added as a binding, etc. We will then add to the wheres array.
        //
        // 其次，如果值是Arrayable我们需要将其原数组形式所以我们底层数组的值而不是一个Arrayable对象不能够被添加为绑定，等我们将添加到wheres阵列
        //
        if ($values instanceof Arrayable) {
            $values = $values->toArray();//获取数组实例
        }

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        // Finally we'll add a binding for each values unless that value is an expression
        // in which case we will just skip over it since it will be the query as a raw
        // string and not as a parameterized place-holder to be replaced by the PDO.
        //
        // 最后，我们将添加一个绑定的每个值，除非价值是一个表达式，在这种情况下，我们会跳过它，因为它将查询作为原始字符串，而不是作为一个参数占位符被替换的PDO
        //
        foreach ($values as $value) {
            if (! $value instanceof Expression) {
                //向查询添加绑定
                $this->addBinding($value, 'where');
            }
        }

        return $this;
    }

    /**
     * Add an "or where in" clause to the query.
     *
     * 在查询中添加“or where in”子句
     *
     * @param  string  $column
     * @param  mixed   $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereIn($column, $values)
    {
        //在查询中添加“where in”子句
        return $this->whereIn($column, $values, 'or');
    }

    /**
     * Add a "where not in" clause to the query.
     *
     * 在查询中添加“where not in”子句
     *
     * @param  string  $column
     * @param  mixed   $values
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        //在查询中添加“where in”子句
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add an "or where not in" clause to the query.
     *
     * 在查询中添加“or where not in”子句
     *
     * @param  string  $column
     * @param  mixed   $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereNotIn($column, $values)
    {
        //在查询中添加“where not in”子句
        return $this->whereNotIn($column, $values, 'or');
    }

    /**
     * Add a where in with a sub-select to the query.
     *
     * 添加一个where in与子select的查询
     *
     * @param  string   $column
     * @param  \Closure $callback
     * @param  string   $boolean
     * @param  bool     $not
     * @return $this
     */
    protected function whereInSub($column, Closure $callback, $boolean, $not)
    {
        $type = $not ? 'NotInSub' : 'InSub';

        // To create the exists sub-select, we will actually create a query and call the
        // provided callback with the query so the developer may set any of the query
        // conditions they want for the in clause, then we'll put it in this array.
        //
        // 要创建存在的子select，我们将创建一个查询，并调用所提供的回调与查询，所以开发人员可以设置任何查询条件，他们想要的条款，然后我们将把它放在这个数组
        //
        //                                       获取查询生成器的新实例
        call_user_func($callback, $query = $this->newQuery());

        $this->wheres[] = compact('type', 'column', 'query', 'boolean');
        //向查询添加绑定(在扁平数组中获取当前查询值绑定,)
        $this->addBinding($query->getBindings(), 'where');

        return $this;
    }

    /**
     * Add an external sub-select to the query.
     *
     * 向查询添加外部子select选项
     *
     * @param  string   $column
     * @param  \Illuminate\Database\Query\Builder|static  $query
     * @param  string   $boolean
     * @param  bool     $not
     * @return $this
     */
    protected function whereInExistingQuery($column, $query, $boolean, $not)
    {
        $type = $not ? 'NotInSub' : 'InSub';

        $this->wheres[] = compact('type', 'column', 'query', 'boolean');
        //向查询添加绑定(在扁平数组中获取当前查询值绑定,)
        $this->addBinding($query->getBindings(), 'where');

        return $this;
    }

    /**
     * Add a "where null" clause to the query.
     *
     * 向查询添加“where null”子句
     *
     * @param  string  $column
     * @param  string  $boolean
     * @param  bool    $not
     * @return $this
     */
    public function whereNull($column, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotNull' : 'Null';

        $this->wheres[] = compact('type', 'column', 'boolean');

        return $this;
    }

    /**
     * Add an "or where null" clause to the query.
     *
     * 向查询添加“or where null”子句
     *
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereNull($column)
    {
        //向查询添加“where null”子句
        return $this->whereNull($column, 'or');
    }

    /**
     * Add a "where not null" clause to the query.
     *
     * 向查询添加“where not null”子句
     *
     * @param  string  $column
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereNotNull($column, $boolean = 'and')
    {
        //向查询添加“where null”子句
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * Add a where between statement to the query.
     *
     * 向查询添加where between语句
     *
     * @param  string  $column
     * @param  array   $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $type = 'between';

        $this->wheres[] = compact('column', 'type', 'boolean', 'not');
        //向查询添加绑定
        $this->addBinding($values, 'where');

        return $this;
    }

    /**
     * Add an or where between statement to the query.
     *
     * 向查询添加or where between语句
     *
     * @param  string  $column
     * @param  array   $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereBetween($column, array $values)
    {
        //向查询添加where between语句
        return $this->whereBetween($column, $values, 'or');
    }

    /**
     * Add a where not between statement to the query.
     *
     * 向查询添加where not between语句
     *
     * @param  string  $column
     * @param  array   $values
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereNotBetween($column, array $values, $boolean = 'and')
    {
        //向查询添加where between语句
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * Add an or where not between statement to the query.
     *
     * 向查询添加or where not between语句
     *
     * @param  string  $column
     * @param  array   $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereNotBetween($column, array $values)
    {
        //向查询添加where not between语句
        return $this->whereNotBetween($column, $values, 'or');
    }

    /**
     * Add an "or where not null" clause to the query.
     *
     * 向查询添加or where not null语句
     *
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereNotNull($column)
    {
        //向查询添加“where not null”子句
        return $this->whereNotNull($column, 'or');
    }

    /**
     * Add a "where date" statement to the query.
     *
     * 向查询添加“where date”子句
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereDate($column, $operator, $value = null, $boolean = 'and')
    {
        list($value, $operator) = $this->prepareValueAndOperator(//为WHERE子句准备值和运算符
            $value, $operator, func_num_args() == 2
        );
        //向查询添加基于日期的（年、月、日、时）语句
        return $this->addDateBasedWhere('Date', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where date" statement to the query.
     *
     * 向查询添加“or where date”子句
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereDate($column, $operator, $value)
    {
        //向查询添加“where date”子句
        return $this->whereDate($column, $operator, $value, 'or');
    }

    /**
     * Add a "where time" statement to the query.
     *
     * 向查询添加“where time”子句
     *
     * @param  string  $column
     * @param  string   $operator
     * @param  int   $value
     * @param  string   $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereTime($column, $operator, $value, $boolean = 'and')
    {
        //向查询添加基于日期的（年、月、日、时）语句
        return $this->addDateBasedWhere('Time', $column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where time" statement to the query.
     *
     * 向查询添加“or where time”子句
     *
     * @param  string  $column
     * @param  string   $operator
     * @param  int   $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereTime($column, $operator, $value)
    {
        //向查询添加“where time”子句
        return $this->whereTime($column, $operator, $value, 'or');
    }

    /**
     * Add a "where day" statement to the query.
     *
     * 向查询添加“where day”子句
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereDay($column, $operator, $value = null, $boolean = 'and')
    {
        //                             为WHERE子句准备值和运算符
        list($value, $operator) = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() == 2
        );
        //向查询添加基于日期的（年、月、日、时）语句
        return $this->addDateBasedWhere('Day', $column, $operator, $value, $boolean);
    }

    /**
     * Add a "where month" statement to the query.
     *
     * 向查询添加“where month”子句
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereMonth($column, $operator, $value = null, $boolean = 'and')
    {
        //                             为WHERE子句准备值和运算符
        list($value, $operator) = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() == 2
        );
        //向查询添加基于日期的（年、月、日、时）语句
        return $this->addDateBasedWhere('Month', $column, $operator, $value, $boolean);
    }

    /**
     * Add a "where year" statement to the query.
     *
     * 向查询添加“where year”子句
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereYear($column, $operator, $value = null, $boolean = 'and')
    {
        //                             为WHERE子句准备值和运算符
        list($value, $operator) = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() == 2
        );
        //向查询添加基于日期的（年、月、日、时）语句
        return $this->addDateBasedWhere('Year', $column, $operator, $value, $boolean);
    }

    /**
     * Add a date based (year, month, day, time) statement to the query.
     *
     * 向查询添加基于日期的（年、月、日、时）语句
     *
     * @param  string  $type
     * @param  string  $column
     * @param  string  $operator
     * @param  int  $value
     * @param  string  $boolean
     * @return $this
     */
    protected function addDateBasedWhere($type, $column, $operator, $value, $boolean = 'and')
    {
        $this->wheres[] = compact('column', 'type', 'boolean', 'operator', 'value');
        // 向查询添加绑定
        $this->addBinding($value, 'where');

        return $this;
    }

    /**
     * Add a nested where statement to the query.
     *
     * 向查询添加嵌套语句
     *
     * @param  \Closure $callback
     * @param  string   $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereNested(Closure $callback, $boolean = 'and')
    {
        //                                  为嵌套的条件创建新的查询实例
        call_user_func($callback, $query = $this->forNestedWhere());
        //将另一个查询生成器作为嵌套在查询生成器中
        return $this->addNestedWhereQuery($query, $boolean);
    }

    /**
     * Create a new query instance for nested where condition.
     *
     * 为嵌套的条件创建新的查询实例
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function forNestedWhere()
    {
        //获取查询生成器的新实例->设置查询对象的表
        return $this->newQuery()->from($this->from);
    }

    /**
     * Add another query builder as a nested where to the query builder.
     *
     * 将另一个查询生成器作为嵌套在查询生成器中
     *
     * @param  \Illuminate\Database\Query\Builder|static $query
     * @param  string  $boolean
     * @return $this
     */
    public function addNestedWhereQuery($query, $boolean = 'and')
    {
        if (count($query->wheres)) {
            $type = 'Nested';

            $this->wheres[] = compact('type', 'query', 'boolean');
            //向查询添加绑定(在扁平数组中获取当前查询值绑定,)
            $this->addBinding($query->getBindings(), 'where');
        }

        return $this;
    }

    /**
     * Add a full sub-select to the query.
     *
     * 添加一个完整的子查询
     *
     * @param  string   $column
     * @param  string   $operator
     * @param  \Closure $callback
     * @param  string   $boolean
     * @return $this
     */
    protected function whereSub($column, $operator, Closure $callback, $boolean)
    {
        $type = 'Sub';

        // Once we have the query instance we can simply execute it so it can add all
        // of the sub-select's conditions to itself, and then we can cache it off
        // in the array of where clauses for the "main" parent query instance.
        //
        // 一旦我们拥有了查询实例，我们就可以简单地执行它，这样就可以将所有子选择的条件添加到自己，然后我们可以在“main”父查询实例的WHERE子句数组中缓存它
        //
        //                                   获取查询生成器的新实例
        call_user_func($callback, $query = $this->newQuery());

        $this->wheres[] = compact(
            'type', 'column', 'operator', 'query', 'boolean'
        );
        //向查询添加绑定(在扁平数组中获取当前查询值绑定,)
        $this->addBinding($query->getBindings(), 'where');

        return $this;
    }

    /**
     * Add an exists clause to the query.
     *
     * 向查询添加一个exists子句
     *
     * @param  \Closure $callback
     * @param  string   $boolean
     * @param  bool     $not
     * @return $this
     */
    public function whereExists(Closure $callback, $boolean = 'and', $not = false)
    {
        //获取查询生成器的新实例
        $query = $this->newQuery();

        // Similar to the sub-select clause, we will create a new query instance so
        // the developer may cleanly specify the entire exists query and we will
        // compile the whole thing in the grammar and insert it into the SQL.
        //
        // 与子select子句类似，我们将创建一个新的查询实例，以便开发人员可以清晰地指定整个exists的查询，并且我们将在语法中编译整个对象并将其插入SQL中
        //
        call_user_func($callback, $query);
        //向查询添加一个exists子句
        return $this->addWhereExistsQuery($query, $boolean, $not);
    }

    /**
     * Add an or exists clause to the query.
     *
     * 向查询添加一个or exists子句
     *
     * @param  \Closure $callback
     * @param  bool     $not
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereExists(Closure $callback, $not = false)
    {
        //向查询添加一个exists子句
        return $this->whereExists($callback, 'or', $not);
    }

    /**
     * Add a where not exists clause to the query.
     *
     * 向查询添加一个where not exists子句
     *
     * @param  \Closure $callback
     * @param  string   $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function whereNotExists(Closure $callback, $boolean = 'and')
    {
        //向查询添加一个exists子句
        return $this->whereExists($callback, $boolean, true);
    }

    /**
     * Add a where not exists clause to the query.
     *
     * 向查询添加一个where not exists子句
     *
     * @param  \Closure  $callback
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orWhereNotExists(Closure $callback)
    {
        //向查询添加一个or exists子句
        return $this->orWhereExists($callback, true);
    }

    /**
     * Add an exists clause to the query.
     *
     * 向查询添加一个exists子句
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function addWhereExistsQuery(Builder $query, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotExists' : 'Exists';

        $this->wheres[] = compact('type', 'operator', 'query', 'boolean');
        //向查询添加绑定(在扁平数组中获取当前查询值绑定,)
        $this->addBinding($query->getBindings(), 'where');

        return $this;
    }

    /**
     * Handles dynamic "where" clauses to the query.
     *
     * 处理查询的动态“where”子句
     *
     * @param  string  $method
     * @param  string  $parameters
     * @return $this
     */
    public function dynamicWhere($method, $parameters)
    {
        $finder = substr($method, 5);

        $segments = preg_split(
            '/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE
        );

        // The connector variable will determine which connector will be used for the
        // query condition. We will change it as we come across new boolean values
        // in the dynamic method strings, which could contain a number of these.
        //
        // 连接器变量将确定哪些连接器将用于查询条件
        // 我们将改变当我们遇到新的布尔值动态方法的字符串,这可能包含这些。
        //
        $connector = 'and';

        $index = 0;

        foreach ($segments as $segment) {
            // If the segment is not a boolean connector, we can assume it is a column's name
            // and we will add it to the query as a new constraint as a where clause, then
            // we can keep iterating through the dynamic method string's segments again.
            //
            // 如果段不是一个布尔连接器，我们可以假设它是一个列的名称，我们将它添加到查询作为一个新的约束作为WHERE子句，然后我们可以继续迭代通过动态方法字符串的段了
            //
            if ($segment != 'And' && $segment != 'Or') {
                //将WHERE语句中的单个动态添加到查询中
                $this->addDynamic($segment, $connector, $parameters, $index);

                $index++;
            }

            // Otherwise, we will store the connector so we know how the next where clause we
            // find in the query should be connected to the previous ones, meaning we will
            // have the proper boolean connector to connect the next where clause found.
            //
            // 否则，我们将存储连接器，这样我们就知道查询中的下一个WHERE子句应该与前一个连接在一起了，这意味着我们将有合适的布尔连接器来连接下一个WHERE子句
            //
            else {
                $connector = $segment;
            }
        }

        return $this;
    }

    /**
     * Add a single dynamic where clause statement to the query.
     *
     * 将WHERE语句中的单个动态添加到查询中
     *
     * @param  string  $segment
     * @param  string  $connector
     * @param  array   $parameters
     * @param  int     $index
     * @return void
     */
    protected function addDynamic($segment, $connector, $parameters, $index)
    {
        // Once we have parsed out the columns and formatted the boolean operators we
        // are ready to add it to this query as a where clause just like any other
        // clause on the query. Then we'll increment the parameter index values.
        //
        // 一旦我们分析了列并格式化了布尔操作符，我们就准备将它作为WHERE子句添加到这个查询中，就像查询上的任何其他子句一样
        // 然后增加参数索引值
        //
        $bool = strtolower($connector);
        //将基本WHERE子句添加到查询中(将字符串转换为蛇形命名,)
        $this->where(Str::snake($segment), '=', $parameters[$index], $bool);
    }

    /**
     * Add a "group by" clause to the query.
     *
     * 向查询添加一个“group by”子句
     *
     * @param  array  ...$groups
     * @return $this
     */
    public function groupBy(...$groups)
    {
        foreach ($groups as $group) {
            $this->groups = array_merge(
                (array) $this->groups,
                array_wrap($group)//如果给定值不是数组，请将其包在一个数组中
            );
        }

        return $this;
    }

    /**
     * Add a "having" clause to the query.
     *
     * 向查询添加一个“having”子句
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @param  string  $boolean
     * @return $this
     */
    public function having($column, $operator = null, $value = null, $boolean = 'and')
    {
        $type = 'Basic';

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        //
        // 这里我们将对操作符做一些假设
        // 如果只有2个值传递给该方法，我们将假定操作符是等号并继续进行
        // 否则，我们将要求操作员通过
        //
        //                              为WHERE子句准备值和运算符
        list($value, $operator) = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() == 2
        );

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        //
        // 如果在有效的操作符列表中找不到给定的操作符，我们将假设开发人员只是对“=”操作符进行短切，我们将将操作符设置为“=”，并适当地设置值
        //
        // 确定给定的操作符是否被支持
        if ($this->invalidOperator($operator)) {
            list($value, $operator) = [$operator, '='];
        }

        $this->havings[] = compact('type', 'column', 'operator', 'value', 'boolean');

        if (! $value instanceof Expression) {
            //向查询添加绑定
            $this->addBinding($value, 'having');
        }

        return $this;
    }

    /**
     * Add a "or having" clause to the query.
     *
     * 向查询添加一个“or having”子句
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orHaving($column, $operator = null, $value = null)
    {
        //向查询添加一个“having”子句
        return $this->having($column, $operator, $value, 'or');
    }

    /**
     * Add a raw having clause to the query.
     *
     * 向查询添加原始having子句
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @param  string  $boolean
     * @return $this
     */
    public function havingRaw($sql, array $bindings = [], $boolean = 'and')
    {
        $type = 'Raw';

        $this->havings[] = compact('type', 'sql', 'boolean');
        //向查询添加绑定
        $this->addBinding($bindings, 'having');

        return $this;
    }

    /**
     * Add a raw or having clause to the query.
     *
     * 向查询添加原始or having子句
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function orHavingRaw($sql, array $bindings = [])
    {
        //向查询添加原始having子句
        return $this->havingRaw($sql, $bindings, 'or');
    }

    /**
     * Add an "order by" clause to the query.
     *
     * 向查询添加一个“order by”子句
     *
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = [
            'column' => $column,
            'direction' => strtolower($direction) == 'asc' ? 'asc' : 'desc',
        ];

        return $this;
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * 为查询的时间戳添加“order by”子句
     *
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function latest($column = 'created_at')
    {
        //向查询添加一个“order by”子句
        return $this->orderBy($column, 'desc');
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * 为查询的时间戳添加“order by”子句
     *
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function oldest($column = 'created_at')
    {
        //向查询添加一个“order by”子句
        return $this->orderBy($column, 'asc');
    }

    /**
     * Put the query's results in random order.
     *
     * 将查询结果随机排列
     *
     * @param  string  $seed
     * @return $this
     */
    public function inRandomOrder($seed = '')
    {
        //将原始的“order by”子句添加到查询中(将随机语句编译为sql)
        return $this->orderByRaw($this->grammar->compileRandom($seed));
    }

    /**
     * Add a raw "order by" clause to the query.
     *
     * 将原始的“order by”子句添加到查询中
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @return $this
     */
    public function orderByRaw($sql, $bindings = [])
    {
        $type = 'Raw';

        $this->{$this->unions ? 'unionOrders' : 'orders'}[] = compact('type', 'sql');
        //向查询添加绑定
        $this->addBinding($bindings, 'order');

        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
     *
     * 别名设置查询的“offset”值
     *
     * @param  int  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function skip($value)
    {
        //向查询添加一个“offset”值
        return $this->offset($value);
    }

    /**
     * Set the "offset" value of the query.
     *
     * 向查询设置“offset”值
     *
     * @param  int  $value
     * @return $this
     */
    public function offset($value)
    {
        $property = $this->unions ? 'unionOffset' : 'offset';

        $this->$property = max(0, $value);

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     *
     * 别名设置查询的“limit”值
     *
     * @param  int  $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Set the "limit" value of the query.
     *
     * 向查询设置“limit”值
     *
     * @param  int  $value
     * @return $this
     */
    public function limit($value)
    {
        $property = $this->unions ? 'unionLimit' : 'limit';

        if ($value >= 0) {
            $this->$property = $value;
        }

        return $this;
    }

    /**
     * Set the limit and offset for a given page.
     *
     * 设置给定页的限制和偏移量
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function forPage($page, $perPage = 15)
    {
        //别名设置查询的“offset”值->别名设置查询的“limit”值
        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }

    /**
     * Constrain the query to the next "page" of results after a given ID.
     *
     * 将查询限制到给定ID后的结果的下一个“页面”
     *
     * @param  int  $perPage
     * @param  int  $lastId
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function forPageAfterId($perPage = 15, $lastId = 0, $column = 'id')
    {
        //获取给定列移除的所有订单的数组订单
        $this->orders = $this->removeExistingOrdersFor($column);
        //将基本WHERE子句添加到查询中
        return $this->where($column, '>', $lastId)
                    ->orderBy($column, 'asc')//向查询添加一个“order by”子句
                    ->take($perPage);//别名设置查询的“limit”值
    }

    /**
     * Get an array orders with all orders for an given column removed.
     *
     * 获取给定列移除的所有订单的数组订单
     *
     * @param  string  $column
     * @return array
     */
    protected function removeExistingOrdersFor($column)
    {
        return Collection::make($this->orders)//创建一个新的集合实例，如果该值不是一个准备好的
                    //创建不通过给定的真值测试的所有元素的集合
                    ->reject(function ($order) use ($column) {
                        return $order['column'] === $column;
                    })->values()->all();//重置基础阵列上的键->获取集合中的所有项目
    }

    /**
     * Add a union statement to the query.
     *
     * 向查询添加union语句
     *
     * @param  \Illuminate\Database\Query\Builder|\Closure  $query
     * @param  bool  $all
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function union($query, $all = false)
    {
        if ($query instanceof Closure) {
            //                       获取查询生成器的新实例
            call_user_func($query, $query = $this->newQuery());
        }

        $this->unions[] = compact('query', 'all');
        //向查询添加绑定            在扁平数组中获取当前查询值绑定
        $this->addBinding($query->getBindings(), 'union');

        return $this;
    }

    /**
     * Add a union all statement to the query.
     *
     * 向查询添加union all语句
     *
     * @param  \Illuminate\Database\Query\Builder|\Closure  $query
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function unionAll($query)
    {
        //向查询添加union语句
        return $this->union($query, true);
    }

    /**
     * Lock the selected rows in the table.
     *
     * 锁定表中选定的行
     *
     * @param  string|bool  $value
     * @return $this
     */
    public function lock($value = true)
    {
        $this->lock = $value;

        if (! is_null($this->lock)) {
            $this->useWritePdo(); //用写的PDO的查询
        }

        return $this;
    }

    /**
     * Lock the selected rows in the table for updating.
     *
     * 锁定表中选定的行进行更新
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function lockForUpdate()
    {
        return $this->lock(true);//锁定表中选定的行
    }

    /**
     * Share lock the selected rows in the table.
     *
     * 共享锁定表中选定的行
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function sharedLock()
    {
        return $this->lock(false);//锁定表中选定的行
    }

    /**
     * Get the SQL representation of the query.
	 *
	 * 获取查询的sql表示形式
     *
     * @return string
     */
    public function toSql()
    {
		//\Illuminate\Database\Query\Grammars\Grammar::compileSelect()将SELECT查询编译为sql
        return $this->grammar->compileSelect($this);
    }

    /**
     * Execute a query for a single record by ID.
     *
     * 通过ID执行单个记录的查询
     *
     * @param  int    $id
     * @param  array  $columns
     * @return mixed|static
     */
    public function find($id, $columns = ['*'])
    {
        //           将基本WHERE子句添加到查询中   执行查询和得到的第一个结果
        return $this->where('id', '=', $id)->first($columns);
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * 从查询的第一个结果中获取单个列的值
     *
     * @param  string  $column
     * @return mixed
     */
    public function value($column)
    {
        $result = (array) $this->first([$column]); //执行查询和得到的第一个结果

        return count($result) > 0 ? reset($result) : null;
    }

    /**
     * Execute the query and get the first result.
     *
     * 执行查询和得到的第一个结果
     *
     * @param  array   $columns
     * @return \stdClass|array|null
     */
    public function first($columns = ['*'])
    {
        //别名设置查询的“limit”值->将查询执行为“SELECT”语句->Collectiona::first() 从集合中获取第一项
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Execute the query as a "select" statement.
	 *
	 * 将查询执行为“SELECT”语句
     *
     * @param  array  $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*'])
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }
		//                      处理“select”查询的结果(this,通过连接执行select语句，获取数据库查询结果)
        $results = $this->processor->processSelect($this, $this->runSelect());

        $this->columns = $original;

        return collect($results);
    }

    /**
     * Run the query as a "select" statement against the connection.
	 *
	 * 将查询作为对连接的“SELECT”语句运行
	 * * 通过连接执行select语句，获取数据库查询结果
     *
     * @return array
     */
    protected function runSelect()
    {
        return $this->connection->select(//对数据库运行SELECT语句
        	//获取查询的sql表示形式   在扁平数组中获取当前查询值绑定
            $this->toSql(), $this->getBindings(), ! $this->useWritePdo
        );
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * 给定查询到一个简单的paginator分页器
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        //                    解析当前页或返回默认值
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        //              得到的分页程序的总记录数
        $total = $this->getCountForPagination($columns);
        //                      设置给定页的限制和偏移量         将查询执行为“SELECT”语句   通过给定的值创建集合对象
        $results = $total ? $this->forPage($page, $perPage)->get($columns) : collect();
        //创建一个新的页面实例
        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),//解决当前请求路径或返回默认值
            'pageName' => $pageName,
        ]);
    }

    /**
     * Get a paginator only supporting simple next and previous links.
     *
     * 得到一个页面只支持简单的上一页和下一页链接
     *
     * This is more efficient on larger data-sets, etc.
     *
     * 这在较大的数据集上更有效
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        //                             解析当前页或返回默认值
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        //别名设置查询的“offset”值              别名设置查询的“limit”值
        $this->skip(($page - 1) * $perPage)->take($perPage + 1);
        //                         将查询执行为“SELECT”语句
        return new Paginator($this->get($columns), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),//解决当前请求路径或返回默认值
            'pageName' => $pageName,
        ]);
    }

    /**
     * Get the count of the total records for the paginator.
     *
     * 得到的分页程序的总记录数
     *
     * @param  array  $columns
     * @return int
     */
    public function getCountForPagination($columns = ['*'])
    {
        //                   运行一个分页计数查询
        $results = $this->runPaginationCountQuery($columns);

        // Once we have run the pagination count query, we will get the resulting count and
        // take into account what type of query it was. When there is a group by we will
        // just return the count of the entire results set since that will be correct.
        //
        // 一旦我们运行分页计数查询，我们将得到计数和考虑什么类型的查询是
        // 当有一个组，我们将返回整个结果集的计数，因为这将是正确的
        //
        if (isset($this->groups)) {
            return count($results);
        } elseif (! isset($results[0])) {
            return 0;
        } elseif (is_object($results[0])) {
            return (int) $results[0]->aggregate;
        } else {
            return (int) array_change_key_case((array) $results[0])['aggregate'];
        }
    }

    /**
     * Run a pagination count query.
     *
     * 运行一个分页计数查询
     *
     * @param  array  $columns
     * @return array
     */
    protected function runPaginationCountQuery($columns = ['*'])
    {
        return $this->cloneWithout(['columns', 'orders', 'limit', 'offset'])//在没有给定属性的情况下克隆查询
                    ->cloneWithoutBindings(['select', 'order'])//在没有给定绑定的情况下克隆查询
                    ->setAggregate('count', $this->withoutSelectAliases($columns))//设置聚合属性而不运行查询(,删除列别名，因为它们将中断计数查询)
                    ->get()->all();//将查询执行为“SELECT”语句->获取集合中的所有项目
    }

    /**
     * Remove the column aliases since they will break count queries.
     *
     * 删除列别名，因为它们将中断计数查询
     *
     * @param  array  $columns
     * @return array
     */
    protected function withoutSelectAliases(array $columns)
    {
        return array_map(function ($column) {
            return is_string($column) && ($aliasPosition = strpos(strtolower($column), ' as ')) !== false
                    ? substr($column, 0, $aliasPosition) : $column;
        }, $columns);
    }

    /**
     * Get a generator for the given query.
     *
     * 获取给定查询的生成器
     *
     * @return \Generator
     */
    public function cursor()
    {
        if (is_null($this->columns)) {
            $this->columns = ['*'];
        }
        //对数据库运行SELECT语句并返回生成器
        return $this->connection->cursor(
            //获取查询的sql表示形式     在扁平数组中获取当前查询值绑定
            $this->toSql(), $this->getBindings(), ! $this->useWritePdo
        );
    }

    /**
     * Chunk the results of the query.
     *
     * 将查询结果分块
     *
     * @param  int  $count
     * @param  callable  $callback
     * @return bool
     */
    public function chunk($count, callable $callback)
    {
        $this->enforceOrderBy();

        $page = 1;

        do {
            // We'll execute the query for the given page and get the results. If there are
            // no results we can just break and return from here. When there are results
            // we will call the callback with the current chunk of these results here.
            //
            // 我们将执行给定页面的查询并得到结果
            // 如果没有结果，我们就可以在这里休息和返回
            // 当有结果时，我们将用这些结果的当前块调用回调函数
            //
            //              设置给定页的限制和偏移量     将查询执行为“SELECT”语句
            $results = $this->forPage($page, $count)->get();

            $countResults = $results->count();//计数集合中的项目数

            if ($countResults == 0) {
                break;
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            //
            // 在每个块结果集上，我们将它们传递给回调函数，然后让开发人员处理回调过程中的所有内容，这使我们能够保持低内存，以便通过大的结果集进行工作
            //
            if ($callback($results) === false) {
                return false;
            }

            $page++;
        } while ($countResults == $count);

        return true;
    }

    /**
     * Chunk the results of a query by comparing numeric IDs.
     *
     * 通过比较数值IDs来分块查询的结果
     *
     * @param  int  $count
     * @param  callable  $callback
     * @param  string  $column
     * @param  string  $alias
     * @return bool
     */
    public function chunkById($count, callable $callback, $column = 'id', $alias = null)
    {
        $alias = $alias ?: $column;

        $lastId = 0;

        do {
            $clone = clone $this;

            // We'll execute the query for the given page and get the results. If there are
            // no results we can just break and return from here. When there are results
            // we will call the callback with the current chunk of these results here.
            //
            // 我们将执行给定页面的查询并得到结果
            // 如果没有结果，我们就可以在这里休息和返回
            // 当有结果时，我们将用这些结果的当前块调用回调函数
            //
            //将查询限制到给定ID后的结果的下一个“页面”                          将查询执行为“SELECT”语句
            $results = $clone->forPageAfterId($count, $lastId, $column)->get();

            $countResults = $results->count();//计数集合中的项目数

            if ($countResults == 0) {
                break;
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            //
            // 在每个块结果集上，我们将将它们传递给回调函数，然后让开发人员处理回调过程中的所有内容，这使我们能够保持低内存，以便通过大的结果集进行工作
            //
            if ($callback($results) === false) {
                return false;
            }
            //从集合中获取最后一个项
            $lastId = $results->last()->{$alias};
        } while ($countResults == $count);

        return true;
    }

    /**
     * Throw an exception if the query doesn't have an orderBy clause.
     *
     * 如果查询没有orderBy子句抛出异常
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    protected function enforceOrderBy()
    {
        if (empty($this->orders) && empty($this->unionOrders)) {
            throw new RuntimeException('You must specify an orderBy clause when using this function.');
        }
    }

    /**
     * Execute a callback over each item while chunking.
     *
     * 执行一个回调在每个项目上分块
     *
     * @param  callable  $callback
     * @param  int  $count
     * @return bool
     */
    public function each(callable $callback, $count = 1000)
    {
        //将查询结果分块
        return $this->chunk($count, function ($results) use ($callback) {
            foreach ($results as $key => $value) {
                if ($callback($value, $key) === false) {
                    return false;
                }
            }
        });
    }

    /**
     * Get an array with the values of a given column.
     *
     * 用给定列的值获取数组
     *
     * @param  string  $column
     * @param  string|null  $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null)
    {
        //将查询执行为“SELECT”语句
        $results = $this->get(is_null($key) ? [$column] : [$column, $key]);

        // If the columns are qualified with a table or have an alias, we cannot use
        // those directly in the "pluck" operations since the results from the DB
        // are only keyed by the column itself. We'll strip the table out here.
        //
        // 如果列有一个表或有别名，我们不能直接使用那些在“pluck”操作，因为从DB的结果只由列本身
        // 我们会把表放在这儿
        //
        //             获取给定键的值
        return $results->pluck(
            $this->stripTableForPluck($column),//从列标识符中删除表名或别名
            $this->stripTableForPluck($key)//从列标识符中删除表名或别名
        );
    }

    /**
     * Strip off the table name or alias from a column identifier.
     *
     * 从列标识符中删除表名或别名
     *
     * @param  string  $column
     * @return string|null
     */
    protected function stripTableForPluck($column)
    {
        return is_null($column) ? $column : last(preg_split('~\.| ~', $column));
    }

    /**
     * Concatenate values of a given column as a string.
     *
     * 一个给定的列值作为一个字符串连接
     *
     * @param  string  $column
     * @param  string  $glue
     * @return string
     */
    public function implode($column, $glue = '')
    {
        //        用给定列的值获取数组    一个给定的键连接的值作为一个字符串
        return $this->pluck($column)->implode($glue);
    }

    /**
     * Determine if any rows exist for the current query.
     *
     * 确定当前查询是否存在任何行
     *
     * @return bool
     */
    public function exists()
    {
        $results = $this->connection->select(//对数据库运行SELECT语句
            //将exists语句编译为sql                 在扁平数组中获取当前查询值绑定
            $this->grammar->compileExists($this), $this->getBindings(), ! $this->useWritePdo
        );

        // If the results has rows, we will get the row and see if the exists column is a
        // boolean true. If there is no results for this query we will return false as
        // there are no rows for this query at all and we can return that info here.
        //
        // 如果结果有行，我们将得到行，看看是否存在列是一个布尔真
        // 如果此查询没有结果，我们将返回false，因为此查询没有行，我们可以在这里返回该信息
        //
        if (isset($results[0])) {
            $results = (array) $results[0];

            return (bool) $results['exists'];
        }

        return false;
    }

    /**
     * Retrieve the "count" result of the query.
     *
     * 检索查询的“count”结果
     *
     * @param  string  $columns
     * @return int
     */
    public function count($columns = '*')
    {
        //                 在数据库上执行聚合函数         如果给定值不是数组，请将其包在一个数组中
        return (int) $this->aggregate(__FUNCTION__, array_wrap($columns));
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * 检索给定列的最小值
     *
     * @param  string  $column
     * @return mixed
     */
    public function min($column)
    {
        //             在数据库上执行聚合函数
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * 检索给定列的最大值
     *
     * @param  string  $column
     * @return mixed
     */
    public function max($column)
    {
        //             在数据库上执行聚合函数
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * 检索给定列值的和
     *
     * @param  string  $column
     * @return mixed
     */
    public function sum($column)
    {
        //             在数据库上执行聚合函数
        $result = $this->aggregate(__FUNCTION__, [$column]);

        return $result ?: 0;
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * 检索给定列的值的平均值
     *
     * @param  string  $column
     * @return mixed
     */
    public function avg($column)
    {
        //             在数据库上执行聚合函数
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Alias for the "avg" method.
     *
     * avg方法的别名
     *
     * @param  string  $column
     * @return mixed
     */
    public function average($column)
    {
        //检索给定列的值的平均值
        return $this->avg($column);
    }

    /**
     * Execute an aggregate function on the database.
     *
     * 在数据库上执行聚合函数
     *
     * @param  string  $function
     * @param  array   $columns
     * @return mixed
     */
    public function aggregate($function, $columns = ['*'])
    {
        $results = $this->cloneWithout(['columns'])//在没有给定属性的情况下克隆查询
                        ->cloneWithoutBindings(['select'])//在没有给定绑定的情况下克隆查询
                        ->setAggregate($function, $columns)//设置聚合属性而不运行查询
                        ->get($columns);//将查询执行为“SELECT”语句

        if (! $results->isEmpty()) {//确定集合是否为空
            return array_change_key_case((array) $results[0])['aggregate'];
        }
    }

    /**
     * Execute a numeric aggregate function on the database.
     *
     * 在数据库上执行数字聚合函数
     *
     * @param  string  $function
     * @param  array   $columns
     * @return float|int
     */
    public function numericAggregate($function, $columns = ['*'])
    {
        //在数据库上执行聚合函数
        $result = $this->aggregate($function, $columns);

        // If there is no result, we can obviously just return 0 here. Next, we will check
        // if the result is an integer or float. If it is already one of these two data
        // types we can just return the result as-is, otherwise we will convert this.
        //
        // 如果没有结果，我们显然可以在这里返回0
        // 接下来，我们将检查结果是整数还是浮点
        // 如果它已经是这两个数据类型中的一个，我们就可以返回结果，否则我们将转换
        //
        if (! $result) {
            return 0;
        }

        if (is_int($result) || is_float($result)) {
            return $result;
        }

        // If the result doesn't contain a decimal place, we will assume it is an int then
        // cast it to one. When it does we will cast it to a float since it needs to be
        // cast to the expected data type for the developers out of pure convenience.
        //
        // 如果结果不包含小数位，我们将假设它是int，然后将其转换为一个
        // 当我们这样做时，我们将把它转换为一个浮动，因为它需要被转换为预期的数据类型
        //
        return strpos((string) $result, '.') === false
                ? (int) $result : (float) $result;
    }

    /**
     * Set the aggregate property without running the query.
     *
     * 设置聚合属性而不运行查询
     *
     * @param  string  $function
     * @param  array  $columns
     * @return $this
     */
    protected function setAggregate($function, $columns)
    {
        $this->aggregate = compact('function', 'columns');

        return $this;
    }

    /**
     * Insert a new record into the database.
     *
     * 将新记录插入数据库
     *
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values)
    {
        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient when building these
        // inserts statements by verifying these elements are actually an array.
        //
        // 由于每一个插入都得到了处理，就像一个批插入，我们将确保绑定的结构是方便的方式构建这些插入语句时，通过验证这些元素实际上是一个数组
        //
        if (empty($values)) {
            return true;
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        // Here, we will sort the insert keys for every record so that each insert is
        // in the same order for the record. We need to make sure this is the case
        // so there are not any errors or problems when inserting these records.
        //
        // 在这里，我们将对每个记录进行插入键排序，以便每个插入的记录都是相同的顺序
        // 我们需要确保这是这样的情况下，没有任何错误或问题时，插入这些记录
        //
        else {
            foreach ($values as $key => $value) {
                ksort($value);

                $values[$key] = $value;
            }
        }

        // Finally, we will run this query against the database connection and return
        // the results. We will need to also flatten these bindings before running
        // the query so they are all in one huge, flattened array for execution.
        //
        // 最后，我们将对数据库连接运行此查询并返回结果
        // 在运行查询之前，我们还需要对这些绑定进行扁平化，以便它们都在一个巨大的扁平数组中执行
        //
        return $this->connection->insert(//对数据库运行insert语句
            $this->grammar->compileInsert($this, $values),//将插入语句编译为sql
            $this->cleanBindings(Arr::flatten($values, 1))//从绑定列表中移除所有表达式(将多维数组变平为单级)
        );
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * 插入新记录并获取主键的值
     *
     * @param  array   $values
     * @param  string  $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $sql = $this->grammar->compileInsertGetId($this, $values, $sequence);//编译插入并将ID语句导入sql

        $values = $this->cleanBindings($values);//从绑定列表中移除所有表达式

        return $this->processor->processInsertGetId($this, $sql, $values, $sequence);//处理“插入获取ID”查询
    }

    /**
     * Update a record in the database.
     *
     * 更新数据库中的记录
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        $sql = $this->grammar->compileUpdate($this, $values);//将更新语句编译为sql
        //对数据库运行更新语句                            从绑定列表中移除所有表达式
        return $this->connection->update($sql, $this->cleanBindings(
            //为更新语句准备绑定
            $this->grammar->prepareBindingsForUpdate($this->bindings, $values)
        ));
    }

    /**
     * Insert or update a record matching the attributes, and fill it with values.
     *
     * 插入或更新记录匹配的属性值和填充它
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return bool
     */
    public function updateOrInsert(array $attributes, array $values = [])
    {
        //    将基本WHERE子句添加到查询中   确定当前查询是否存在任何行
        if (! $this->where($attributes)->exists()) {
            //将新记录插入数据库
            return $this->insert(array_merge($attributes, $values));
        }
        //         别名设置查询的“limit”值 更新数据库中的记录
        return (bool) $this->take(1)->update($values);
    }

    /**
     * Increment a column's value by a given amount.
     *
     * 按给定值递增列的值
     *
     * @param  string  $column
     * @param  int     $amount
     * @param  array   $extra
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Non-numeric value passed to increment method.');
        }
        //在关键字标识符中包装值
        $wrapped = $this->grammar->wrap($column);
        //                                         创建原始数据库表达式
        $columns = array_merge([$column => $this->raw("$wrapped + $amount")], $extra);
        //更新数据库中的记录
        return $this->update($columns);
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * 按给定数量递减列的值
     *
     * @param  string  $column
     * @param  int     $amount
     * @param  array   $extra
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Non-numeric value passed to decrement method.');
        }
        //在关键字标识符中包装值
        $wrapped = $this->grammar->wrap($column);
        //                                         创建原始数据库表达式
        $columns = array_merge([$column => $this->raw("$wrapped - $amount")], $extra);
        //更新数据库中的记录
        return $this->update($columns);
    }

    /**
     * Delete a record from the database.
     *
     * 从数据库中删除记录
     *
     * @param  mixed  $id
     * @return int
     */
    public function delete($id = null)
    {
        // If an ID is passed to the method, we will set the where clause to check the
        // ID to let developers to simply and quickly remove a single row from this
        // database without manually specifying the "where" clauses on the query.
        //
        // 如果将id传递给该方法，我们将设置WHERE子句来检查id，以便开发人员能够简单且快速地从该数据库移除单个行，而无需手动指定查询的“WHERE”子句
        //
        if (! is_null($id)) {
            //将基本WHERE子句添加到查询中
            $this->where($this->from.'.id', '=', $id);
        }
        //对数据库运行删除语句
        return $this->connection->delete(
            //将删除语句编译为sql                       在扁平数组中获取当前查询值绑定
            $this->grammar->compileDelete($this), $this->getBindings()
        );
    }

    /**
     * Run a truncate statement on the table.
     *
     * 在表上运行截断语句
     *
     * @return void
     */
    public function truncate()
    {
        //                 将截断表语句编译为sql
        foreach ($this->grammar->compileTruncate($this) as $sql => $bindings) {
            $this->connection->statement($sql, $bindings);//执行SQL语句并返回布尔结果
        }
    }

    /**
     * Get a new instance of the query builder.
     *
     * 获取查询生成器的新实例
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newQuery()
    {
        return new static($this->connection, $this->grammar, $this->processor);
    }

    /**
     * Create a raw database expression.
     *
     * 创建原始数据库表达式
     *
     * @param  mixed  $value
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value)
    {
        return $this->connection->raw($value);//获取新的原始查询表达式
    }

    /**
     * Get the current query value bindings in a flattened array.
     *
     * 在扁平数组中获取当前查询值绑定
     *
     * @return array
     */
    public function getBindings()
    {
        return Arr::flatten($this->bindings);//将多维数组变平为单级
    }

    /**
     * Get the raw array of bindings.
     *
     * 获取绑定的原始数组
     *
     * @return array
     */
    public function getRawBindings()
    {
        return $this->bindings;
    }

    /**
     * Set the bindings on the query builder.
     *
     * 在查询生成器上设置绑定
     *
     * @param  array   $bindings
     * @param  string  $type
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setBindings(array $bindings, $type = 'where')
    {
        if (! array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}.");
        }

        $this->bindings[$type] = $bindings;

        return $this;
    }

    /**
     * Add a binding to the query.
     *
     * 向查询添加绑定
     *
     * @param  mixed   $value
     * @param  string  $type
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function addBinding($value, $type = 'where')
    {
        if (! array_key_exists($type, $this->bindings)) {
            throw new InvalidArgumentException("Invalid binding type: {$type}.");
        }

        if (is_array($value)) {
            $this->bindings[$type] = array_values(array_merge($this->bindings[$type], $value));
        } else {
            $this->bindings[$type][] = $value;
        }

        return $this;
    }

    /**
     * Merge an array of bindings into our bindings.
     *
     * 将绑定数组合并到我们的绑定中
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return $this
     */
    public function mergeBindings(Builder $query)
    {
        $this->bindings = array_merge_recursive($this->bindings, $query->bindings);

        return $this;
    }

    /**
     * Remove all of the expressions from a list of bindings.
     *
     * 从绑定列表中移除所有表达式
     *
     * @param  array  $bindings
     * @return array
     */
    protected function cleanBindings(array $bindings)
    {
        return array_values(array_filter($bindings, function ($binding) {
            return ! $binding instanceof Expression;
        }));
    }

    /**
     * Get the database connection instance.
     *
     * 获取数据库链接实例
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the database query processor instance.
     *
     * 获取数据库查询处理器实例
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * Get the query grammar instance.
     *
     * 获取查询语法实例
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    public function getGrammar()
    {
        return $this->grammar;
    }

    /**
     * Use the write pdo for query.
     *
     * 用写的PDO的查询
     *
     * @return $this
     */
    public function useWritePdo()
    {
        $this->useWritePdo = true;

        return $this;
    }

    /**
     * Clone the query without the given properties.
     *
     * 在没有给定属性的情况下克隆查询
     *
     * @param  array  $except
     * @return static
     */
    public function cloneWithout(array $except)
    {
        //  用给定的值调用给定的闭包，然后返回值
        return tap(clone $this, function ($clone) use ($except) {
            foreach ($except as $property) {
                $clone->{$property} = null;
            }
        });
    }

    /**
     * Clone the query without the given bindings.
     *
     * 在没有给定绑定的情况下克隆查询
     *
     * @param  array  $except
     * @return static
     */
    public function cloneWithoutBindings(array $except)
    {
        //  用给定的值调用给定的闭包，然后返回值
        return tap(clone $this, function ($clone) use ($except) {
            foreach ($except as $type) {
                $clone->bindings[$type] = [];
            }
        });
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * 将动态方法调用处理到方法中
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        //检查宏是否已注册
        if (static::hasMacro($method)) {
            //动态调用类的调用
            return $this->macroCall($method, $parameters);
        }

        if (Str::startsWith($method, 'where')) {//确定给定的子字符串是否属于给定的字符串
            return $this->dynamicWhere($method, $parameters);//处理查询的动态“where”子句
        }

        $className = static::class;

        throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
    }
}
