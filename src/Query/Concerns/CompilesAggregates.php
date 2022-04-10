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
     * @param IlluminateBuilder $query
     * @param  array<mixed>  $aggregate
     * @return string
     */
    protected function compileAggregate(IlluminateBuilder $query, $aggregate): string
    {
        $method = 'compile' . ucfirst($aggregate['function']);

        return $this->$method($query, $aggregate);
    }

    /**
     * Compile AQL for count aggregate.
     */
    protected function compileCount(Builder $query): Builder
    {
        $query->aqb = $query->aqb->collect()->withCount('aggregateResult');

        return $query;
    }

    /**
     * Compile AQL for max aggregate.
     *
     * @param Builder $query
     * @param array<mixed> $aggregate
     *
     * @return Builder
     */
    protected function compileMax(Builder $query, array $aggregate)
    {
        $column = $this->normalizeColumn($query, $aggregate['columns'][0]);

        $query->aqb = $query->aqb->collect()->aggregate('aggregateResult', $query->aqb->max($column));

        return $query;
    }

    /**
     * Compile AQL for min aggregate.
     *
     * @param array<mixed> $aggregate
     */
    protected function compileMin(Builder $query, array $aggregate): Builder
    {
        $column = $this->normalizeColumn($query, $aggregate['columns'][0]);

        $query->aqb = $query->aqb->collect()->aggregate('aggregateResult', $query->aqb->min($column));

        return $query;
    }

    /**
     * Compile AQL for average aggregate.
     *
     * @param array<mixed> $aggregate
     */
    protected function compileAvg(Builder $query, array $aggregate): Builder
    {
        $column = $this->normalizeColumn($query, $aggregate['columns'][0]);

        $query->aqb = $query->aqb->collect()->aggregate('aggregateResult', $query->aqb->average($column));

        return $query;
    }

    /**
     * Compile AQL for sum aggregate.
     *
     * @param Builder $query
     * @param array<mixed> $aggregate
     *
     * @return Builder
     */
    protected function compileSum(Builder $query, array $aggregate): Builder
    {
        $column = $this->normalizeColumn($query, $aggregate['columns'][0]);

        $query->aqb = $query->aqb->collect()->aggregate('aggregateResult', $query->aqb->sum($column));

        return $query;
    }
}
