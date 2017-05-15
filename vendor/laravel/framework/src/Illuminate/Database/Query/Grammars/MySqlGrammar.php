<?php

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Support\Str;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JsonExpression;

class MySqlGrammar extends Grammar
{
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
        'lock',
    ];

    /**
     * Compile a select query into SQL.
     *
     * 将select查询编译为SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        //将SELECT查询编译为sql
        $sql = parent::compileSelect($query);

        if ($query->unions) {
            //                   编译与主查询相关的“union”查询
            $sql = '('.$sql.') '.$this->compileUnions($query);
        }

        return $sql;
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

        return $conjuction.'('.$union['query']->toSql().')';
    }

    /**
     * Compile the random statement into SQL.
     *
     * 将随机语句编译为SQL
     *
     * @param  string  $seed
     * @return string
     */
    public function compileRandom($seed)
    {
        return 'RAND('.$seed.')';
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
        if (! is_string($value)) {
            return $value ? 'for update' : 'lock in share mode';
        }

        return $value;
    }

    /**
     * Compile an update statement into SQL.
     *
     * 将更新语句编译成SQL
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
        //
        //               为更新语句编译所有列
        $columns = $this->compileUpdateColumns($values);

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
        //编译查询的“where”部分
        $where = $this->compileWheres($query);

        $sql = rtrim("update {$table}{$joins} set $columns $where");

        // If the query has an order by clause we will compile it since MySQL supports
        // order bys on update statements. We'll compile them using the typical way
        // of compiling order bys. Then they will be appended to the SQL queries.
        //
        // 如果查询有order by子句，我们将编译它，因为MySQL支持update语句的order by。我们将使用典型的编译顺序来编译它们。然后，它们将被追加到SQL查询中
        //
        if (! empty($query->orders)) {
            //               编译查询部分的“order by”
            $sql .= ' '.$this->compileOrders($query, $query->orders);
        }

        // Updates on MySQL also supports "limits", which allow you to easily update a
        // single record very easily. This is not supported by all database engines
        // so we have customized this update compiler here in order to add it in.
        //
        // MySQL的更新也支持“限制”，这允许您很容易地更新单个记录
        // 所有数据库引擎都不支持这一点，所以我们在这里定制了这个更新编译器，以便将它添加进来
        //
        if (isset($query->limit)) {
            //编译查询的“limit”部分
            $sql .= ' '.$this->compileLimit($query, $query->limit);
        }

        return rtrim($sql);
    }

    /**
     * Compile all of the columns for an update statement.
     *
     * 为更新语句编译所有列
     *
     * @param  array  $values
     * @return string
     */
    protected function compileUpdateColumns($values)
    {
        //                       在每个项目上运行map
        return collect($values)->map(function ($value, $key) {
            if ($this->isJsonSelector($key)) {//确定给定的字符串是否是一个JSON选择器
                //准备一个JSON使用JSON_SET函数列被更新
                return $this->compileJsonUpdateColumn($key, new JsonExpression($value));
            } else {
                //在关键字标识符中包装值           获取适当的查询参数占位符
                return $this->wrap($key).' = '.$this->parameter($value);
            }
        })->implode(', ');//一个给定的键连接的值作为一个字符串
    }

    /**
     * Prepares a JSON column being updated using the JSON_SET function.
     *
     * 准备一个JSON使用JSON_SET函数列被更新
     *
     * @param  string  $key
     * @param  \Illuminate\Database\Query\JsonExpression  $value
     * @return string
     */
    protected function compileJsonUpdateColumn($key, JsonExpression $value)
    {
        $path = explode('->', $key);
        //在关键字标识符中包装一个字符串
        $field = $this->wrapValue(array_shift($path));

        $accessor = '"$.'.implode('.', $path).'"';
        //                                                   得到表达式的值
        return "{$field} = json_set({$field}, {$accessor}, {$value->getValue()})";
    }

    /**
     * Prepare the bindings for an update statement.
     *
     * 为更新语句准备绑定
     *
     * Booleans, integers, and doubles are inserted into JSON updates as raw values.
     *
     * 布尔值、整数和双浮点插入JSON更新作为原始值
     *
     * @param  array  $bindings
     * @param  array  $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values)
    {
        //                    创建不通过给定的真值测试的所有元素的集合
        $values = collect($values)->reject(function ($value, $column) {
            //确定给定的字符串是否是一个JSON选择器
            return $this->isJsonSelector($column) &&
                in_array(gettype($value), ['boolean', 'integer', 'double']);
        })->all();// 获取集合中的所有项目
        //为更新语句准备绑定
        return parent::prepareBindingsForUpdate($bindings, $values);
    }

    /**
     * Compile a delete statement into SQL.
     *
     * 将delete语句编译成SQL
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileDelete(Builder $query)
    {
        //在关键字标识符中包装表
        $table = $this->wrapTable($query->from);
        //编译查询的“where”部分
        $where = is_array($query->wheres) ? $this->compileWheres($query) : '';

        return isset($query->joins)
                    ? $this->compileDeleteWithJoins($query, $table, $where)//编译使用连接的delete查询
                    : $this->compileDeleteWithoutJoins($query, $table, $where);//编译不使用连接的delete查询
    }

    /**
     * Compile a delete query that does not use joins.
     *
     * 编译不使用连接的delete查询
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @param  array  $where
     * @return string
     */
    protected function compileDeleteWithoutJoins($query, $table, $where)
    {
        $sql = trim("delete from {$table} {$where}");

        // When using MySQL, delete statements may contain order by statements and limits
        // so we will compile both of those here. Once we have finished compiling this
        // we will return the completed SQL statement so it will be executed for us.
        //
        // 在使用MySQL时，delete语句可能包含语句和限制的顺序，因此我们将在这里编译这两个语句
        // 完成编译后，我们将返回已完成的SQL语句，以便为我们执行它
        //
        if (! empty($query->orders)) {
            //编译查询部分的“order by”
            $sql .= ' '.$this->compileOrders($query, $query->orders);
        }

        if (isset($query->limit)) {
            //编译查询的“limit”部分
            $sql .= ' '.$this->compileLimit($query, $query->limit);
        }

        return $sql;
    }

    /**
     * Compile a delete query that uses joins.
     *
     * 编译使用连接的delete查询
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $table
     * @param  array  $where
     * @return string
     */
    protected function compileDeleteWithJoins($query, $table, $where)
    {
        //编译查询的“连接”部分
        $joins = ' '.$this->compileJoins($query, $query->joins);

        $alias = strpos(strtolower($table), ' as ') !== false
                ? explode(' as ', $table)[1] : $table;

        return trim("delete {$alias} from {$table}{$joins} {$where}");
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * 在关键字标识符中包装一个字符串
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        // If the given value is a JSON selector we will wrap it differently than a
        // traditional value. We will need to split this path and wrap each part
        // wrapped, etc. Otherwise, we will simply wrap the value as a string.
        //
        // 如果给定的值是一个JSON选择器，我们将用不同于传统值的方式包装它
        // 我们将需要分割这条路径，并将每个部分封装起来，否则，我们将简单地将该值包装为一个字符串
        //
        //确定给定的字符串是否是一个JSON选择器
        if ($this->isJsonSelector($value)) {
            //包装给定的JSON选择器
            return $this->wrapJsonSelector($value);
        }

        return '`'.str_replace('`', '``', $value).'`';
    }

    /**
     * Wrap the given JSON selector.
     *
     * 包装给定的JSON选择器
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonSelector($value)
    {
        $path = explode('->', $value);
        //在关键字标识符中包装一个字符串
        $field = $this->wrapValue(array_shift($path));
        //                                                 在每个项目上运行map
        return sprintf('%s->\'$.%s\'', $field, collect($path)->map(function ($part) {
            return '"'.$part.'"';
        })->implode('.'));//一个给定的键连接的值作为一个字符串
    }

    /**
     * Determine if the given string is a JSON selector.
     *
     * 确定给定的字符串是否是一个JSON选择器
     *
     * @param  string  $value
     * @return bool
     */
    protected function isJsonSelector($value)
    {
        //确定一个给定的字符串包含另一个字符串
        return Str::contains($value, '->');
    }
}
