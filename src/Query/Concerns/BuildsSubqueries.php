<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\FluentAQL\Expressions\FunctionExpression;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

trait BuildsSubqueries
{
    /**
     * Add a subselect expression to the query.
     *
     * @param  Builder  $query
     * @param  string  $as
     * @return $this
     */
    public function selectSub($query, $as)
    {
        $query = $this->createSub($query);

        // Register $as as attribute alias
        $this->grammar->registerColumnAlias($as, $as);

        // Set $as as return value
        $this->columns[] = $as;

        // let alias = query
        $this->variables[$as] = $query;

        return $query;
    }

    /**
     * Parse the subquery into SQL and bindings.
     *
     * @param  Builder|EloquentBuilder|Relation|QueryBuilder|FunctionExpression|string  $query
     * @return array|QueryBuilder
     *
     * @throws \InvalidArgumentException
     */
    protected function parseSub($query)
    {
        if ($query instanceof self || $query instanceof EloquentBuilder || $query instanceof Relation) {
            $query = $this->prependDatabaseNameIfCrossDatabaseQuery($query);
            $query->grammar->compileSelect($query);

            if (isset($query->limit) && $query->limit == 1) {
                //Return the value, not an array of values
                /** @phpstan-ignore-next-line */
                return $query->aqb->first($query->aqb);
            }

            return $query->aqb;
        }

        if ($query instanceof QueryBuilder || $query instanceof FunctionExpression) {
            return $query;
        }

        if (is_string($query)) {
            return $query;
        }

        throw new InvalidArgumentException(
            'A subquery must be a query builder instance, a Closure, or a string.'
        );
    }
}
