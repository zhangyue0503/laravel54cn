<?php

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Support\Arr;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Grammar as BaseGrammar;
//语法
class Grammar extends BaseGrammar
{
    /**
     * The grammar specific operators.
     *
     * 语法特定的运算符
     *
     * @var array
     */
    protected $operators = [];

    /**
     * The components that make up a select clause.
     *
     * 组成一个select子句的组件
     *
     * @var array
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'unions',
        'lock',
    ];

    /**
     * Compile a select query into SQL.
	 *
	 * 将SELECT查询编译为sql
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        // If the query does not have any columns set, we'll set the columns to the
        // * character to just get all of the columns from the database. Then we
        // can build the query and concatenate all the pieces together as one.
        //
        // 如果查询没有任何列集合,我们将设置列*字符来从数据库中获取所有的列
        // 然后，我们可以构建查询，并将所有的片段连接在一起
        //
        $original = $query->columns;

        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.
        //
        // 为了编译这个查询，我们将对查询的每个组件进行旋转，并查看该组件是否存在
        // 如果是这样，我们只需要调用负责制造SQL的组件的编译器函数
        //
		//     连接数组片段并去除空格(编译select子句所需的组件)
        $sql = trim($this->concatenate(
            $this->compileComponents($query))
        );

        $query->columns = $original;

        return $sql;
    }

    /**
     * Compile the components necessary for a select clause.
	 *
	 * 编译select子句所需的组件
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    protected function compileComponents(Builder $query)
    {
        $sql = [];

        foreach ($this->selectComponents as $component) {
            // To compile the query, we'll spin through each component of the query and
            // see if that component exists. If it does we'll just call the compiler
            // function for the component which is responsible for making the SQL.
            //
            // 为了编译这个查询，我们将对查询的每个组件进行旋转，并查看该组件是否存在
            // 如果是这样，我们只需要调用负责制造SQL的组件的编译器函数
            //
            if (! is_null($query->$component)) {
                $method = 'compile'.ucfirst($component);

                $sql[$component] = $this->$method($query, $query->$component);
            }
        }

        return $sql;
    }

    /**
     * Compile an aggregated select clause.
     *
     * 编译一个聚合的select子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $aggregate
     * @return string
     */
    protected function compileAggregate(Builder $query, $aggregate)
    {
        //将列名称的数组转换为分隔字符串
        $column = $this->columnize($aggregate['columns']);

        // If the query has a "distinct" constraint and we're not asking for all columns
        // we need to prepend "distinct" onto the column name so that the query takes
        // it into account when it performs the aggregating operations on the data.
        //
        // 如果查询“截然不同”的约束,我们不要求所有列我们需要前置“截然不同”到列名称,查询执行时考虑了它的聚合操作数据
        //
        if ($query->distinct && $column !== '*') {
            $column = 'distinct '.$column;
        }

        return 'select '.$aggregate['function'].'('.$column.') as aggregate';
    }

    /**
     * Compile the "select *" portion of the query.
	 *
	 * 编译请求语句的"select *"部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $columns
     * @return string|null
     */
    protected function compileColumns(Builder $query, $columns)
    {
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
        //
        // 如果查询实际上是执行一个聚合选择,我们会让编译器处理的建筑选择条款,因为它需要更多的语法,是最好的处理函数保持整洁
        //
        if (! is_null($query->aggregate)) {
            return;
        }

        $select = $query->distinct ? 'select distinct ' : 'select ';
        //将列名称的数组转换为分隔字符串
        return $select.$this->columnize($columns);
    }

    /**
     * Compile the "from" portion of the query.
	 *
	 * 编译查询语句的“from”部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @return string
     */
    protected function compileFrom(Builder $query, $table)
    {
        //                  在关键字标识符中包装表
        return 'from '.$this->wrapTable($table);
    }

    /**
     * Compile the "join" portions of the query.
     *
     * 编译查询的“连接”部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $joins
     * @return string
     */
    protected function compileJoins(Builder $query, $joins)
    {
        //                    在每个项目上运行map
        return collect($joins)->map(function ($join) use ($query) {
            //            在关键字标识符中包装表
            $table = $this->wrapTable($join->table);
            //                                        编译查询的“where”部分
            return trim("{$join->type} join {$table} {$this->compileWheres($join)}");
        })->implode(' ');// 一个给定的键连接的值作为一个字符串
    }

