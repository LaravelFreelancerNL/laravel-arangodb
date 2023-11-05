<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateBuilder;
use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesAggregates
{
    /**
     * Compile an aggregated select clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $aggregate
     * @return string
     */
    protected function compileAggregate(IlluminateBuilder $query, $aggregate)
    {
        $method = 'compile'.ucfirst($aggregate['function']);

        return $this->$method($query, $aggregate);
    }

    /**
     * Compile AQL for count aggregate.
     */
    protected function compileCount(Builder $query)
    {
        return "COLLECT WITH COUNT INTO aggregateResult";
    }

    /**
     * Compile AQL for max aggregate.
     *
     * @param  array<mixed>  $aggregate
     * @return string
     */
    protected function compileMax(Builder $query, array $aggregate)
    {
        $column = $this->normalizeColumn($query, $aggregate['columns'][0]);

        return "COLLECT AGGREGATE aggregateResult = MAX($column)";
    }

    /**
     * Compile AQL for min aggregate.
     *
     * @param  array<mixed>  $aggregate
     */
    protected function compileMin(Builder $query, array $aggregate)
    {
        $column = $this->normalizeColumn($query, $aggregate['columns'][0]);

        return "COLLECT AGGREGATE aggregateResult = MIN($column)";
    }

    /**
     * Compile AQL for average aggregate.
     *
     * @param  array<mixed>  $aggregate
     */
    protected function compileAvg(Builder $query, array $aggregate)
    {
        $column = $this->normalizeColumn($query, $aggregate['columns'][0]);

        return "COLLECT AGGREGATE aggregateResult = AVERAGE($column)";
    }

    /**
     * Compile AQL for sum aggregate.
     *
     * @param  array<mixed>  $aggregate
     */
    protected function compileSum(Builder $query, array $aggregate)
    {
        $column = $this->normalizeColumn($query, $aggregate['columns'][0]);

        return "COLLECT AGGREGATE aggregateResult = SUM($column)";
    }
}
