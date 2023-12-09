<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Builder as IlluminateEloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use InvalidArgumentException;

trait BuildsSubqueries
{
    /**
     * Creates a subquery and parse it.
     *
     * @param  \Closure|IlluminateQueryBuilder|IlluminateEloquentBuilder|string $query
     * @return array
     */
    protected function createSub($query)
    {
        // If the given query is a Closure, we will execute it while passing in a new
        // query instance to the Closure. This will give the developer a chance to
        // format and work with the query before we cast it to a raw SQL string.
        if ($query instanceof Closure) {
            $callback = $query;

            $callback($query = $this->forSubQuery());
        }

        return $this->parseSub($query);
    }

    /**
     * Create a new query instance for sub-query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function forSubQuery()
    {
        return $this->newQuery();
    }

    protected function hasLimitOfOne(IlluminateQueryBuilder $query)
    {
        if (isset($query->limit) && $query->limit == 1) {
            return true;
        }

        return false;
    }

    /**
     * Parse the subquery into AQL and bindings.
     *
     * @param  mixed  $query
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseSub($query)
    {
        if ($query instanceof self || $query instanceof EloquentBuilder || $query instanceof Relation) {
            $this->exchangeTableAliases($query);

            $this->importBindings($query);

            $queryString = $this->grammar->wrapSubquery($query->toSql());

            if ($this->hasLimitOfOne($query)) {
                $queryString = 'FIRST(' . $queryString . ')';
            }

            return [$queryString, $query->getBindings()];
        } elseif (is_string($query)) {
            return [$query, []];
        } else {
            throw new InvalidArgumentException(
                'A subquery must be a query builder instance, a Relation, a Closure, or a string.'
            );
        }
    }
}
