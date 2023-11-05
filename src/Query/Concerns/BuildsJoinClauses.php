<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Closure;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Query\JoinClause;
use LogicException;

trait BuildsJoinClauses
{
    /**
     * Add a right join to the query.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|string|null  $second
     * @return $this
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        throw new LogicException('This database engine does not support the rightJoin method.');
    }

    /**
     * Add a subquery right join to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|string  $query
     * @param  string  $as
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|string|null  $second
     * @return $this
     */
    public function rightJoinSub($query, $as, $first, $operator = null, $second = null)
    {
        throw new LogicException('This database engine does not support the rightJoinSub method.');
    }

    /**
     * Add a "right join where" clause to the query.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $table
     * @param  \Closure|string  $first
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $second
     * @return $this
     */
    public function rightJoinWhere($table, $first, $operator, $second)
    {
        throw new LogicException('This database engine does not support the rightJoinWhere method.');
    }


    /**
     * Add a join clause to the query.
     *
     * The boolean argument flag is part of this method's API in Laravel.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param  mixed  $table
     * @param  \Closure|string  $first
     * @param  string|null  $operator
     * @param  string|null  $second
     * @param  string  $type
     * @param  bool  $where
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false): Builder
    {
        $join = $this->newJoinClause($this, $type, $table);

        // If the first "column" of the join is really a Closure instance the developer
        // is trying to build a join with a complex "on" clause containing more than
        // one condition, so we'll add the join and call a Closure with the query.
        if ($first instanceof Closure) {
            $first($join);

            $this->joins[] = $join;
        }
        if (! $first instanceof Closure) {
            // If the column is simply a string, we can assume the join simply has a basic
            // "on" clause with a single condition. So we will just build the join with
            // this simple join clauses attached to it. There is not a join callback.

            //where and on are the same for aql
            $method = $where ? 'where' : 'on';

            $this->joins[] = $join->$method($first, $operator, $second);
        }

        return $this;
    }

    /**
     * Get a new join clause.
     *
     * @param  string  $type
     * @param  string  $table
     */
    protected function newJoinClause(IlluminateQueryBuilder $parentQuery, $type, $table): JoinClause
    {
        return new JoinClause($parentQuery, $type, $table);
    }
}
