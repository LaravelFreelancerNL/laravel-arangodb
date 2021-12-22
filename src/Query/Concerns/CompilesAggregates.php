<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use LaravelFreelancerNL\Aranguent\Query\Builder;

trait CompilesAggregates
{
    /**
     * Compile an aggregated select clause.
     *
     * @param Builder $builder
     * @param array<mixed> $aggregate
     *
     * @return Builder
     */
    protected function compileAggregate(Builder $builder, array $aggregate): Builder
    {
        $method = 'compile' . ucfirst($aggregate['function']);

        return $this->$method($builder, $aggregate);
    }

    /**
     * Compile AQL for count aggregate.
     */
    protected function compileCount(Builder $builder): Builder
    {
        $builder->aqb = $builder->aqb->collect()->withCount('aggregateResult');

        return $builder;
    }

    /**
     * Compile AQL for max aggregate.
     *
     * @param Builder $builder
     * @param array<mixed> $aggregate
     *
     * @return Builder
     */
    protected function compileMax(Builder $builder, array $aggregate)
    {
        $column = $this->normalizeColumn($builder, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->max($column));

        return $builder;
    }

    /**
     * Compile AQL for min aggregate.
     *
     * @param array<mixed> $aggregate
     */
    protected function compileMin(Builder $builder, array $aggregate): Builder
    {
        $column = $this->normalizeColumn($builder, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->min($column));

        return $builder;
    }

    /**
     * Compile AQL for average aggregate.
     *
     * @param array<mixed> $aggregate
     */
    protected function compileAvg(Builder $builder, array $aggregate): Builder
    {
        $column = $this->normalizeColumn($builder, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->average($column));

        return $builder;
    }

    /**
     * Compile AQL for sum aggregate.
     *
     * @param Builder $builder
     * @param array<mixed> $aggregate
     *
     * @return Builder
     */
    protected function compileSum(Builder $builder, array $aggregate): Builder
    {
        $column = $this->normalizeColumn($builder, $aggregate['columns'][0]);

        $builder->aqb = $builder->aqb->collect()->aggregate('aggregateResult', $builder->aqb->sum($column));

        return $builder;
    }
}