    /**
     * Compile the "where" portions of the query.
     *
     * 编译查询的“where”部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileWheres(Builder $query)
    {
        // Each type of where clauses has its own compiler function which is responsible
        // for actually creating the where clauses SQL. This helps keep the code nice
        // and maintainable since each clause has a very small method that it uses.
        //
        // 每种where子句都有自己的编译器函数，它负责实际创建where子句SQL
        // 这有助于保持代码的良好和可维护性，因为每个子句都有一个非常小的方法
        //
        if (is_null($query->wheres)) {
            return '';
        }

        // If we actually have some where clauses, we will strip off the first boolean
        // operator, which is added by the query builders for convenience so we can
        // avoid checking for the first clauses in each of the compilers methods.
        //
        // 如果我们有一些where子句,我们会去掉第一个布尔操作符,这是由查询添加建筑商为了方便所以我们可以避免第一条款检查每个编译器的方法
        //
        //                   获取查询的所有where子句的数组
        if (count($sql = $this->compileWheresToArray($query)) > 0) {
            return $this->concatenateWhereClauses($query, $sql);//将where子句语句格式化为一个字符串
        }

        return '';
    }

    /**
     * Get an array of all the where clauses for the query.
     *
     * 获取查询的所有where子句的数组
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    protected function compileWheresToArray($query)
    {
        //                             在每个项目上运行map
        return collect($query->wheres)->map(function ($where) use ($query) {
            return $where['boolean'].' '.$this->{"where{$where['type']}"}($query, $where);
        })->all();//获取集合中的所有项目
    }

    /**
     * Format the where clause statements into one string.
     *
     * 将where子句语句格式化为一个字符串
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $sql
     * @return string
     */
    protected function concatenateWhereClauses($query, $sql)
    {
        $conjunction = $query instanceof JoinClause ? 'on' : 'where';
        //                          从语句中移除领先的布尔值
        return $conjunction.' '.$this->removeLeadingBoolean(implode(' ', $sql));
    }

    /**
     * Compile a raw where clause.
     *
     * 编译原始where子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereRaw(Builder $query, $where)
    {
        return $where['sql'];
    }

    /**
     * Compile a basic where clause.
     *
     * 编译基本where子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBasic(Builder $query, $where)
    {
        //        获取适当的查询参数占位符
        $value = $this->parameter($where['value']);
        //在关键字标识符中包装值
        return $this->wrap($where['column']).' '.$where['operator'].' '.$value;
    }

    /**
     * Compile a "where in" clause.
     *
     * 编译一个“where in”条款
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereIn(Builder $query, $where)
    {
        if (! empty($where['values'])) {
            //在关键字标识符中包装值                                  为数组创建查询参数占位符
            return $this->wrap($where['column']).' in ('.$this->parameterize($where['values']).')';
        }

        return '0 = 1';
    }

    /**
     * Compile a "where not in" clause.
     *
     * 编译一个“where not in”条款
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotIn(Builder $query, $where)
    {
        if (! empty($where['values'])) {
            //在关键字标识符中包装值                                  为数组创建查询参数占位符
            return $this->wrap($where['column']).' not in ('.$this->parameterize($where['values']).')';
        }

        return '1 = 1';
    }

    /**
     * Compile a where in sub-select clause.
     *
     * 在子选择子句中编译一个位置
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereInSub(Builder $query, $where)
    {
        //在关键字标识符中包装值                                  将SELECT查询编译为sql
        return $this->wrap($where['column']).' in ('.$this->compileSelect($where['query']).')';
    }

    /**
     * Compile a where not in sub-select clause.
     *
     * 在非子选择子句中编译
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotInSub(Builder $query, $where)
    {
        //在关键字标识符中包装值                                  将SELECT查询编译为sql
        return $this->wrap($where['column']).' not in ('.$this->compileSelect($where['query']).')';
    }

    /**
     * Compile a "where null" clause.
     *
     * 编译一个“where null”条款
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNull(Builder $query, $where)
    {
        //在关键字标识符中包装值
        return $this->wrap($where['column']).' is null';
    }

    /**
     * Compile a "where not null" clause.
     *
     * 编译一个“where not null”条款
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotNull(Builder $query, $where)
    {
        //在关键字标识符中包装值
        return $this->wrap($where['column']).' is not null';
    }

    /**
     * Compile a "between" where clause.
     *
     * 编译一个where子句“between”
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBetween(Builder $query, $where)
    {
        $between = $where['not'] ? 'not between' : 'between';
        //在关键字标识符中包装值
        return $this->wrap($where['column']).' '.$between.' ? and ?';
    }

    /**
     * Compile a "where date" clause.
     *
     * 编译一个“where date”子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereDate(Builder $query, $where)
    {
        //根据where子句编译一个日期
        return $this->dateBasedWhere('date', $query, $where);
    }

    /**
     * Compile a "where time" clause.
     *
     * 编译一个“where time”子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereTime(Builder $query, $where)
    {
        //根据where子句编译一个日期
        return $this->dateBasedWhere('time', $query, $where);
    }

    /**
     * Compile a "where day" clause.
     *
     * 编译一个“where day”子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereDay(Builder $query, $where)
    {
        //根据where子句编译一个日期
        return $this->dateBasedWhere('day', $query, $where);
    }

    /**
     * Compile a "where month" clause.
     *
     * 编译一个“where month”子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereMonth(Builder $query, $where)
    {
        //根据where子句编译一个日期
        return $this->dateBasedWhere('month', $query, $where);
    }

    /**
     * Compile a "where year" clause.
     *
     * 编译一个“where year”子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereYear(Builder $query, $where)
    {
        //根据where子句编译一个日期
        return $this->dateBasedWhere('year', $query, $where);
    }

    /**
     * Compile a date based where clause.
     *
     * 根据where子句编译一个日期
     *
     * @param  string  $type
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function dateBasedWhere($type, Builder $query, $where)
    {
        //获取适当的查询参数占位符
        $value = $this->parameter($where['value']);
        //在关键字标识符中包装值
        return $type.'('.$this->wrap($where['column']).') '.$where['operator'].' '.$value;
    }

    /**
     * Compile a where clause comparing two columns..
     *
     * 编译where子句比较两列
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereColumn(Builder $query, $where)
    {
        //在关键字标识符中包装值
        return $this->wrap($where['first']).' '.$where['operator'].' '.$this->wrap($where['second']);
    }

    /**
     * Compile a nested where clause.
     *
     * 编译一个嵌套where子句
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNested(Builder $query, $where)
    {
        // Here we will calculate what portion of the string we need to remove. If this
        // is a join clause query, we need to remove the "on" portion of the SQL and
        // if it is a normal query we need to take the leading "where" of queries.
        //
        // 在这里，我们将计算需要删除的字符串的哪一部分
        // 如果这是一个加入条款查询,我们需要删除“on”部分的SQL查询,如果是一个正常的领导”,“我们需要查询
        //
        $offset = $query instanceof JoinClause ? 3 : 6;
        //                  编译查询的“where”部分
        return '('.substr($this->compileWheres($where['query']), $offset).')';
    }

    /**
     * Compile a where condition with a sub-select.
     *
     * 使用子选择编译一个条件
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  array   $where
     * @return string
     */
    protected function whereSub(Builder $query, $where)
    {
        //将SELECT查询编译为sql
        $select = $this->compileSelect($where['query']);
        //在关键字标识符中包装值
        return $this->wrap($where['column']).' '.$where['operator']." ($select)";
    }

