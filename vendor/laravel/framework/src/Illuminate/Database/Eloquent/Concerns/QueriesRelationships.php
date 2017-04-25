<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
//查询关系
trait QueriesRelationships
{
    /**
     * Add a relationship count / exists condition to the query.
     *
     * 向查询添加关系 计数/存在 条件
     *
     * @param  string  $relation
     * @param  string  $operator
     * @param  int     $count
     * @param  string  $boolean
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null)
    {
        if (strpos($relation, '.') !== false) {
            return $this->hasNested($relation, $operator, $count, $boolean, $callback);//向查询添加嵌套关系 计数/存在 条件
        }

        $relation = $this->getRelationWithoutConstraints($relation);//获取“has relation”基础查询实例

        // If we only need to check for the existence of the relation, then we can optimize
        // the subquery to only run a "where exists" clause instead of this full "count"
        // clause. This will make these queries run much faster compared with a count.
        //
        // 如果我们只需要检查的关系存在，那么我们可以优化子查询只运行一个“存在”的条款而不是全“计数”条款
        // 这将使这些查询与计数相比运行得更快
        //
        $method = $this->canUseExistsForExistenceCheck($operator, $count)//检查是否可以运行“存在”查询以优化性能
                        ? 'getRelationExistenceQuery' //为内部关系存在查询添加约束
                        : 'getRelationExistenceCountQuery';//为关系计数查询添加约束

        $hasQuery = $relation->{$method}(
            //获取相关模型的关系->获取模型表的新查询生成器
            $relation->getRelated()->newQuery(), $this
        );

        // Next we will call any given callback as an "anonymous" scope so they can get the
        // proper logical grouping of the where clauses if needed by this Eloquent query
        // builder. Then, we will be ready to finalize and return this query instance.
        //
        // 接下来，我们将调用任何给定的回调作为一个“匿名”的范围，这样他们就可以得到适当的逻辑分组的WHERE子句，如果需要这个Eloquent的查询生成器
        // 然后，我们将准备结束并返回这个查询实例
        //
        if ($callback) {
            //将给定的范围应用于当前生成器实例
            $hasQuery->callScope($callback);
        }

        return $this->addHasWhere(//将“has”条件where子句添加到查询子句中
            $hasQuery, $relation, $operator, $count, $boolean
        );
    }

    /**
     * Add nested relationship count / exists conditions to the query.
     *
     * 向查询添加嵌套关系 计数/存在 条件
     *
     * Sets up recursive call to whereHas until we finish the nested relation.
     *
     * 建立递归调用那直到我们完成嵌套关系
     *
     * @param  string  $relations
     * @param  string  $operator
     * @param  int     $count
     * @param  string  $boolean
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    protected function hasNested($relations, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
    {
        $relations = explode('.', $relations);

        $closure = function ($q) use (&$closure, &$relations, $operator, $count, $boolean, $callback) {
            // In order to nest "has", we need to add count relation constraints on the
            // callback Closure. We'll do this by simply passing the Closure its own
            // reference to itself so it calls itself recursively on each segment.
            //
            // 为了嵌套“has”，我们需要在回调闭包中添加计数关系约束
            // 我们将通过简单地传递闭包自身引用来实现这一点，因此它在每个段上递归调用自己
            //
            count($relations) > 1
                ? $q->whereHas(array_shift($relations), $closure)//在WHERE子句中添加关系 计数/存在 条件
                : $q->has(array_shift($relations), $operator, $count, 'and', $callback); //向查询添加关系 计数/存在 条件
        };

        return $this->has(array_shift($relations), '>=', 1, $boolean, $closure); //向查询添加关系 计数/存在 条件
    }

    /**
     * Add a relationship count / exists condition to the query with an "or".
     *
     * 使用“或”向查询添加关系 计数/存在 条件
     *
     * @param  string  $relation
     * @param  string  $operator
     * @param  int     $count
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function orHas($relation, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'or');//向查询添加关系 计数/存在 条件
    }

    /**
     * Add a relationship count / exists condition to the query.
     *
     * 向查询添加关系 计数/存在 条件
     *
     * @param  string  $relation
     * @param  string  $boolean
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function doesntHave($relation, $boolean = 'and', Closure $callback = null)
    {
        return $this->has($relation, '<', 1, $boolean, $callback);//向查询添加关系 计数/存在 条件
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * 在WHERE子句中添加关系 计数/存在 条件
     *
     * @param  string  $relation
     * @param  \Closure|null  $callback
     * @param  string  $operator
     * @param  int     $count
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function whereHas($relation, Closure $callback = null, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'and', $callback);//向查询添加关系 计数/存在 条件
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * 在WHERE子句和“或”子句中添加关系 计数/存在 条件
     *
     * @param  string    $relation
     * @param  \Closure  $callback
     * @param  string    $operator
     * @param  int       $count
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function orWhereHas($relation, Closure $callback, $operator = '>=', $count = 1)
    {
        return $this->has($relation, $operator, $count, 'or', $callback);//向查询添加关系 计数/存在 条件
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * 在WHERE子句中添加关系 计数/存在 条件
     *
     * @param  string  $relation
     * @param  \Closure|null  $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function whereDoesntHave($relation, Closure $callback = null)
    {
        return $this->doesntHave($relation, 'and', $callback);//向查询添加关系 计数/存在 条件
    }

    /**
     * Add subselect queries to count the relations.
     *
     * 添加subselect查询数的关系
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function withCount($relations)
    {
        if (is_null($this->query->columns)) {
            $this->query->select([$this->query->from.'.*']);//对数据库运行SELECT语句
        }

        $relations = is_array($relations) ? $relations : func_get_args();
        //           将关系列表解析为个体
        foreach ($this->parseWithRelations($relations) as $name => $constraints) {
            // First we will determine if the name has been aliased using an "as" clause on the name
            // and if it has we will extract the actual relationship name and the desired name of
            // the resulting column. This allows multiple counts on the same relationship name.
            //
            // 首先我们要确定名称已使用“别名”这个名称条款和如果有我们将提取的实际关系名称和结果列所需的名字
            // 这允许在同一关系名称上的多个计数
            //
            $segments = explode(' ', $name);

            unset($alias);
            //                               将给定的字符串转为小写
            if (count($segments) == 3 && Str::lower($segments[1]) == 'as') {
                list($name, $alias) = [$segments[0], $segments[2]];
            }

            $relation = $this->getRelationWithoutConstraints($name);//获取“has relation”基础查询实例

            // Here we will get the relationship count query and prepare to add it to the main query
            // as a sub-select. First, we'll get the "has" query and use that to get the relation
            // count query. We will normalize the relation name then append _count as the name.
            //
            // 在这里，我们将得到关系计数查询，并准备将其添加到主查询作为子选择
            // 首先，我们将得到“has”查询并使用它来获取关系计数查询
            // 我们将恢复正常关系的名字添加_count作为名称
            //
            $query = $relation->getRelationExistenceCountQuery(//为关系计数查询添加约束
                //获取相关模型的关系        获取模型表的新查询生成器
                $relation->getRelated()->newQuery(), $this
            );
            //将给定的范围应用于当前生成器实例
            $query->callScope($constraints);
            //将约束从另一个查询合并到当前查询         获取关系的基础查询
            $query->mergeConstraintsFrom($relation->getQuery());

            // Finally we will add the proper result column alias to the query and run the subselect
            // statement against the query builder. Then we will return the builder instance back
            // to the developer for further constraint chaining that needs to take place on it.
            //
            // 最后，我们将添加适当的结果列别名查询运行select语句对查询生成器
            // 然后，我们将将生成器实例返回给需要进一步约束链接的开发人员
            //
            //           转换字符串为蛇形命名
            $column = snake_case(isset($alias) ? $alias : $name).'_count';
            //添加一个subselect表达式查询(获取基础查询生成器实例,)
            $this->selectSub($query->toBase(), $column);
        }

        return $this;
    }

    /**
     * Add the "has" condition where clause to the query.
     *
     * 将“has”条件where子句添加到查询子句中
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $hasQuery
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    protected function addHasWhere(Builder $hasQuery, Relation $relation, $operator, $count, $boolean)
    {
        //         将约束从另一个查询合并到当前查询(获取关系的基础查询)
        $hasQuery->mergeConstraintsFrom($relation->getQuery());

        return $this->canUseExistsForExistenceCheck($operator, $count)//检查是否可以运行“存在”查询以优化性能
                ? $this->addWhereExistsQuery($hasQuery->toBase(), $boolean, $not = ($operator === '<' && $count === 1))//向查询添加一个存在子句(获取基础查询生成器实例,)
                : $this->addWhereCountQuery($hasQuery->toBase(), $operator, $count, $boolean);//将子查询计数子句添加到该查询中(获取基础查询生成器实例,)
    }

    /**
     * Merge the where constraints from another query to the current query.
     *
     * 将约束从另一个查询合并到当前查询
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $from
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function mergeConstraintsFrom(Builder $from)
    {
        //           使用“点”符号从数组中获取一个项
        $whereBindings = Arr::get(
            //获取底层查询生成器实例->获取绑定的原始数组
            $from->getQuery()->getRawBindings(), 'where', []
        );

        // Here we have some other query that we want to merge the where constraints from. We will
        // copy over any where constraints on the query as well as remove any global scopes the
        // query might have removed. Then we will return ourselves with the finished merging.
        //
        // 在这里，我们有一些其他的查询，我们想合并的约束
        // 我们将复制查询的任何限制，以及移除查询可能移除的任何全局作用域
        // 然后我们将返回自己与完成合并
        //
        //           删除所有或经过注册的全局作用域(获取从查询中移除的全局作用域数组)->合并WHERE子句和绑定的数组(获取底层查询生成器实例->wheres,)
        return $this->withoutGlobalScopes(
            $from->removedScopes()
        )->mergeWheres(
            $from->getQuery()->wheres, $whereBindings
        );
    }

    /**
     * Add a sub-query count clause to this query.
     *
     * 将子查询计数子句添加到该查询中
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @return $this
     */
    protected function addWhereCountQuery(QueryBuilder $query, $operator = '>=', $count = 1, $boolean = 'and')
    {
        //             向查询添加绑定(在扁平数组中获取当前查询值绑定,)
        $this->query->addBinding($query->getBindings(), 'where');

        return $this->where( //将基本WHERE子句添加到查询中
            new Expression('('.$query->toSql().')'),//创建新的原始查询表达式(获取查询的sql表示形式)
            $operator,
            is_numeric($count) ? new Expression($count) : $count,
            $boolean
        );
    }

    /**
     * Get the "has relation" base query instance.
     *
     * 获取“has relation”基础查询实例
     *
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function getRelationWithoutConstraints($relation)
    {
        //          在关系上禁用约束的回调
        return Relation::noConstraints(function () use ($relation) {
            //获取被查询的模型实例
            return $this->getModel()->{$relation}();
        });
    }

    /**
     * Check if we can run an "exists" query to optimize performance.
     *
     * 检查是否可以运行“存在”查询以优化性能
     *
     * @param  string  $operator
     * @param  int  $count
     * @return bool
     */
    protected function canUseExistsForExistenceCheck($operator, $count)
    {
        return ($operator === '>=' || $operator === '<') && $count === 1;
    }
}
