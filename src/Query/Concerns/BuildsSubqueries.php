<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Builder as IlluminateEloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use InvalidArgumentException;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\Aranguent\Query\Grammar;

trait BuildsSubqueries
{
    /**
     * IN SQL subqueries in selects and where's need to return a single value,
     * whereas subqueries in joins return an object. This variable lets the
     * compiler know how to return a single value.
     *
     * @var bool
     */
    public bool $returnSingleValue = false;

    /**
     * Creates a subquery and parse it.
     *
     * @param  \Closure|IlluminateQueryBuilder|IlluminateEloquentBuilder|string $query
     * @return array
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function createSub($query, bool $returnSingleValue = false)
    {
        // If the given query is a Closure, we will execute it while passing in a new
        // query instance to the Closure. This will give the developer a chance to
        // format and work with the query before we cast it to a raw SQL string.
        if ($query instanceof Closure) {
            $callback = $query;

            $callback($query = $this->forSubQuery());
        }

        assert($query instanceof Builder);

        if ($returnSingleValue) {
            $query->returnSingleValue = $returnSingleValue;
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
        assert($query instanceof Builder);

        if ($query->limit === 1 || $query->returnSingleValue === true) {
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

            assert($this->grammar instanceof Grammar);
            $queryString = $this->grammar->wrapSubquery($query->toSql());

            if ($this->hasLimitOfOne($query)) {
                $queryString = 'FIRST(' . $queryString . ')';
            }

            return [$queryString, $query->getBindings()];
        }

        if (is_string($query)) {
            return [$query, []];
        }

        throw new InvalidArgumentException(
            'A subquery must be a query builder instance, a Relation, a Closure, or a string.'
        );
    }
}