    /**
     * Compile a where exists clause.
     *
     * 编译存在条款
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereExists(Builder $query, $where)
    {
        //将SELECT查询编译为sql
        return 'exists ('.$this->compileSelect($where['query']).')';
    }

    /**
     * Compile a where exists clause.
     *
     * 编译存在条款
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotExists(Builder $query, $where)
    {
        //将SELECT查询编译为sql
        return 'not exists ('.$this->compileSelect($where['query']).')';
    }

    /**
     * Compile the "group by" portions of the query.
     *
     * 根据查询的部分编译“组”
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $groups
     * @return string
     */
    protected function compileGroups(Builder $query, $groups)
    {
        //将列名称的数组转换为分隔字符串
        return 'group by '.$this->columnize($groups);
    }

    /**
     * Compile the "having" portions of the query.
     *
     * 编译查询的“having”部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $havings
     * @return string
     */
    protected function compileHavings(Builder $query, $havings)
    {
        $sql = implode(' ', array_map([$this, 'compileHaving'], $havings));
        //从语句中移除领先的布尔值
        return 'having '.$this->removeLeadingBoolean($sql);
    }

    /**
     * Compile a single having clause.
     *
     * 编译一个单独的条款
     *
     * @param  array   $having
     * @return string
     */
    protected function compileHaving(array $having)
    {
        // If the having clause is "raw", we can just return the clause straight away
        // without doing any more processing on it. Otherwise, we will compile the
        // clause into SQL based on the components that make it up from builder.
        //
        // 如果“原始”子句是“原始”的，我们可以直接返回该子句，而不需要对其进行任何处理
        // 否则，我们将根据从构建器生成的组件将该子句编译成SQL
        //
        if ($having['type'] === 'Raw') {
            return $having['boolean'].' '.$having['sql'];
        }
        //           编译基本条款
        return $this->compileBasicHaving($having);
    }

