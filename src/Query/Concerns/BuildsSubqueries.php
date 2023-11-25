<?php

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use InvalidArgumentException;
use LaravelFreelancerNL\Aranguent\Query\Builder;
use LaravelFreelancerNL\FluentAQL\Expressions\FunctionExpression;
use LaravelFreelancerNL\FluentAQL\QueryBuilder;

trait BuildsSubqueries
{
    protected function hasLimitOfOne(IlluminateQueryBuilder $query)
    {
        if (isset($query->limit) && $query->limit == 1) {
            return true;
        }

        return false;
    }

    /**
     * Parse the subquery into SQL and bindings.
     *
     * @param  Builder|EloquentBuilder|Relation|QueryBuilder|FunctionExpression|string  $query
     * @return array<mixed>|QueryBuilder
     *
     * @throws \InvalidArgumentException
     */
    //    protected function parseSub($query)
    //    {
    //        if ($query instanceof self || $query instanceof EloquentBuilder || $query instanceof Relation) {
    //            $query = $this->prependDatabaseNameIfCrossDatabaseQuery($query);
    //            $query->grammar->compileSelect($query);
    //
    //            if (isset($query->limit) && $query->limit == 1) {
    //                //Return the value, not an array of values
    //                /** @phpstan-ignore-next-line */
    //                return $query->aqb->first($query->aqb);
    //            }
    //
    //            return $query->aqb;
    //        }
    //
    //        if ($query instanceof QueryBuilder || $query instanceof FunctionExpression) {
    //            return $query;
    //        }
    //
    //        if (is_string($query)) {
    //            return $query;
    //        }
    //
    //        throw new InvalidArgumentException(
    //            'A subquery must be a query builder instance, a Closure, or a string.'
    //        );
    //    }
}
