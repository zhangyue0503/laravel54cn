<?php

namespace Illuminate\Database\Query;

use Closure;

class JoinClause extends Builder
{
    /**
     * The type of join being performed.
     *
     * 执行的连接类型
     *
     * @var string
     */
    public $type;

    /**
     * The table the join clause is joining to.
     *
     * 连接子句将连接到
     *
     * @var string
     */
    public $table;

    /**
     * The parent query builder instance.
     *
     * 父查询构建器实例
     *
     * @var \Illuminate\Database\Query\Builder
     */
    private $parentQuery;

    /**
     * Create a new join clause instance.
     *
     * 创建一个新的加入从句实例
     *
     * @param  \Illuminate\Database\Query\Builder $parentQuery
     * @param  string  $type
     * @param  string  $table
     * @return void
     */
    public function __construct(Builder $parentQuery, $type, $table)
    {
        $this->type = $type;
        $this->table = $table;
        $this->parentQuery = $parentQuery;
        //创建一个新的查询构造器实例
        parent::__construct(
            //获取数据库链接实例                   获取查询语法实例                  获取数据库查询处理器实例
            $parentQuery->getConnection(), $parentQuery->getGrammar(), $parentQuery->getProcessor()
        );
    }

    /**
     * Add an "on" clause to the join.
     *
     * 向连接添加一个“on”子句
     *
     * On clauses can be chained, e.g.
     *
     * 子句可以被链接起来
     *
     *  $join->on('contacts.user_id', '=', 'users.id')
     *       ->on('contacts.info_id', '=', 'info.id')
     *
     * will produce the following SQL:
     *
     * 将生成以下SQL:
     *
     * on `contacts`.`user_id` = `users`.`id`  and `contacts`.`info_id` = `info`.`id`
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function on($first, $operator = null, $second = null, $boolean = 'and')
    {
        if ($first instanceof Closure) {
            //向查询添加嵌套语句
            return $this->whereNested($first, $boolean);
        }
        //向查询中添加“where”子句比较两列
        return $this->whereColumn($first, $operator, $second, $boolean);
    }

    /**
     * Add an "or on" clause to the join.
     *
     * 向连接添加一个“or on”子句
     *
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function orOn($first, $operator = null, $second = null)
    {
        //向连接添加一个“on”子句
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Get a new instance of the join clause builder.
     *
     * 获取连接子句构建器的新实例
     *
     * @return \Illuminate\Database\Query\JoinClause
     */
    public function newQuery()
    {
        return new static($this->parentQuery, $this->type, $this->table);
    }
}
