<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Closure;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Query\JoinClause;

trait BuildsJoinClauses
{
    /**
     * Add a join clause to the query.
     *
     * The boolean argument flag is part of this method's API in Laravel.
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param mixed          $table
     * @param \Closure|string $first
     * @param string|null     $operator
     * @param string|null     $second
     * @param string          $type
     * @param bool            $where
     *
     * @return Builder
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
     * @param IlluminateQueryBuilder $parentQuery
     * @param string                 $type
     * @param string                 $table
     *
     * @return JoinClause
     */
    protected function newJoinClause(IlluminateQueryBuilder $parentQuery, $type, $table): JoinClause
    {
        return new JoinClause($parentQuery, $type, $table);
    }
}