    /**
     * Compile a basic having clause.
     *
     * 编译基本条款
     *
     * @param  array   $having
     * @return string
     */
    protected function compileBasicHaving($having)
    {
        //在关键字标识符中包装值
        $column = $this->wrap($having['column']);
        //获取适当的查询参数占位符
        $parameter = $this->parameter($having['value']);

        return $having['boolean'].' '.$column.' '.$having['operator'].' '.$parameter;
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * 编译查询部分的“order by”
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $orders
     * @return string
     */
    protected function compileOrders(Builder $query, $orders)
    {
        if (! empty($orders)) {
            //                                     将查询命令编译成一个数组
            return 'order by '.implode(', ', $this->compileOrdersToArray($query, $orders));
        }

        return '';
    }

    /**
     * Compile the query orders to an array.
     *
     * 将查询命令编译成一个数组
     *
     * @param  \Illuminate\Database\Query\Builder
     * @param  array  $orders
     * @return array
     */
    protected function compileOrdersToArray(Builder $query, $orders)
    {
        return array_map(function ($order) {
            return ! isset($order['sql'])
            //在关键字标识符中包装值
                        ? $this->wrap($order['column']).' '.$order['direction']
                        : $order['sql'];
        }, $orders);
    }

    /**
     * Compile the random statement into SQL.
     *
     * 将随机语句编译为sql
     *
     * @param  string  $seed
     * @return string
     */
    public function compileRandom($seed)
    {
        return 'RANDOM()';
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * 编译查询的“limit”部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $limit
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        return 'limit '.(int) $limit;
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * 编译查询的“offset”部分
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $offset
     * @return string
     */
    protected function compileOffset(Builder $query, $offset)
    {
        return 'offset '.(int) $offset;
    }

    /**
     * Compile the "union" queries attached to the main query.
     *
     * 编译与主查询相关的“union”查询
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileUnions(Builder $query)
    {
        $sql = '';

        foreach ($query->unions as $union) {
            //编译一个联合声明
            $sql .= $this->compileUnion($union);
        }

        if (! empty($query->unionOrders)) {
            //编译查询部分的“order by”
            $sql .= ' '.$this->compileOrders($query, $query->unionOrders);
        }

        if (isset($query->unionLimit)) {
            // 编译查询的“limit”部分
            $sql .= ' '.$this->compileLimit($query, $query->unionLimit);
        }

        if (isset($query->unionOffset)) {
            //编译查询的“offset”部分
            $sql .= ' '.$this->compileOffset($query, $query->unionOffset);
        }

        return ltrim($sql);
    }

    /**
     * Compile a single union statement.
     *
     * 编译一个联合声明
     *
     * @param  array  $union
     * @return string
     */
    protected function compileUnion(array $union)
    {
        $conjuction = $union['all'] ? ' union all ' : ' union ';

        return $conjuction.$union['query']->toSql();
    }

    /**
     * Compile an exists statement into SQL.
     *
     * 将exists语句编译为sql
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileExists(Builder $query)
    {
        //将SELECT查询编译为sql
        $select = $this->compileSelect($query);
        //                                    在关键字标识符中包装值
        return "select exists({$select}) as {$this->wrap('exists')}";
    }

    /**
     * Compile an insert statement into SQL.
     *
     * 将插入语句编译为sql
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileInsert(Builder $query, array $values)
    {
        // Essentially we will force every insert to be treated as a batch insert which
        // simply makes creating the SQL easier for us since we can utilize the same
        // basic routine regardless of an amount of records given to us to insert.
        //
        // 本质上我们将迫使每个插入被视为批量插入,只是让我们创建SQL容易因为我们可以使用相同的基本常规不管给我们插入的记录
        //
        //在关键字标识符中包装表
        $table = $this->wrapTable($query->from);

        if (! is_array(reset($values))) {
            $values = [$values];
        }
        //将列名称的数组转换为分隔字符串
        $columns = $this->columnize(array_keys(reset($values)));

        // We need to build a list of parameter place-holders of values that are bound
        // to the query. Each insert should have the exact same amount of parameter
        // bindings so we will loop through the record and parameterize them all.
        //
        // 我们需要构建一个与该查询绑定的值的参数占位符列表
        // 每个插入应该有相同数量的参数绑定,因此我们将遍历记录并将其参数化
        //
        //                                 在每个项目上运行map
        $parameters = collect($values)->map(function ($record) {
            return '('.$this->parameterize($record).')';//为数组创建查询参数占位符
        })->implode(', ');//一个给定的键连接的值作为一个字符串

        return "insert into $table ($columns) values $parameters";
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
     * 编译插入并将ID语句导入sql
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array   $values
     * @param  string  $sequence
     * @return string
     */
    public function compileInsertGetId(Builder $query, $values, $sequence)
    {
        //将插入语句编译为sql
        return $this->compileInsert($query, $values);
    }

    /**
     * Compile an update statement into SQL.
     *
     * 将更新语句编译为sql
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileUpdate(Builder $query, $values)
    {
        //在关键字标识符中包装表
        $table = $this->wrapTable($query->from);

        // Each one of the columns in the update statements needs to be wrapped in the
        // keyword identifiers, also a place-holder needs to be created for each of
        // the values in the list of bindings so we can make the sets statements.
        //
        // update语句中的列的每一个需要用关键字标识符,还需要创建一个占位用为每个绑定我们的列表中的值可以设置语句
        //
        //                          在每个项目上运行map
        $columns = collect($values)->map(function ($value, $key) {
            //在关键字标识符中包装值                获取适当的查询参数占位符
            return $this->wrap($key).' = '.$this->parameter($value);
        })->implode(', ');//一个给定的键连接的值作为一个字符串

        // If the query has any "join" clauses, we will setup the joins on the builder
        // and compile them so we can attach them to this update, as update queries
        // can get join statements to attach to other tables when they're needed.
        //
        // 如果查询有任何“加入”条款,我们将设置连接构建器和编译它们,所以我们可以将它们附加到这个更新,更新查询可以加入语句连接到其他表时必要的
        //
        $joins = '';

        if (isset($query->joins)) {
            //编译查询的“连接”部分
            $joins = ' '.$this->compileJoins($query, $query->joins);
        }

        // Of course, update queries may also be constrained by where clauses so we'll
        // need to compile the where clauses and attach it to the query so only the
        // intended records are updated by the SQL statements we generate to run.
        //
        // 当然,更新查询也可能受到where子句,所以我们需要编译where子句并将其附加到查询只打算记录更新我们生成的SQL语句
        //
        //            编译查询的“where”部分
        $wheres = $this->compileWheres($query);

        return trim("update {$table}{$joins} set $columns $wheres");
    }

    /**
     * Prepare the bindings for an update statement.
     *
     * 为更新语句准备绑定
     *
     * @param  array  $bindings
     * @param  array  $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values)
    {
        //获取指定数组，除了指定的数组项
        $bindingsWithoutJoin = Arr::except($bindings, 'join');

        return array_values(
            //                                       将多维数组变平为单级
            array_merge($bindings['join'], $values, Arr::flatten($bindingsWithoutJoin))
        );
    }

    /**
     * Compile a delete statement into SQL.
     *
     * 将删除语句编译为sql
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileDelete(Builder $query)
    {
        //编译查询的“where”部分
        $wheres = is_array($query->wheres) ? $this->compileWheres($query) : '';
        //                      在关键字标识符中包装表
        return trim("delete from {$this->wrapTable($query->from)} $wheres");
    }

    /**
     * Compile a truncate table statement into SQL.
     *
     * 将截断表语句编译为sql
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    public function compileTruncate(Builder $query)
    {
        //                  在关键字标识符中包装表
        return ['truncate '.$this->wrapTable($query->from) => []];
    }

    /**
     * Compile the lock into SQL.
     *
     * 将锁编译成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  bool|string  $value
     * @return string
     */
    protected function compileLock(Builder $query, $value)
    {
        return is_string($value) ? $value : '';
    }

    /**
     * Determine if the grammar supports savepoints.
     *
     * 确定语法支持保存点
     *
     * @return bool
     */
    public function supportsSavepoints()
    {
        return true;
    }

    /**
     * Compile the SQL statement to define a savepoint.
     *
     * 编译SQL语句来定义一个保存点
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepoint($name)
    {
        return 'SAVEPOINT '.$name;
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
     *
     * 编译SQL语句以执行保存点回滚
     *
     * @param  string  $name
     * @return string
     */
    public function compileSavepointRollBack($name)
    {
        return 'ROLLBACK TO SAVEPOINT '.$name;
    }

    /**
     * Concatenate an array of segments, removing empties.
	 *
	 * 连接数组片段并去除空格
     *
     * @param  array   $segments
     * @return string
     */
    protected function concatenate($segments)
    {
        return implode(' ', array_filter($segments, function ($value) {
            return (string) $value !== '';
        }));
    }

    /**
     * Remove the leading boolean from a statement.
     *
     * 从语句中移除领先的布尔值
     *
     * @param  string  $value
     * @return string
     */
    protected function removeLeadingBoolean($value)
    {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    /**
     * Get the grammar specific operators.
     *
     * 获取语法特定的操作符
     *
     * @return array
     */
    public function getOperators()
    {
        return $this->operators;
    }
}
