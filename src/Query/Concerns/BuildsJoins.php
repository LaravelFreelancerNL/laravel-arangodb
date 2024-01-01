<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder as IlluminateEloquentBuilder;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Query\JoinClause;
use LogicException;

trait BuildsJoins
{
    /**
     * Add a right join to the query.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $table
     * @param Closure|string  $first
     * @param  string|null  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|string|null  $second
     * @return $this
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        throw new LogicException('This database driver does not support the rightJoin method.');
    }

    /**
     * Add a subquery right join to the query.
     *
     * @param Closure|IlluminateQueryBuilder|IlluminateEloquentBuilder|string  $query
     * @param  string  $as
     * @param Closure|string  $first
     * @param  string|null  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|string|null  $second
     * @return $this
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function rightJoinSub($query, $as, $first, $operator = null, $second = null)
    {
        throw new LogicException('This database driver does not support the rightJoinSub method.');
    }

    /**
     * Add a "right join where" clause to the query.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $table
     * @param Closure|string  $first
     * @param  string  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $second
     * @return $this
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function rightJoinWhere($table, $first, $operator, $second)
    {
        throw new LogicException('This database driver does not support the rightJoinWhere method.');
    }


    /**
     * Add a join clause to the query.
     *
     * The boolean argument flag is part of this method's API in Laravel.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param  mixed  $table
     * @param Closure|string  $first
     * @param  string|null  $operator
     * @param  float|int|string|null  $second
     * @param  string  $type
     * @param  bool  $where
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false): IlluminateQueryBuilder
    {
        $join = $this->newJoinClause($this, $type, $table);

        // If the first "column" of the join is really a Closure instance the developer
        // is trying to build a join with a complex "on" clause containing more than
        // one condition, so we'll add the join and call a Closure with the query.
        if ($first instanceof Closure) {
            $first($join);

            $this->joins[] = $join;
        }
        if (!$first instanceof Closure) {
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
     * Add a subquery join clause to the query.
     *
     * @param Closure|IlluminateQueryBuilder|IlluminateEloquentBuilder|string  $query
     * @param  string  $as
     * @param Closure|string  $first
     * @param  string|null  $operator
     * @param  float|int|string|null  $second
     * @param  string  $type
     * @param  bool  $where
     *
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function joinSub($query, $as, $first, $operator = null, $second = null, $type = 'inner', $where = false): IlluminateQueryBuilder
    {
        assert($query instanceof Builder);

        $query->importTableAliases($this);
        $query->importTableAliases([$as => $as]);
        $this->importTableAliases($query);

        [$query] = $this->createSub($query);

        return  $this->join(new Expression($query . ' as ' . $as), $first, $operator, $second, $type, $where);
    }


    /**
     * Add a left join to the query.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $table
     * @param Closure|string  $first
     * @param  string|null  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|string|null  $second
     */
    public function leftJoin($table, $first, $operator = null, $second = null): IlluminateQueryBuilder
    {
        return $this->join(
            $table,
            $first,
            $operator,
            $this->grammar->getValue($second),
            'left'
        );
    }

    /**
     * Add a subquery left join to the query.
     *
     * @param Closure|IlluminateQueryBuilder|IlluminateEloquentBuilder|string  $query
     * @param  string  $as
     * @param Closure|string  $first
     * @param  string|null  $operator
     * @param  \Illuminate\Contracts\Database\Query\Expression|string|null  $second
     */
    public function leftJoinSub($query, $as, $first, $operator = null, $second = null): IlluminateQueryBuilder
    {
        return $this->joinSub($query, $as, $first, $operator, $this->grammar->getValue($second), 'left');
    }


    /**
     * Get a new join clause.
     *
     * @param  string  $type
     * @param  string  $table
     */
    protected function newJoinClause(IlluminateQueryBuilder $parentQuery, $type, $table)
    {
        // @phpstan-ignore-next-line
        return new JoinClause($parentQuery, $type, $table);
    }

}
